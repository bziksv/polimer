<?php
/** @noinspection PhpDeprecationInspection */
namespace Avito\Export\Push\Setup;

use Avito\Export\DB;
use Avito\Export\Feed;
use Avito\Export\Push;
use Avito\Export\Exchange;
use Avito\Export\Trading;
use Avito\Export\Watcher;
use Avito\Export\Concerns;
use Avito\Export\Admin\UserField\BooleanType;
use Bitrix\Main\ORM;
use Bitrix\Main;

/** @deprecated */
class RepositoryTable extends DB\Table
{
	use Concerns\HasLocale;
	use Watcher\Setup\HasRepositoryRefresh;
	use Watcher\Setup\HasRepositoryChanges;

	public static function migrateDeprecated() : bool
	{
		return true;
	}

	public static function getTableName() : string
	{
		return 'avito_export_push';
	}

	public static function getMap() : array
	{
		return array_merge(
			[
				new ORM\Fields\IntegerField('ID', [
					'autocomplete' => true,
					'primary' => true,
				]),
				new ORM\Fields\StringField('NAME', [
					'required' => true,
				]),

				new ORM\Fields\IntegerField('FEED_ID', [
					'required' => true,
				]),
				new ORM\Fields\Relations\Reference('FEED', Feed\Setup\RepositoryTable::class, ORM\Query\Join::on('this.FEED_ID', 'ref.ID')),

				new ORM\Fields\ArrayField('SETTINGS'),

				new ORM\Fields\DatetimeField('TIMESTAMP_X', [
					'required' => true,
				]),
			],
			static::getChangesMap(),
			static::getRefreshMap()
		);
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$newName = Exchange\Setup\RepositoryTable::getTableName();

		if (!$connection->isTableExists($newName))
		{
			static::createExchangeTable();
			static::migrateExchangeRows();
			static::dropSelf($connection);
		}
		else if (!static::isExchangeFilled())
		{
			static::migrateExchangeRows();
			static::dropSelf($connection);
		}
	}

	protected static function isExchangeFilled() : bool
	{
		$query = Exchange\Setup\RepositoryTable::getList([
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	protected static function createExchangeTable() : void
	{
		DB\Controller::createTables([
			Exchange\Setup\RepositoryTable::class,
		]);
	}

	public static function migrateExchangeRows() : void
	{
		$query = static::getList();
		$settingsBridge = new Exchange\Setup\SettingsBridge();
		$commonSettings = $settingsBridge->commonSettings();
		$tradingSettings = $settingsBridge->tradingSettings();

		while ($push = $query->fetchObject())
		{
			$tradingValues = static::exchangeTradingSettings($push->fillFeed(), $tradingSettings);

			$exchange = new Exchange\Setup\Model([
				'ID' => $push->getId(),
				'NAME' => $push->getName(),
				'FEED_ID' => $push->getFeedId(),
				'TIMESTAMP_X' => $push->getTimestampX(),
				'COMMON_SETTINGS' => array_intersect_key($push->getSettings(), $commonSettings->fields()),
				'USE_PUSH' => true,
				'PUSH_SETTINGS' => array_merge(array_diff_key($push->getSettings(), $commonSettings->fields()), [
					'AUTO_UPDATE' => $push->getAutoUpdate() ? BooleanType::VALUE_Y : BooleanType::VALUE_N,
					'REFRESH_PERIOD' => $push->getRefreshPeriod(),
					'REFRESH_TIME' => $push->getRefreshTime(),
				]),
				'USE_TRADING' => !empty($tradingValues),
				'TRADING_SETTINGS' => $tradingValues,
			]);

			$exchange->save();
			$exchange->activate();
		}
	}

	protected static function exchangeTradingSettings(?Feed\Setup\Model $feed, ?Trading\Setup\Settings $tradingSettings) : array
	{
		try
		{
			if ($feed === null || $tradingSettings === null) { return []; }

			$sites = $feed->allSites();
			$result = [];

			foreach ($tradingSettings->fields($sites) as $name => $field)
			{
				if (empty($field['SETTINGS']['DEFAULT_VALUE']))
				{
					$mandatory = $field['MANDATORY'] ?? 'N';

					if ($mandatory === 'Y')
					{
						$result = [];
						break;
					}

					continue;
				}

				$result[$name] = $field['SETTINGS']['DEFAULT_VALUE'];
			}
		}
		catch (Main\SystemException $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected static function dropSelf(Main\DB\Connection $connection) : void
	{
		$connection->dropTable(static::getTableName());
	}
}
