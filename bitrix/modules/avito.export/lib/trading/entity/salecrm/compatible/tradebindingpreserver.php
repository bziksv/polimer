<?php
namespace Avito\Export\Trading\Entity\SaleCrm\Compatible;

use Avito\Export\Concerns;
use Avito\Export\Trading\Entity\Sale as TradingSale;
use Bitrix\Main;
use Bitrix\Sale;

class TradeBindingPreserver extends TradingSale\EventHandler
{
	use Concerns\HasOnceStatic;

	protected static $deleted = [];
	protected static $isBind = false;

	public function handlers() : array
	{
		return [
			[
				'module' => 'sale',
				'event' => 'OnBeforeCollectionDeleteItem',
			],
		];
	}

	/** @noinspection PhpUnused */
	public static function onBeforeCollectionDeleteItem(Main\Event $event) : void
	{
		if (!static::testRequest()) { return; }

		$binding = static::sanitizeEntity($event->getParameter('ENTITY'));

		if ($binding === null || !static::isOurBinding($binding)) { return; }

		$orderId = (int)$binding->getField('ORDER_ID');

		static::$deleted[$orderId] = [
			'TRADING_PLATFORM_ID' => (int)$binding->getField('TRADING_PLATFORM_ID'),
			'EXTERNAL_ORDER_ID' => (string)$binding->getField('EXTERNAL_ORDER_ID'),
			'PARAMS' => $binding->getField('PARAMS'),
		];

		static::bind();
	}

	public static function onBeforeSaleTradeBindingEntitySetFields(Main\Event $event) : ?Main\EventResult
	{
		$binding = static::sanitizeEntity($event->getParameter('ENTITY'));

		if ($binding === null) { return null; }

		/** @var Sale\TradeBindingCollection $bindingCollection */
		$bindingCollection = $binding->getCollection();
		$order = $bindingCollection !== null ? $bindingCollection->getOrder() : null;
		$orderId = $order !== null ? $order->getId() : null;

		if ($orderId === null || !isset(static::$deleted[$orderId])) { return null; }

		/** @noinspection PhpInternalEntityUsedInspection */
		$filled = array_filter($binding->getFields()->getValues());
		$new = (array)$event->getParameter('VALUES');
		$deleted = static::$deleted[$orderId];
		$platformId = $new['TRADING_PLATFORM_ID'] ?? $filled['TRADING_PLATFORM_ID'] ?? null;

		if ($platformId !== null && (int)$platformId !== $deleted['TRADING_PLATFORM_ID']) { return null; }

		$restore = array_diff_key($deleted, $filled, $new);

		if (empty($restore)) { return null; }

		return new Main\EventResult(Main\EventResult::SUCCESS, [
			'VALUES' => $new + $restore,
		]);
	}

	protected static function testRequest() : bool
	{
		return static::onceStatic('testRequest', static function() {
			$requestPage = Main\Application::getInstance()->getContext()->getRequest()->getRequestedPage();

			return (
				preg_match('#/crm\.(order|deal)\..+?/#', $requestPage)
				&& preg_match('#ajax\.php$#', $requestPage)
			);
		});
	}

	protected static function bind() : void
	{
		if (static::$isBind) { return; }

		static::$isBind = true;

		$eventManager = Main\EventManager::getInstance();

		$eventManager->addEventHandler('sale', 'OnBeforeSaleTradeBindingEntitySetFields', [static::class, 'OnBeforeSaleTradeBindingEntitySetFields']);
	}

	protected static function sanitizeEntity($entity) : ?Sale\TradeBindingEntity
	{
		return $entity instanceof Sale\TradeBindingEntity ? $entity : null;
	}

	protected static function isOurBinding(Sale\TradeBindingEntity $binding) : bool
	{
		$platform = $binding->getTradePlatform();

		return ($platform !== null && $platform->getCode() === TradingSale\Platform::CODE);
	}
}
