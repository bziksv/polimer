<?php
namespace Avito\Export\Components;

use Avito\Export\Chat;
use Bitrix\Main;
use Avito\Export\DB;
use Avito\Export\Api;
use Avito\Export\Exchange;

/** @noinspection PhpUnused */
class AdminChatAjax extends Main\Engine\Controller
{
	public function chatsAction(int $setupId, int $userId, int $limit, int $offset) : array
	{
		return $this->actionWrapper(function() use ($setupId, $userId, $limit, $offset) {
			$setup = $this->loadSetup($setupId);
			$chatsRequest = new Api\Messenger\V2\Accounts\User\Chats\Request();
			$chatsRequest->token($setup->getSettings()->commonSettings()->token());
			$chatsRequest->userId($userId);
			$chatsRequest->limit($limit);
			$chatsRequest->offset($offset);
			$chatsResponse = $chatsRequest->execute();

			$chatsCollection = $chatsResponse->chats();
			if ($chatsCollection === null || $chatsCollection->isEmpty())
			{
				throw new Main\SystemException($this->lang('EMPTY_CHATS'));
			}

			$chats = [];

			/** @var Api\Messenger\V2\Model\Chat $chat */
			foreach ($chatsCollection as $chat)
			{
				$chats[$chat->id()] = $this->formatChat($chat);
			}

			return $chats;
		});
	}

	public function chatByIdAction(int $setupId, int $userId, string $chatId) : array
	{
		return $this->actionWrapper(function() use ($setupId, $userId, $chatId) {
			$setup = $this->loadSetup($setupId);
			$chatRequest = new Api\Messenger\V2\Accounts\Chat\Request();
			$chatRequest->token($setup->getSettings()->commonSettings()->token());
			$chatRequest->userId($userId);
			$chatRequest->chatId($chatId);
			$chatResponse = $chatRequest->execute();
			return $this->formatChat($chatResponse);
		});
	}

	protected function formatChat(Api\Messenger\V2\Model\Chat $chat) : array
	{
		/** @var Api\Messenger\V2\Model\Chat\User $user */
		$userMain = $chat->users()->rewind();
		$chatId = $chat->id();
		$images = $chat->context()->value()->images();
		$imageMain = null;
		if ($images !== null)
		{
			$imageMain = $images->main();
			$imageMain = reset($imageMain);
		}
		$replace = $this->lang('SYSTEM_MESSAGE_PART');
		$lastMessage = $chat->lastMessage();

		if ($lastMessage === null)
		{
			$content = [
				'message' => $this->lang('NOT_LAST_MESSAGE'),
				'direction' => 'out',
				'type' => 'text',
				'read' => true,
			];
		}
		else
		{
			$content = [
				'message' => trim(str_replace($replace, '', $lastMessage->content())),
				'direction' => $lastMessage->direction(),
				'type' => $lastMessage->type(),
				'read' => $lastMessage->read() !== null,
			];
		}

		return [
			'id' => $chatId,
			'img' => $imageMain,
			'name' => $userMain->name(),
			'title' => $chat->context()->value()->title(),
			'url' => $chat->context()->value()->url(),
			'users' => $this->meaningfulUsers($chat->users()),
			'created' => $chat->created(),
			'updated' => $chat->updated(),
		] + $content;
	}

	protected function meaningfulUsers(Api\Messenger\V2\Model\Chat\Users $users) : array
	{
		$result = [];

		/** @var \Avito\Export\Api\Messenger\V2\Model\Chat\User $user */
		foreach ($users as $user)
		{
			$avatarImages = $user->publicUserProfile()->avatar()->images();
			rsort($avatarImages);
			$avatarImages = reset($avatarImages);
			$avatarChar = mb_strpos($avatarImages, 'static') !== false
				? mb_strtoupper(mb_substr($user->name(), 0, 1)) : false;

			$result[$user->id()] = [
				'name' => $user->name(),
				'avatar' => $avatarImages,
				'avatarChar' => $avatarChar,
				'url' => $user->publicUserProfile()->url(),
			];
		}

		return $result;
	}

