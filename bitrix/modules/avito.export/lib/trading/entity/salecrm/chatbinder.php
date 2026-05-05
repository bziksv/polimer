<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Avito\Export\Assert;
use Avito\Export\DB;
use Avito\Export\Exchange;
use Avito\Export\Trading\Entity as TradingEntity;
use Bitrix\Main;
use Bitrix\ImOpenLines;

class ChatBinder extends TradingEntity\Sale\EventHandler
{
	public function handlers() : array
	{
		return [
			[
				'module' => 'imopenlines',
				'event' => 'OnSessionStart',
			],
		];
	}

	/** @noinspection PhpUnused */
	public static function OnSessionStart(Main\Event $event) : void
	{
		try
		{
			/** @var ImOpenLines\Session $session */
			$session = $event->getParameter('RUNTIME_SESSION');
			[$type, , $chatId] = explode('|', $session->getData('USER_CODE'));
			$imChat = $session->getChat();
			$environment = TradingEntity\Registry::environment();

			if ($type !== ChatRegistry::CONNECTOR_ID || $imChat === null || !($environment instanceof Container)) { return; }

			$order = self::searchOrder($environment, $chatId);
			$chat = $environment->chatRegistry()->wakeup($imChat);

			if ($order === null) { return; }

			static::link($environment, $chat, $order);
			static::release($chatId);
		}
		catch (\Throwable $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
		}
	}

	protected static function searchOrder(TradingEntity\SaleCrm\Container $environment, string $chatId) : ?Order
	{
		$row = Internals\WaitChatTable::getRow([
			'filter' => [ '=CHAT_ID' => $chatId ],
		]);

		if (!$row) { return null; }

		$order = $environment->orderRegistry()->load($row['ORDER_ID']);

		if ($order === null) { return null; }

		/** @var Order $order */
		Assert::typeOf($order, Order::class, 'order');

		return $order;
	}

	protected static function link(TradingEntity\SaleCrm\Container $environment, Chat $chat, Order $order) : void
	{
		$userId = $chat->user();
		$orderContacts = $order->contacts();
		$chatContacts = $chat->contacts();

		if ($userId !== null)
		{
			$order->fillUser($userId);
		}

		if (!$environment->contactRegistry()->anonymous($order->personType())->same($orderContacts))
		{
			$contactAdapter = $environment->contactRegistry()->contact($order->personType(), []);
			$contactAdapter->fillId($chatContacts);

			$chatContacts = $contactAdapter->merge($orderContacts, $order->userId());
		}

		$order->fillContacts($chatContacts);
		$order->linkActivity($chat->activity());
		$order->linkChat($chat);
		$order->save();
	}

	protected static function release(string $chatId) : void
	{
		$delete = new DB\Facade\BatchDelete(Internals\WaitChatTable::class);
		$delete->run([
			'filter' => [ '=CHAT_ID' => $chatId ],
		]);
	}
}
