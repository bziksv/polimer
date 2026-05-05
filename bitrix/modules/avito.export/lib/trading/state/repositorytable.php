<?php
namespace Avito\Export\Trading\State;

use Avito\Export\DB;
use Bitrix\Main;
use Bitrix\Main\ORM;

class RepositoryTable extends DB\Table
{
	public static function getCollectionClass() : string
	{
		return Collection::class;
	}

	public static function getObjectClass() : string
	{
		return Model::class;
	}

	public static function getTableName() : string
	{
		return 'avito_export_trading_state';
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\StringField('ORDER_ID', [
				'primary' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 20) ];
				},
			]),
			new ORM\Fields\StringField('NAME', [
				'primary' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 30) ];
				},
			]),
			new ORM\Fields\TextField('VALUE'),
			new ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$sqlHelper = $connection->getSqlHelper();
		$valueTypeField = static::getEntity()->getField('VALUE');

		$connection->queryExecute(sprintf(
			'ALTER TABLE %s MODIFY COLUMN %s %s',
			$sqlHelper->quote($tableName),
			$sqlHelper->quote('VALUE'),
			$sqlHelper->getColumnTypeByField($valueTypeField)
		));
	}
}