	public function messagesByChatIdAction(int $setupId, int $userId, string $chatId, int $limit, int $offset) : array
	{
		return $this->actionWrapper(function() use ($setupId, $userId, $chatId, $limit, $offset) {
			$setup = $this->loadSetup($setupId);
			$chatRequest = new Api\Messenger\V3\Accounts\Chats\Messages\Request();
			$chatRequest->token($setup->getSettings()->commonSettings()->token());
			$chatRequest->userId($userId);
			$chatRequest->chatId($chatId);
			$chatRequest->limit($limit);
			$chatRequest->offset($offset);
			$chatResponse = $chatRequest->execute();
			$messages = $chatResponse->messages();

			$result = [];

			/** @var Api\Messenger\V3\Model\Message $message */
			foreach ($messages as $key => $message)
			{
				$rawData = $message->rawData();
				$rawData['content'] = $this->formatContent($message->type(), $message->content()->rawData());
				$quote = $message->quote();

				if ($quote !== null)
				{
					$rawData['quote']['content'] = $this->formatContent($quote->type(), $quote->content()->rawData());
				}

				$rawData['delimiter'] = null;
				$created = Main\Type\DateTime::createFromTimestamp($message->created());
                $rawData['deletable'] = $this->deletableMessage($message->created(), $message->type(), $message->direction());
				$next = $key === count($messages) - 1 ? $message : $messages[$key + 1];
				$nextCreated = Main\Type\DateTime::createFromTimestamp($next->created());

				if (
					$key === count($messages) - 1
					|| $created->format('d.m') !== $nextCreated->format('d.m')
				)
				{
					$rawData['delimiter'] = $this->formatDelimiter($created);
				}

				$rawData['rendering_avatar'] = $key === count($messages) - 1
					|| (isset($rawData['delimiter']) && $next->authorId() === $message->authorId())
					|| $next->authorId() !== $message->authorId();

				$rawData['createdTime'] = $created->format('H:i');
				$rawData['isRead'] = ($message->read() !== null);

                $result[$key] = $rawData;
			}

			return $result;
		});
	}

	protected function formatContent(string $type, array $content) : array
	{
		if ($type === 'call' || $type === 'video' || $type === 'image' || $type === 'link')
		{
			$content += [
				'text' => $this->lang(mb_strtoupper($type) . '_MESSAGE_TEXT'),
			];
		}

		if ($type === 'system')
		{
			$replace = $this->lang('SYSTEM_MESSAGE_PART');
			$content['text'] = trim(str_replace($replace, '', $content['text']));
		}

		return $content;
	}

	public function sendMessageAction(int $setupId, int $userId, string $chatId, string $message) : array
	{
		return $this->actionWrapper(function() use ($setupId, $userId, $chatId, $message) {
			$setup = $this->loadSetup($setupId);
			$chatRequest = new Api\Messenger\V1\Accounts\Chats\Messages\Send\Request();
			$chatRequest->token($setup->getSettings()->commonSettings()->token());
			$chatRequest->userId($userId);
			$chatRequest->chatId($chatId);
			$chatRequest->message($message);

            $chatResponse = $chatRequest->execute();
			$result = $chatResponse->rawData();
			$created = Main\Type\DateTime::createFromTimestamp($result['created']);
			$result['createdTime'] = $created->format('H:i');
			$result['deletable'] = $this->deletableMessage($chatResponse->created(), $chatResponse->type(), $chatResponse->direction());
			$result['isRead'] = false;
			$result['chat_id'] = $chatId;
			$result['delimiter'] = $this->formatDelimiter($created);
			$result['rendering_avatar'] = true;

			return $result;
		});
	}

	public function checkNewMessagesAction(int $setupId, int $checkTimestamp) : array
	{
		return $this->actionWrapper(function() use ($setupId, $checkTimestamp) {
			$query = Chat\Unread\MessageTable::getList([
				'filter' => [
					'=SETUP_ID' => $setupId,
                    [
                        'LOGIC' => 'OR',
                        [ '>=CREATED' => Main\Type\DateTime::createFromTimestamp($checkTimestamp) ],
	                    [ '>=READ' => Main\Type\DateTime::createFromTimestamp($checkTimestamp) ]
                    ]
				],
			]);

			$result = [];

            $result['checkTimestamp'] = time();

			/** @var Chat\Unread\Message $message */
			while ($message = $query->fetchObject())
			{
				$created = $message->getCreated()->getTimestamp();
				$direction = $message->getAuthorId() === $message->getUserId() ? 'out' : 'in';
				$content = $message->getContent();
				$type = $message->getType();
				$result['messages'][] = [
					'id' => $message->getExternalId(),
					'author_id' => $message->getAuthorId(),
					'chat_id' => $message->getChatId(),
					'chat_message' => $this->formatContent($type, $content),
					'chat_type' => $message->getChatType(),
					'content' => $content,
					'createdTime' => $message->getCreated()->format('H:i'),
					'created' => $created,
					'item_id' => $message->getItemId(),
					'type' => $type,
					'user_id' => $message->getUserId(),
					'direction' => $direction,
					'deletable' => $this->deletableMessage($created, $message->getType(), $direction),
					'rendering_avatar' => true,
					'delimiter' => $this->formatDelimiter($message->getCreated()),
					'isRead' => $message->getRead() !== null,
				];
			}

			return $result;
		});
	}

