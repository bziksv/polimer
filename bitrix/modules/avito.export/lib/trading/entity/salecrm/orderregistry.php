<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

use Bitrix\Sale;
use Bitrix\Crm;
use Avito\Export\Assert;
use Avito\Export\Trading\Entity\Sale as TradingSale;

class OrderRegistry extends TradingSale\OrderRegistry
{
	protected function makeOrder(TradingSale\Container $environment, Sale\Order $order, int $listenerState = null) : TradingSale\Order
	{
		/** @var Crm\Order\Order $order */
		Assert::typeOf($order, Crm\Order\Order::class, 'order');

		return new Order($environment, $order, $listenerState);
	}
}