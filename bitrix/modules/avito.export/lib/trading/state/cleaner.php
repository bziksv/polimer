<?php
namespace Avito\Export\Trading\State;

use Avito\Export\Agent;
use Avito\Export\Config;
use Avito\Export\DB;
use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Exchange;
use Avito\Export\Trading;
use Avito\Export\Utils;
use Bitrix\Main;

class Cleaner extends Agent\Base
{
	public static function install() : void
	{
		$interval = 86400;

		static::register([
			'method' => 'run',
			'interval' => $interval,
			'next_exec' => Utils\Agent::nextExec(
				$interval,
				random_int(0, 8),
				random_int(0, 59)
			),
		]);
	}

	public static function uninstall() : void
	{
		static::unregister([ 'method' => 'run' ]);
	}

	public static function run() : bool
	{
		try
		{
			static::clean([
				RepositoryTable::class => true,
				Trading\Entity\SaleCrm\Internals\WaitChatTable::class => true,
				Logger\Table::class => [
					'=SETUP_TYPE' => [
                        Glossary::SERVICE_TRADING,
                        Glossary::SERVICE_CHAT,
                    ],
				],
			]);
		}
		catch (Main\SystemException $exception)
		{
			$setupId = static::setupId();

			if ($setupId === null) { return false; }

			static::logException($setupId, $exception);
		}

		return true;
	}

	protected static function clean(array $tables) : void
	{
		$expireDate = static::expireDate();

		/** @var class-string<\Bitrix\Main\ORM\Data\DataManager> $table */
		foreach ($tables as $table => $filter)
		{
			$loopCount = 0;
			$query = [
				'filter' =>
					($filter !== true ? $filter : [])
					+ [ '<TIMESTAMP_X' => $expireDate ],
			];

			while ($table::getRow($query))
			{
				$batch = new DB\Facade\BatchDelete($table);
				$batch->run($query + [ 'limit' => 10000 ]);

				if (++$loopCount > 1000)
				{
					throw new Main\SystemException('infinite loop on clean tables');
				}
			}
		}
	}

	protected static function expireDate() : Main\Type\DateTime
	{
		$days = max(1, (int)Config::getOption('trading_expire_days', 60));
		$date = new Main\Type\DateTime();
		$date->add(sprintf('-P%sD', $days));

		return $date;
	}

	protected static function setupId() : ?int
	{
		$result = null;

		$query = Exchange\Setup\RepositoryTable::getList([
			'select' => [ 'ID' ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	protected static function logException(int $setupId, \Throwable $exception) : void
	{
		$logger = new Logger\Logger(Glossary::SERVICE_TRADING, $setupId);
		$logger->error($exception);
	}
}