	public function readChatAction(int $setupId, int $userId, string $chatId) : array
	{
		return $this->actionWrapper(function() use ($setupId, $userId, $chatId) {
			$setup = $this->loadSetup($setupId);
			$chatRequest = new Api\Messenger\V1\Accounts\Chats\Messages\Read\Request();
			$chatRequest->token($setup->getSettings()->commonSettings()->token());
			$chatRequest->userId($userId);
			$chatRequest->chatId($chatId);

			if (empty($chatRequest->execute()->rawData()))
			{
                Chat\Informer\Actualizer::markRead($setupId, [$chatId], true);
                Chat\Informer\Actualizer::scheduleClear($setupId, [$chatId]);
			}

			return [];
		});
	}

    public function deleteMessageAction(int $setupId, int $userId, string $chatId, string $messageId) : array
    {
        return $this->actionWrapper(function() use ($setupId, $userId, $chatId, $messageId) {
            $setup = $this->loadSetup($setupId);
            $chatRequest = new Api\Messenger\V1\Accounts\Chats\Messages\Delete\Request();
            $chatRequest->token($setup->getSettings()->commonSettings()->token());
            $chatRequest->userId($userId);
            $chatRequest->chatId($chatId);
            $chatRequest->messageId($messageId);
	        $chatRequest->execute();

            return [];
        });
    }

    protected function calculateDateDiff(int $timestamp) : float
    {
        $curr = new Main\Type\DateTime();
        $curr = $curr->getTimestamp();
        $diff = $curr - $timestamp;
        return $diff / (60 * 60);
    }

	protected function deletableMessage(int $created, string $type, string $direction) : bool
	{
		return (
			$this->calculateDateDiff($created) < 1
			&& $type !== 'system'
			&& $type !== 'deleted'
			&& $direction === 'out'
		);
	}

	protected function formatDelimiter(Main\Type\DateTime $dateTime) : string
	{
		$nowDateTime = (new Main\Type\DateTime())->format('d.m');

		if ($nowDateTime === $dateTime->format('d.m'))
		{
			return $this->lang('NAME_DAY_WEEK_NOW');
		}

		$nameDayWeek = $this->lang('NAME_DAY_WEEK_' . (int)$dateTime->format('N'));
		$nameMonth = $this->lang('NAME_MONTH_' . (int)$dateTime->format('m'));
		$day = (int)$dateTime->format('d');

		return $this->lang('NAME_DELIMITER', [
			'#DAY_WEEK#' => $nameDayWeek,
			'#DAY#' => $day,
			'#MONTH#' => $nameMonth,
		]);
	}

	protected function loadSetup(int $setupId) : Chat\Setup\Model
	{
		return Exchange\Setup\Model::getById($setupId)->getChat();
	}

	protected function actionWrapper(callable $function) : array
	{
		try
		{
			$this->loadModule();

			session_write_close();

			return [
				'status' => 'ok',
				'data' => call_user_func($function),
			];
		}
		catch (Main\SystemException $exception)
		{
			return [
				'status' => 'error',
				'message' => $exception->getMessage(),
			];
		}
	}

	protected function lang(string $key, array $replace = null) : string
	{
		return Main\Localization\Loc::getMessage('AVITO_EXPORT_ADMIN_CHAT_' . $key, $replace) ?: $key;
	}

	protected function loadModule() : void
	{
		if (!Main\Loader::includeModule('avito.export'))
		{
			throw new Main\SystemException('Module avito.export is required');
		}
	}
}