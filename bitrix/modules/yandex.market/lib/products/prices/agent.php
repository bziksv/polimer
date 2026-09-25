<?php
namespace Yandex\Market\Products\Prices;

use Yandex\Market\Reference\Agent;

class AgentFacade extends Agent\Base
{
	public static function getDefaultParams()
	{
		return [
			'method' => 'process',
			'interval' => Config::getIntervalSeconds(),
			'sort' => 350,
		];
	}

	public static function sync()
	{
		if (Config::isConfigured())
		{
			self::register([
				'interval' => Config::getIntervalSeconds(),
			]);
		}
		else
		{
			self::unregister();
		}
	}

	public static function process()
	{
		if (!Config::isConfigured())
		{
			self::unregister();

			return false;
		}

		$needRepeat = false;
		$chunkSize = Sender::getChunkSize();

		do
		{
			$pending = Repository::fetchPending($chunkSize);

			if (empty($pending)) { break; }

			try
			{
				Sender::sendBatch($pending);
			}
			catch (\Throwable $exception)
			{
				if (class_exists('\CEventLog'))
				{
					\CEventLog::Add([
						'SEVERITY' => 'ERROR',
						'AUDIT_TYPE_ID' => 'YANDEX_MARKET_PRODUCTS_PRICES',
						'MODULE_ID' => Config::MODULE_ID,
						'DESCRIPTION' => $exception->getMessage(),
					]);
				}

				break;
			}

			$needRepeat = count($pending) >= $chunkSize;
		}
		while ($needRepeat);

		return true;
	}
}
