<?php
namespace Avito\Export\Logger;

use Avito\Export\DB;
use Avito\Export\Psr;
use Avito\Export\Admin\UserField;
use Avito\Export\Feed;
use Avito\Export\Exchange;
use Avito\Export\Concerns;
use Avito\Export\Glossary;
use Bitrix\Main;

class Table extends DB\Table
{
	use Concerns\HasLocale;

	public static function getTableIndexes() : array
	{
		return [
			0 => ['ENTITY_TYPE', 'ENTITY_ID'],
			1 => ['TIMESTAMP_X'],
			2 => ['REGION_ID'],
		];
	}

	public static function getTableName() : string
	{
		return 'avito_export_log';
	}

	public static function getMap() : array
	{
		return [
			new Main\ORM\Fields\EnumField('SETUP_TYPE', [
				'primary' => true,
				'required' => true,
				'values' => [
					Glossary::SERVICE_FEED,
					Glossary::SERVICE_PUSH,
					Glossary::SERVICE_TRADING,
					Glossary::SERVICE_CHAT,
				],
			]),
			new Main\ORM\Fields\IntegerField('SETUP_ID', [
				'primary' => true,
				'required' => true,
			]),
			new Main\ORM\Fields\StringField('SIGN', [
				'primary' => true,
				'validation' => [static::class, 'getValidationSign'],
			]),
			new Main\ORM\Fields\EnumField('ENTITY_TYPE', [
				'required' => true,
				'values' => [
					Glossary::ENTITY_OFFER,
					Glossary::ENTITY_STOCKS,
					Glossary::ENTITY_PRICE,
					Glossary::ENTITY_ORDER,
					Glossary::ENTITY_AGENT,
					Glossary::ENTITY_TOKEN,
					Glossary::ENTITY_MESSAGE,
				],
			]),
			new Main\ORM\Fields\StringField('ENTITY_ID', [
				'required' => true,
				'default_value' => 0,
			]),
			new Main\ORM\Fields\IntegerField('REGION_ID', [
				'default_value' => 0,
			]),
			new Main\ORM\Fields\EnumField('LEVEL', [
				'values' => [
					Psr\Logger\LogLevel::ALERT,
					Psr\Logger\LogLevel::CRITICAL,
					Psr\Logger\LogLevel::DEBUG,
					Psr\Logger\LogLevel::EMERGENCY,
					Psr\Logger\LogLevel::ERROR,
					Psr\Logger\LogLevel::INFO,
					Psr\Logger\LogLevel::NOTICE,
					Psr\Logger\LogLevel::WARNING,
				],
				'required' => true,
			]),
			new Main\ORM\Fields\TextField('MESSAGE'),
			new Main\ORM\Fields\ArrayField('CONTEXT'),
			new Main\ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function getValidationSign() : array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 33), // md5
		];
	}

	public static function getMapDescription():array
	{
		self::includeLocale();

		$result = parent::getMapDescription();
		$result['SETUP_ID'] = static::extendSetupIdDescription($result['SETUP_ID']);
		$result['LEVEL'] = static::extendLevelDescription($result['LEVEL']);

		return $result;
	}

	protected static function extendSetupIdDescription(array $field) : array
	{
		$field['USER_TYPE'] = UserField\Registry::description('enumeration');
		$field['USER_TYPE']['SETTINGS'] = [
			'UNIQUE' => [ 'SETUP_TYPE' ],
		];
		$field['VALUES'] = [];
		$tables = [
			Glossary::SERVICE_FEED => Feed\Setup\RepositoryTable::class,
			Glossary::SERVICE_PUSH => Exchange\Setup\RepositoryTable::class,
			Glossary::SERVICE_TRADING => Exchange\Setup\RepositoryTable::class,
			Glossary::SERVICE_CHAT => Exchange\Setup\RepositoryTable::class,
		];

		/** @var Main\ORM\Data\DataManager $table */
		foreach ($tables as $group => $table)
		{
			$query = $table::getList([
				'select' => [ 'ID', 'NAME' ],
			]);

			while ($row = $query->fetch())
			{
				$field['VALUES'][] = [
					'ID' => $group . ':' . $row['ID'],
					'VALUE' => sprintf('[%s] %s (%s)', $row['ID'], $row['NAME'], self::getLocale('SETUP_' . mb_strtoupper($group))),
					'GROUP' => self::getFieldEnumTitle('SETUP_TYPE', $group),
				];
			}
		}

		return $field;
	}

	protected static function extendLevelDescription(array $field) : array
	{
		$field['USER_TYPE']['CLASS_NAME'] = UserField\LogType::class;

		if (!empty($field['VALUES']))
		{
			$allowedTypes = [
				Psr\Logger\LogLevel::ERROR => true,
				Psr\Logger\LogLevel::CRITICAL => true,
				Psr\Logger\LogLevel::INFO => true,
				Psr\Logger\LogLevel::WARNING => true,
			];

			foreach ($field['VALUES'] as $optionKey => $option)
			{
				if (!isset($allowedTypes[$option['ID']]))
				{
					unset($field['VALUES'][$optionKey]);
				}
			}
		}

		return $field;
	}

	public static function migrateTableName() : string
	{
		return 'avito_export_feed_log';
	}

	/** @noinspection DuplicatedCode */
	public static function migrate(Main\DB\Connection $connection) : void
	{
		static::migrateFeedLog($connection);
		static::migrateEntityType($connection);
	}

	protected static function migrateFeedLog(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$existsFields = $connection->getTableFields($tableName);
		$sqlHelper = $connection->getSqlHelper();
		$needResetPrimary = false;

		if (isset($existsFields['SETUP_TYPE']))
		{
			$setupTypeField = static::getEntity()->getField('SETUP_TYPE');

			$needResetPrimary = true;
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s MODIFY %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->getColumnTypeByField($setupTypeField)
			));
		}

		if (!isset($existsFields['REGION_ID']))
		{
			$regionIdField = static::getEntity()->getField('REGION_ID');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('REGION_ID'),
				$sqlHelper->getColumnTypeByField($regionIdField)
			));

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=0',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('REGION_ID')
			));
		}

		if (!isset($existsFields['SETUP_TYPE']))
		{
			$needResetPrimary = true;
			$setupTypeField = static::getEntity()->getField('SETUP_TYPE');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->getColumnTypeByField($setupTypeField)
			));

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s="%s"',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->forSql(Glossary::SERVICE_FEED)
			));
		}

		if (isset($existsFields['FEED_ID']) && !isset($existsFields['SETUP_ID']))
		{
			$setupIdField = static::getEntity()->getField('SETUP_ID');

			$needResetPrimary = true;
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s CHANGE %s %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('FEED_ID'),
				$sqlHelper->quote('SETUP_ID'),
				$sqlHelper->getColumnTypeByField($setupIdField)
			));
		}

		if ($needResetPrimary)
		{
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s DROP PRIMARY KEY',
				$sqlHelper->quote($tableName)
			));

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD PRIMARY KEY(%s, %s, %s)',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->quote('SETUP_ID'),
				$sqlHelper->quote('SIGN')
			));
		}
	}

	protected static function migrateEntityType(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$queryColumns = $connection->query(sprintf('SHOW COLUMNS FROM %s', $sqlHelper->quote($tableName)));
		$columnTypes = array_column($queryColumns->fetchAll(), 'Type', 'Field');

		if (!isset($columnTypes['ENTITY_TYPE'])) { return; }

		$stored = $columnTypes['ENTITY_TYPE'];
		$storedLength = static::columnTypeLength($stored);
		$required = $sqlHelper->getColumnTypeByField(static::getEntity()->getField('ENTITY_TYPE'));
		$requiredLength = static::columnTypeLength($required);

		if ($requiredLength > $storedLength)
		{
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s MODIFY %s %s NOT NULL',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('ENTITY_TYPE'),
				$required
			));

			$batchUpdate = new DB\Facade\BatchUpdate(static::class);
			$batchUpdate->run([
				'filter' => [
					'=SETUP_TYPE' => Glossary::SERVICE_PUSH,
					'=ENTITY_TYPE' => Glossary::ENTITY_OFFER,
				],
			], [
				'ENTITY_TYPE' => Glossary::ENTITY_STOCKS,
			]);
		}
	}

	protected static function columnTypeLength(string $type) : ?int
	{
		$result = null;

		if (preg_match('/\((\d+)/', $type, $matches))
		{
			$result = (int)$matches[1];
		}

		return $result;
	}
}
