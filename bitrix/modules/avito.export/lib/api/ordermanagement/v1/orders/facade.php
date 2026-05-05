<?php
namespace Avito\Export\Api\OrderManagement\V1\Orders;

use Avito\Export\Config;
use Bitrix\Main;
use Avito\Export\Concerns;
use Avito\Export\Trading;
use Avito\Export\Api;

class Facade
{
	use Concerns\HasLocale;

	public static function cachedById(Trading\Setup\Model $trading, int $externalId) : Api\OrderManagement\Model\Order
	{
		$order = null;

		$cache = static::cache();

		if ($cache !== null)
		{
			$cached = $cache->get($externalId);

			if (is_array($cached))
			{
				$order = new Api\OrderManagement\Model\Order((array)$cache[$externalId]);
			}
		}

		if ($order === null)
		{
			$order = static::getById($trading, $externalId);

			if ($cache !== null)
			{
				$cache->set($externalId, $order->rawData());
			}
		}

		return $order;
	}

	public static function releaseCache(int $externalId) : void
	{
		$cache = static::cache();
		if ($cache !== null)
		{
			$cache->offsetUnset($externalId);
		}
	}

	public static function getById(Trading\Setup\Model $trading, int $externalId) : Api\OrderManagement\Model\Order
	{
		$client = new Request();
		$client->token($trading->getSettings()->commonSettings()->token());
		$client->ids([ $externalId ]);

		/** @var Response $response */
		/** @var Api\OrderManagement\Model\Order $order */
		$response = $client->execute();
		$order = $response->orders()->offsetGet(0);

		if ($order === null)
		{
			throw new Main\SystemException(static::getLocale('ORDER_NOT_FOUND', [
				'#ID#' => $externalId,
			]));
		}

		$cache = static::cache();
		if ($cache !== null)
		{
			$cache->set($externalId, $order->rawData());
		}

		return $order;
	}

	protected static function cache() : ?Main\Data\LocalStorage\SessionLocalStorage
	{
		$application = Main\Application::getInstance();
		if (method_exists($application, 'getLocalSession'))
		{
			return $application->getLocalSession(Config::LANG_PREFIX . 'API_ORDER');
		}

		return null;
	}
}