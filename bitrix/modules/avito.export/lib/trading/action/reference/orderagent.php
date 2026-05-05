<?php
namespace Avito\Export\Trading\Action\Reference;

use Avito\Export\Agent as AgentReference;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Psr;
use Avito\Export\Trading;
use Avito\Export\Exchange;
use Avito\Export\Api;
use Avito\Export\Data;
use Bitrix\Main;

abstract class OrderAgent extends AgentReference\Base
{
	protected const ERROR_LIMIT = 5;
	protected const ERROR_DELAY = 600;

	public static function start(int $exchangeId) : void
	{
		throw new Main\NotImplementedException();
	}

	protected static function switchOffStart(int $exchangeId) : void
	{
		static::unregister([
			'method' => 'start',
			'arguments' => [ $exchangeId ],
		]);
	}

	public static function process(int $exchangeId, int $page = 1, int $orderOffset = null, $stopOrder = null, int $errorCount = 0)
	{
		global $pPERIOD;

		try
		{
			$exchange = Exchange\Setup\Model::getById($exchangeId);
			$trading = $exchange->getTrading();
			$agentEnvironment = new AgentEnvironment();

			if ($trading === null || !$exchange->getUseTrading())
			{
				static::switchOffStart($exchangeId);

				return false;
			}

			$response = static::fetchOrders($trading, $page);

			$agentEnvironment->wake();
			[$processResult, $stopOrder] = static::processOrders($trading, $response->orders(), $orderOffset, $stopOrder);
			$agentEnvironment->restore();

			if ($processResult === false)
			{
				static::finalize($trading, $stopOrder);
				return false; // reached stop order
			}

			if ($processResult !== true)
			{
				return [ $exchangeId, $page, $processResult, $stopOrder ]; // same page with order offset
			}

			if ($response->hasMore())
			{
				return [ $exchangeId, $page + 1, null, $stopOrder ]; // next page
			}

			static::finalize($trading, $stopOrder);
			return false; // stop
		}
		catch (Main\ObjectNotFoundException|Main\NotSupportedException $exception) // trading not found or not supported
		{
			static::switchOffStart($exchangeId);

			return false; // stop
		}
		catch (\Throwable $exception) // failed query
		{
			if (isset($agentEnvironment))
			{
				$agentEnvironment->restore();
			}

			if (
				++$errorCount > static::ERROR_LIMIT
				|| ($exception instanceof Api\Exception\HttpError && $exception->badFormatted())
			)
			{
				static::logger($exchangeId)->error($exception, [
					'ENTITY_TYPE' => Glossary::ENTITY_AGENT,
				]);

				return false; // stop
			}

			$pPERIOD = static::ERROR_DELAY;

			static::logger($exchangeId)->warning($exception, [
				'ENTITY_TYPE' => Glossary::ENTITY_AGENT,
			]);

			return [ $exchangeId, $page, $orderOffset, $stopOrder, $errorCount ];  // repeat
		}
	}

	protected static function fetchOrders(Trading\Setup\Model $trading, int $page = null) : Api\OrderManagement\V1\Orders\Response
	{
		$client = new Api\OrderManagement\V1\Orders\Request();
		$client->token($trading->getSettings()->commonSettings()->token());
		$client->page($page ?? 1);

		return $client->execute();
	}

	protected static function processOrders(Trading\Setup\Model $trading, Api\OrderManagement\Model\Orders $orders, int $orderOffset = null, $stopOrder = null) : array
	{
		throw new Main\NotImplementedException();
	}

	protected static function finalize(Trading\Setup\Model $trading, $stopOrder) : void
	{
		// nothing by default
	}

	protected static function isOldOrder(Api\OrderManagement\Model\Order $order, int $days = 30) : bool
	{
		$compareDateTime = new Main\Type\DateTime();
		$compareDateTime->add('-' . $days . 'd');

		return (Data\DateTime::compare($compareDateTime, $order->updatedAt()) === 1);
	}

	protected static function logger(int $exchangeId) : Psr\Logger\LoggerInterface
	{
		$result = new Logger\Logger(Glossary::SERVICE_TRADING, $exchangeId);
		$result->allowTouch();

		return $result;
	}
}