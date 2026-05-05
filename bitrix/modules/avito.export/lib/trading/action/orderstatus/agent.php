<?php
namespace Avito\Export\Trading\Action\OrderStatus;

use Avito\Export\Config;
use Avito\Export\Data;
use Avito\Export\Glossary;
use Avito\Export\Psr;
use Avito\Export\Api;
use Avito\Export\Watcher;
use Avito\Export\Trading;

class Agent extends Trading\Action\Reference\OrderAgent
{
	protected const ERROR_LIMIT = 5;
	protected const ERROR_DELAY = 600;

	public static function getDefaultParams() : array
	{
		return [
			'interval' => 3600,
		];
	}

	public static function start(int $exchangeId) : void
	{
		static::register([
			'method' => 'process',
			'interval' => 5,
			'arguments' => [
				$exchangeId,
				1,
				null,
				static::stopLimit($exchangeId)
			],
		]);
	}

	protected static function processOrders(Trading\Setup\Model $trading, Api\OrderManagement\Model\Orders $orders, int $orderOffset = null, $stopOrder = null) : array
	{
		$limitResource = new Watcher\Engine\LimitResource();
		$offsetFound = ($orderOffset === null);
		$stopOrder = static::sanitizeStopLimit($stopOrder);
		$hasStop = false;
		$allUnnecessary = true;

		/** @var Api\OrderManagement\Model\Order $order */
		foreach ($orders as $order)
		{
			$isUnnecessary = static::isUnnecessaryOrder($order);

			if ($stopOrder['next'] === null) { $stopOrder['next'] = $order->id(); }
			if ($stopOrder['now'] === $order->id()) { $hasStop = true; }

			if (!$isUnnecessary) { $allUnnecessary = false; }

			if ($orderOffset === $order->id())
			{
				$offsetFound = true;
				continue;
			}

			if (!$offsetFound) { continue; }

			if (!$isUnnecessary)
			{
				if (!static::isFinalStatus($order))
				{
					$stopOrder['next'] = $order->id();
				}

				static::callAction($trading, $order);
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$limitResource->tick();

			if ($limitResource->isExpired())
			{
				return [$order->id(), $stopOrder];
			}
		}

		if ($hasStop)
		{
			return [false, $stopOrder];
		}

		if ($stopOrder['now'] === null && $allUnnecessary)
		{
			return [false, $stopOrder];
		}

		return [true, $stopOrder];
	}

	protected static function sanitizeStopLimit($stopOrder = null) : array
	{
		if (is_array($stopOrder)) { return $stopOrder; }

		return [
			'now' => $stopOrder,
			'next' => null,
		];
	}

	protected static function isUnnecessaryOrder(Api\OrderManagement\Model\Order $order) : bool
	{
		return (static::isFinalStatus($order) && static::isOldOrder($order));
	}

	protected static function stopLimit(int $exchangeId) : ?int
	{
		return Data\Number::cast(Config::getOption('trading_order_status_last_' . $exchangeId));
	}

	protected static function finalize(Trading\Setup\Model $trading, $stopOrder) : void
	{
		if (!isset($stopOrder['next']) || $stopOrder['next'] === $stopOrder['now']) { return; }

		Config::setOption('trading_order_status_last_' . $trading->getId(), $stopOrder['next']);
	}

	protected static function callAction(Trading\Setup\Model $trading, Api\OrderManagement\Model\Order $order) : void
	{
		try
		{
			$action = new Action($trading, new Command($order));
			$action->process();
		}
		catch (\Throwable $exception)
		{
			static::logger($trading->getId())->error($exception, [
				'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
				'ENTITY_ID' => $order->number(),
			]);
		}
	}

	protected static function isFinalStatus(Api\OrderManagement\Model\Order $order) : bool
	{
		return in_array($order->status(), [
			Trading\Service\Status::STATUS_CLOSED,
			Trading\Service\Status::STATUS_CANCELED
		], true);
	}
}