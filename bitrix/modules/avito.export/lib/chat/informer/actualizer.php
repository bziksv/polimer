<?php
namespace Avito\Export\Chat\Informer;

use Avito\Export\Agent as AgentReference;
use Avito\Export\Chat\Unread\MessageTable;
use Avito\Export\Chat\Setup;
use Avito\Export\Exchange;
use Avito\Export\Api;
use Avito\Export\DB;
use Avito\Export\Glossary;
use Bitrix\Main;

class Actualizer extends AgentReference\Base
{
	public static function getDefaultParams() : array
	{
		return [
			'interval' => 300,
		];
	}

	public static function isInstalled(int $setupId) : bool
	{
		return static::isRegistered([
			'method' => 'process',
			'arguments' => [ $setupId ],
			'search' => AgentReference\Controller::SEARCH_RULE_SOFT,
		]);
	}

	public static function install(int $setupId) : void
	{
		$params = static::getDefaultParams();

		static::register([
			'method' => 'process',
			'arguments' => [ $setupId ],
			'search' => AgentReference\Controller::SEARCH_RULE_SOFT,
			'next_exec' => (new Main\Type\DateTime())->add(sprintf('PT%sS', $params['interval'])),
		]);
	}

	public static function uninstall(int $setupId) : void
	{
		static::unregister([
			'method' => 'process',
			'arguments' => [ $setupId ],
			'search' => AgentReference\Controller::SEARCH_RULE_SOFT,
		]);
	}

	public static function process(int $setupId, int $repeat = 0)
	{
		$chatSetup = null;

		try
		{
			$chatIds = static::chatIds($setupId);
			++$repeat;

			if (empty($chatIds)) { return false; }

			$chatSetup = static::chatSetup($setupId);
			[$read, $unread] = static::splitReadChats($chatSetup, $chatIds);

			static::markRead($setupId, $read);
			static::scheduleClear($setupId, $read);

			if (empty($unread) || $repeat >= 20) { return false; }

			return [ $setupId, $repeat ];
		}
		catch (\Throwable $exception)
		{
			if ($chatSetup !== null)
			{
				$chatSetup->makeLogger()->error($exception, [
					'ENTITY_TYPE' => Glossary::ENTITY_MESSAGE,
				]);
			}
			else
			{
				trigger_error($exception->getMessage(), E_USER_WARNING);
			}

			return false;
		}
	}

	public static function scheduleClear(int $setupId, array $read) : void
	{
		if (empty($read)) { return; }

		static::register([
			'method' => 'clear',
			'arguments' => [ $setupId ],
			'next_exec' => (new Main\Type\DateTime())->add(static::expireInterval()),
		]);
	}

	public static function clear(int $setupId) : bool
	{
		$batch = new DB\Facade\BatchDelete(MessageTable::class);
		$batch->run([
			'filter' => [
				'=SETUP_ID' => $setupId,
				'!READ' => false,
				'<=READ' => (new Main\Type\DateTime())->add('-' . static::expireInterval()),
			],
		]);

		return false;
	}

	public static function chatSetup(int $setupId) : Setup\Model
	{
		return Exchange\Setup\Model::getById($setupId)->getChat();
	}

	protected static function chatIds(int $setupId) : array
	{
		$query = MessageTable::getList([
			'filter' => [ '=SETUP_ID' => $setupId, 'READ' => false ],
			'select' => [ 'CHAT_ID' ],
			'group' => [ 'CHAT_ID' ],
		]);

		return array_column($query->fetchAll(), 'CHAT_ID');
	}

	protected static function splitReadChats(Setup\Model $chatSetup, array $chatIds) : array
	{
		$read = [];
		$unread = [];

		foreach ($chatIds as $chatId)
		{
			if (static::hasUnread($chatSetup, $chatId))
			{
				$unread[] = $chatId;
			}
			else
			{
				$read[] = $chatId;
			}
		}

		return [ $read, $unread ];
	}

	protected static function hasUnread(Setup\Model $chatSetup, string $chatId) : bool
	{
		$token = $chatSetup->getSettings()->commonSettings()->token();
		$userId = $token->getServiceId();
		$hasUnread = false;

		$request = new Api\Messenger\V3\Accounts\Chats\Messages\Request();
		$request->token($token);
		$request->userId($userId);
		$request->chatId($chatId);

		/** @var Api\Messenger\V3\Model\Message $message */
		foreach ($request->execute()->messages() as $message)
		{
			if ($message->read() === null)
			{
				$hasUnread = true;
				break;
			}
		}

		return $hasUnread;
	}

	public static function markRead(int $setupId, array $chatIds, bool $onlyIncoming = false) : void
	{
		if (empty($chatIds)) { return; }

		$filter = Main\ORM\Query\Query::filter()
			->where('SETUP_ID', $setupId)
			->whereIn('CHAT_ID', $chatIds)
			->whereNull('READ');

		if ($onlyIncoming)
		{
			$filter->whereColumn('USER_ID', '=', 'AUTHOR_ID');
		}

		$batch = new DB\Facade\BatchUpdate(MessageTable::class);
		$batch->run([
			'filter' => $filter,
		], [
			'READ' => new Main\Type\DateTime(),
		]);
	}

	protected static function expireInterval() : string
	{
		return 'PT1H';
	}
}