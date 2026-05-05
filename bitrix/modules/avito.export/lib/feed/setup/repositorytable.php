<?php
namespace Avito\Export\Feed\Setup;

use Avito\Export;
use Avito\Export\DB;
use Avito\Export\Watcher;
use Avito\Export\Concerns;
use Avito\Export\Admin\UserField;
use Bitrix\Main;

class RepositoryTable extends DB\Table
{
	use Concerns\HasLocale;
	use Watcher\Setup\HasRepositoryRefresh;
	use Watcher\Setup\HasRepositoryChanges;

	public static function getObjectClass() : string
	{
		return Model::class;
	}

	public static function getTableName() : string
	{
		return 'avito_export_feeds';
	}

	public static function getMap() : array
	{
		return array_merge(
			[
				new Main\ORM\Fields\IntegerField('ID', [
					'autocomplete' => true,
					'primary' => true,
				]),
				new Main\ORM\Fields\StringField('NAME', [
					'required' => true,
				]),
				new Main\ORM\Fields\DatetimeField('TIMESTAMP_X', [
					'required' => true,
				]),
				(new Main\ORM\Fields\ArrayField('SITE', [
					'required' => true,
					'fetch_data_modification' => function () {
						return [
							function ($value, $query, $row, $alias) {
								if (
									!is_string($value)
									|| $value === ''
									|| mb_strpos($value, 'a:') === 0 // fast check is serialized array
									|| unserialize($value, [ 'allowed_classes' => false ]) !== false
								)
								{
									return $value;
								}

								$aliasPrefix = mb_substr($alias, 0, mb_strlen($alias) - mb_strlen('SITE'));
								$iblockAlias = $aliasPrefix . 'IBLOCK';
								$iblockIds = is_string($row[$iblockAlias])
									? unserialize($row[$iblockAlias], [ 'allowed_classes' => false ])
									: $row[$iblockAlias];

								if (!is_array($iblockIds))
								{
									return $value;
								}

								return serialize(array_fill_keys($iblockIds, $value));
							},
						];
					},
				]))
					->configureSerializationPhp(),

				new Main\ORM\Fields\BooleanField('HTTPS', [
					'values' => ['0', '1'],
				]),

				(new Main\ORM\Fields\ArrayField('IBLOCK'))
					->configureSerializationPhp(),

				new Main\ORM\Fields\IntegerField('REGION'),

				new Main\ORM\Fields\StringField('FILE_NAME', [
					'required' => true,
					'format' => '/^[0-9A-Za-z-_.]+$/',
					'size' => 20,
				]),

				(new Main\ORM\Fields\ArrayField('FILTER'))
					->configureSerializationPhp()
					->addFetchDataModifier(static function($value) {
						// convert one filter to filter collection
						if (!is_array($value)) { return $value; }

						foreach ($value as $iblockId => $filterCollection)
						{
							if (!is_array($filterCollection)) { continue; }

							$firstFilter = reset($filterCollection);

							if (isset($firstFilter['FIELD'])) // is condition
							{
								$value[$iblockId] = [ $filterCollection ];
							}
						}

						return $value;
					}),

				(new Main\ORM\Fields\ArrayField('CATEGORY_LIMIT'))
					->configureSerializationPhp(),

				(new Main\ORM\Fields\ArrayField('TAGS', [
					'fetch_data_modification' => function () {
						return [
							// migrate from php serializer to json (save emoji to db)
							function ($value) {
								if (
									!is_string($value)
									|| $value === ''
									|| mb_strpos($value, 'a:') !== 0 // fast check is serialized array
								)
								{
									return $value;
								}

								$data = unserialize($value, [ 'allowed_classes' => false ]);

								if ($data === false) { return $value; }

								return Main\Web\Json::encode($data);
							},
						];
					},
				]))
					->configureSerializationJson(),
			],
			static::getChangesMap(),
			static::getRefreshMap()
		);
	}

