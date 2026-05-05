<?php

namespace Avito\Export\Watcher\Agent;

use Avito\Export\DB;
use Avito\Export\Glossary;
use Bitrix\Main;

class StateTable extends DB\Table
{
	public static function getTableName() : string
	{
		return 'avito_export_watcher_agent';
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
				'primary' => true,
				'required' => true
			]),
			new Main\ORM\Fields\StringField('METHOD', [
				'required' => true,
				'primary' => true,
				'validation' => [__CLASS__, 'validateMethod']
			]),
			new Main\ORM\Fields\StringField('STEP', [
				'validation' => [__CLASS__, 'validateStep']
			]),
			new Main\ORM\Fields\StringField('OFFSET', [
				'validation' => [__CLASS__, 'validateOffset']
			]),
			new Main\ORM\Fields\DatetimeField('INIT_TIME'),
		];
	}

	public static function validateMethod() : array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 15),
		];
	}

	public static function validateStep() : array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 15)
		];
	}

	public static function validateOffset() : array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 64)
		];
	}

	public static function migrateTableName() : string
	{
		return 'avito_export_feed_agent';
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
				'ALTER TABLE %s ADD PRIMARY KEY(%s, %s, %s)',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SETUP_TYPE'),
				$sqlHelper->quote('SETUP_ID'),
				$sqlHelper->quote('METHOD')
			));
		}
	}
}