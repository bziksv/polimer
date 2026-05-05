<?php
namespace Avito\Export\Watcher\Agent;

use Bitrix\Main;
use Avito\Export\DB;
use Avito\Export\Glossary;

class ChangesTable extends DB\Table
{
	public static function getTableName(): string
	{
		return 'avito_export_watcher_changes';
	}

	public static function getTableIndexes() : array
	{
		return [
			0 => ['TIMESTAMP_X'],
		];
	}

	public static function getMap() : array
	{
		return [
			new Main\ORM\Fields\EnumField('SETUP_TYPE', [
				'required' => true,
				'primary' => true,
				'values' => [
					Glossary::SERVICE_FEED,
					Glossary::SERVICE_PUSH,
				],
			]),
			new Main\ORM\Fields\IntegerField('SETUP_ID', [
				'required' => true,
				'primary' => true,
				'size' => 20,
			]),
			new Main\ORM\Fields\StringField('ENTITY_TYPE', [
				'required' => true,
				'primary' => true,
				'validation' => [static::class, 'getValidationEntityType'],
			]),
			new Main\ORM\Fields\IntegerField('ENTITY_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function getValidationEntityType() : array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 20),
		];
	}

	public static function migrateTableName() : string
	{
		return 'avito_export_feed_changes';
	}

	/** @noinspection DuplicatedCode */
	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$existsFields = $connection->getTableFields($tableName);
		$sqlHelper = $connection->getSqlHelper();
		$needResetPrimary = false;

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
				'ALTER TABLE %s ADD PRIMARY KEY(%s, %s, %s, %s)',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->quote('SETUP_ID'),
				$sqlHelper->quote('ENTITY_TYPE'),
				$sqlHelper->quote('ENTITY_ID')
			));
		}
	}
}