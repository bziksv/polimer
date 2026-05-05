<?php
namespace Avito\Export\Trading\Action\OrderAccept;

use Avito\Export\Config;
use Avito\Export\Glossary;
use Avito\Export\Psr;
use Avito\Export\Trading;
use Avito\Export\Api;
use Avito\Export\Watcher;
use Avito\Export\Data;
use Bitrix\Main;

class Agent extends Trading\Action\Reference\OrderAgent
{
	protected const ERROR_LIMIT = 5;
	protected const ERROR_DELAY = 600;

	public static function getDefaultParams() : array
	{
		return [
			'interval' => 600,
		];
	}

	/** @noinspection ProperNullCoalescingOperatorUsageInspection */
	public static function start(int $exchangeId) : void
	{
		static::register([
			'method' => 'process',
			'interval' => 5,
			'arguments' => [
				$exchangeId,
				1,
				null,
				static::stopLimitCreatedAt($exchangeId) ?? static::stopLimitCompatible($exchangeId),
			],
		]);
	}

	protected static function processOrders(Trading\Setup\Model $trading, Api\OrderManagement\Model\Orders $orders, int $orderOffset = null, $stopOrder = null) : array
	{
		$limitResource = new Watcher\Engine\LimitResource();
		$offsetFound = ($orderOffset === null);
		[$stopOrder, $stopCreated] = static::parseStopArgument($stopOrder);

		/** @var Api\OrderManagement\Model\Order $order */
		foreach ($orders as $order)
		{
			if (!static::checkStopLimit($order, $stopOrder, $stopCreated)) { return [ false, $stopOrder ]; }

			if ($orderOffset === $order->id())
			{
				$offsetFound = true;
				continue;
			}

			if (!$offsetFound) { continue; }

			static::updateStopLimit($trading->getId(), $order);

			if (!static::isUnnecessaryOrder($order))
			{
				static::callAction($trading, $order);
			}

			/** @noinspection DisconnectedForeachInstructionInspection */
			$limitResource->tick();

			if ($limitResource->isExpired())
			{
				return [ $order->id(), $stopOrder ];
			}
		}

		return [ true, $stopOrder ];
	}

	protected static function parseStopArgument($stopOrder) : array
	{
		if ($stopOrder === null || $stopOrder === '')
		{
			return [ null, null ];
		}

		if (is_numeric($stopOrder))
		{
			return [ (int)$stopOrder, null ];
		}

		return [ null, Data\DateTime::cast($stopOrder) ];
	}

	protected static function isUnnecessaryOrder(Api\OrderManagement\Model\Order $order) : bool
	{
		return (
			$order->status() === Trading\Service\Status::STATUS_CLOSED
			|| (
				$order->status() === Trading\Service\Status::STATUS_CANCELED
				&& static::isOldOrder($order, 7)
			)
		);
	}

	protected static function callAction(Trading\Setup\Model $trading, Api\OrderManagement\Model\Order $order) : void
	{
		try
		{
			static::callProcedure($trading, $order, 'order/accept');
			static::callProcedure($trading, $order, 'order/status');
		}
		catch (\Throwable $exception)
		{
			static::logger($trading->getId())->error($exception, [
				'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
				'ENTITY_ID' => $order->number(),
			]);
		}
	}

	protected static function callProcedure(Trading\Setup\Model $trading, Api\OrderManagement\Model\Order $order, string $path) : void
	{
		$procedure = new Trading\Action\Procedure($trading, $path, [ 'order' => $order ]);
		$procedure->run();
	}

	protected static function stopLimitCompatible(int $exchangeId) : ?int
	{
		return Data\Number::cast(Config::getOption('trading_order_accept_last_' . $exchangeId));
	}

	protected static function stopLimitCreatedAt(int $exchangeId) : ?string
	{
		return Config::getOption('trading_order_accept_date_' . $exchangeId, null);
	}

	protected static function checkStopLimit(Api\OrderManagement\Model\Order $order, int $stopOrder = null, Main\Type\DateTime $stopCreatedAt = null) : bool
	{
		if ($stopCreatedAt !== null)
		{
			return Data\DateTime::compare($order->createdAt(), $stopCreatedAt) === 1;
		}

		if ($stopOrder !== null)
		{
			return $order->id() > $stopOrder;
		}

		return true;
	}

	protected static function updateStopLimit(int $exchangeId, Api\OrderManagement\Model\Order $order) : void
	{
		$optionLimitCreatedAt = Data\DateTime::cast(static::stopLimitCreatedAt($exchangeId));
		$createdAtOffset = Data\DateTime::max(
			$order->createdAt()->add('-PT1H'), // gap for order create delay
			(new Main\Type\DateTime())->add('-P1D') // gap for incorrect timezone and order create delay
		);

		if ($optionLimitCreatedAt === null || Data\DateTime::compare($createdAtOffset, $optionLimitCreatedAt) === 1)
		{
			Config::setOption('trading_order_accept_date_' . $exchangeId, Data\DateTime::stringify($createdAtOffset));
		}
	}
}