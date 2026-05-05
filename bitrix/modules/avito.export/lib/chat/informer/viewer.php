<?php
namespace Avito\Export\Chat\Informer;

use Avito\Export\Event as ReferenceEvent;
use Avito\Export\Psr;
use Avito\Export\Exchange;
use Avito\Export\Concerns;
use Avito\Export\Admin;
use Avito\Export\Chat\Unread;
use Bitrix\Main;

class Viewer extends ReferenceEvent\Base
{
	use Concerns\HasLocale;

	public static function install() : void
	{
		foreach (static::handlers() as $handler)
		{
			static::register($handler);
		}
	}

	public static function uninstall() : void
	{
		foreach (static::handlers() as $handler)
		{
			static::unregister($handler);
		}
	}

	protected static function handlers() : array
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnAdminInformerInsertItems',
			],
		];
	}

	/** @noinspection PhpUnused */
	public static function OnAdminInformerInsertItems() : void
	{
		try
		{
			if (static::isChatPage()) { return; }

			$messages = static::loadMessages();

			if (empty($messages))
			{
				static::uninstall();
				return;
			}

			static::render($messages);
			static::actualizeTask($messages);
		}
		catch (\Throwable $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
	}

	protected static function isChatPage() : bool
	{
		global $APPLICATION;

		return (mb_strpos($APPLICATION->GetCurPage(), 'avito_export_chat.php') !== false);
	}

	protected static function loadMessages() : array
	{
		$messages = [];

		$query = Unread\MessageTable::getList([
			'filter' => [ 'READ' => false ],
			'order' => [ 'CREATED' => 'DESC' ],
			'limit' => 5,
		]);

		/** @var Unread\Message $message */
		while ($message = $query->fetchObject())
		{
			$chatId = $message->getChatId();

			if (isset($messages[$chatId])) { continue; }
			if ($message->getUserId() === $message->getAuthorId()) { continue; }

			$messages[$chatId] = [
				'setupId' => $message->getSetupId(),
				'chatId' => $chatId,
				'userId' => $message->getUserId(),
				'content' => $message->formatContent(),
			];
		}

		return $messages;
	}

	protected static function render(array $messages) : void
	{
		echo Main\UI\Extension::getHtml('avitoexport.admin.informer');

		foreach ($messages as $chatId => $message)
		{
			\CAdminInformer::AddItem([
				'TITLE' => self::getLocale('TITLE'),
				'COLOR' => 'avito-white',
				'FOOTER' => static::footerHtml($chatId, $message),
				'HTML' => $message['content'],
				'SORT' => 1
			]);

			\CAdminInformer::$alertCounter++;
		}
	}

	/**
	 * @noinspection HtmlUnknownTarget
	 * @noinspection BadExpressionStatementJS
	 * @noinspection JSUnnecessarySemicolon
	 * @noinspection JSVoidFunctionReturnValueUsed
	 */
	protected static function footerHtml(string $chatId, array $message) : string
	{
		$chatUrl = Admin\Path::moduleUrl('chat', [
			'lang' => LANGUAGE_ID,
			'setup' => $message['setupId'],
			'chatId' => $chatId,
		]);

		$reply = sprintf('<a href="%s">%s</a>', $chatUrl, self::getLocale('REPLY'));
		$read = sprintf(
			'<a href="#" onclick=\'avitoHideInformerMessage(this, %s); return false;\' style="float: right !important; font-size: 0.8em !important; margin-top: 0.25em !important;">%s</a>',
			Main\Web\Json::encode(array_intersect_key($message, [
				'setupId' => true,
				'chatId' => true,
				'userId' => true,
			])),
			self::getLocale('READ')
		);

		return $reply . $read;
	}

	protected static function actualizeTask(array $messages) : void
	{
		$setupIds = array_column($messages, 'SETUP_ID', 'SETUP_ID');

		foreach ($setupIds as $setupId)
		{
			if (Actualizer::isInstalled($setupId)) { continue; }

			Actualizer::install($setupId);
		}
	}
}