	public static function getMapDescription(): array
	{
		self::includeLocale();

		$result = parent::getMapDescription();

		$result['IBLOCK'] = static::extendIblockDescription($result['IBLOCK']);
		$result['REGION'] = static::extendRegionDescription($result['REGION']);
		$result['REFRESH_PERIOD'] = static::extendRefreshPeriodDescription($result['REFRESH_PERIOD']);
		$result['REFRESH_TIME'] = static::extendRefreshTimeDescription($result['REFRESH_TIME']);
		$result['SITE'] = static::extendSiteDescription($result['SITE']);

		return $result;
	}

	protected static function extendIblockDescription(array $field) : array
	{
		$field['MANDATORY'] = 'Y';
		$field['MULTIPLE'] = 'Y';
		$field['USER_TYPE'] = UserField\Registry::description('iblock');
		$field['SETTINGS'] = [
			'VALUES_CATALOG' => UserField\IblockType::VALUES_CATALOG,
		];

		return $field;
	}

	protected static function extendRegionDescription(array $field) : array
	{
		$field['USER_TYPE'] = UserField\Registry::description('iblock');
		$field['SETTINGS'] = [
			'VALUES_CATALOG' => UserField\IblockType::VALUES_SIMPLE,
		];

		return $field;
	}

	protected static function extendSiteDescription(array $field) : array
	{
		$field['MULTIPLE'] = 'Y';
		$field['USER_TYPE'] = UserField\Registry::description('enumeration');
		$field['VALUES'] = static::getSiteEnum();

		return $field;
	}

	protected static function getSiteEnum() : array
	{
		$result = [];
		$query = Main\SiteTable::getList([
			'select' => [ 'LID', 'SERVER_NAME', 'NAME' ],
			'filter' => [ '=ACTIVE' => 'Y' ],
		]);

		while ($site = $query->fetch())
		{
			$result[] = [
				'ID' => $site['LID'],
				'VALUE' => sprintf('[%s] %s', $site['LID'], $site['SERVER_NAME'] ?: $site['NAME']),
			];
		}

		return $result;
	}

	public static function onDelete(Main\ORM\Event $event) : void
	{
		$primary = $event->getParameter('primary');

		if (empty($primary['ID'])) { return; }

		static::deleteFeedLog($primary['ID']);
		static::deleteOfferResult($primary['ID']);
	}

	protected static function deleteFeedLog(int $feedId) : void
	{
		$batch = new DB\Facade\BatchDelete(Export\Logger\Table::class);

		$batch->run([
			'filter' => [
				'=SETUP_TYPE' => Export\Glossary::SERVICE_FEED,
				'=SETUP_ID' => $feedId,
			],
		]);
	}

	protected static function deleteOfferResult(int $feedId) : void
	{
		$batch = new DB\Facade\BatchDelete(Export\Feed\Engine\Steps\Offer\Table::class);

		$batch->run([
			'filter' => [ '=FEED_ID' => $feedId ],
		]);
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$tableFields = $connection->getTableFields($tableName);

		$fields = [
			[
				'NAME' => 'REFRESH_TIME',
				'TYPE' => ' varchar(5) NOT NULL',
			],
			[
				'NAME' => 'REGION',
				'TYPE' => ' int(11) NOT NULL',
			],
			[
				'NAME' => 'CATEGORY_LIMIT',
				'TYPE' => ' text DEFAULT NULL',
				'AFTER' => 'FILTER'
			],
		];

		foreach ($fields as $field)
		{
			if (isset($tableFields[$field['NAME']])) { continue; }

			$connection->queryExecute(
				'ALTER TABLE ' . $sqlHelper->quote($tableName)
				. ' ADD COLUMN ' . $sqlHelper->quote($field['NAME']) . $field['TYPE']
				. (isset($field['AFTER']) && $field['AFTER'] ? ' AFTER ' . $field['AFTER'] : '')
			);
		}
	}
}