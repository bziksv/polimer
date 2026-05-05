<?php
namespace Avito\Export\Feed\Engine\Steps\Offer;

use Avito\Export;
use Bitrix\Main;

class Table extends Export\DB\Table
{
	public const STATUS_FAIL = 0;
	public const STATUS_OK = 1;
	public const STATUS_WAIT = 2;

	public static function getTableName() : string
	{
		return 'avito_export_feed_offer';
	}

	public static function getTableIndexes() : array
	{
		return [
		    0 => [ 'IBLOCK_ID' ],
			1 => [ 'TIMESTAMP_X' ],
			2 => [ 'HASH' ],
			3 => [ 'PRIMARY' ],
			4 => [ 'PARENT_ID' ],
		];
	}

	public static function getMap(): array
	{
		return [
			new Main\ORM\Fields\IntegerField('FEED_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\ORM\Fields\IntegerField('ELEMENT_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\ORM\Fields\IntegerField('REGION_ID', [
				'required' => true,
				'primary' => true,
			]),
			new Main\ORM\Fields\StringField('PRIMARY', [
				'required' => true,
				'validation' => [static::class, 'getValidationPrimary'],
			]),
			new Main\ORM\Fields\StringField('HASH', [
				'size' => 33,
				'validation' => [static::class, 'getValidationHash'],
			]),
			new Main\ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true
			]),
			new Main\ORM\Fields\IntegerField('IBLOCK_ID'),
			new Main\ORM\Fields\IntegerField('PARENT_ID'),
			new Main\ORM\Fields\EnumField('STATUS', [
				'required' => true,
				'values' => [
					self::STATUS_FAIL,
					self::STATUS_OK,
					self::STATUS_WAIT,
				],
			]),
			new Main\ORM\Fields\IntegerField('MERGED_ID'),
		];
	}

	public static function getValidationPrimary(): array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 100)
		];
	}

	public static function getValidationHash(): array
	{
		return [
			new Main\ORM\Fields\Validators\LengthValidator(null, 33)
		];
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$tableFields = $connection->getTableFields($tableName);

		if (!isset($tableFields['REGION_ID']))
		{
			$regionField = static::getEntity()->getField('REGION_ID');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('REGION_ID'),
				$sqlHelper->getColumnTypeByField($regionField)
			));

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=0',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('REGION_ID')
			));

			$connection->queryExecute(sprintf('ALTER TABLE %s DROP PRIMARY KEY', $sqlHelper->quote($tableName)));
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD PRIMARY KEY(%s, %s, %s)',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('FEED_ID'),
				$sqlHelper->quote('ELEMENT_ID'),
				$sqlHelper->quote('REGION_ID')
			));
		}

		if (!isset($tableFields['PRIMARY']))
		{
			$primaryField = static::getEntity()->getField('PRIMARY');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('PRIMARY'),
				$sqlHelper->getColumnTypeByField($primaryField)
			));
		}

		if (!isset($tableFields['STATUS']))
		{
			$statusField = static::getEntity()->getField('STATUS');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('STATUS'),
				$sqlHelper->getColumnTypeByField($statusField)
			));

			$connection->queryExecute(sprintf(
				'UPDATE %s SET %s=1',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('STATUS')
			));
		}

		if (!isset($tableFields['MERGED_ID']))
		{
			$mergedIdField = static::getEntity()->getField('MERGED_ID');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('MERGED_ID'),
				$sqlHelper->getColumnTypeByField($mergedIdField)
			));
		}
	}
}
