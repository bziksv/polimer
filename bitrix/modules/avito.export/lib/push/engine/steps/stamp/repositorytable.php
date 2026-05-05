<?php
namespace Avito\Export\Push\Engine\Steps\Stamp;

use Avito\Export\DB;
use Avito\Export\Glossary;
use Avito\Export\Push\Engine\Steps\PrimaryMap;
use Bitrix\Main\ORM;
use Bitrix\Main;

class RepositoryTable extends DB\Table
{
	public const STATUS_WAIT = 'N';
	public const STATUS_DELETE = 'D';
	public const STATUS_READY = 'Y';
	public const STATUS_FAILED = 'F';
	public const STATUS_UNPUBLISHED = 'U';

	public const VALUE_NULL = 'N';

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
		return 'avito_export_push_stamp';
	}

	public static function getTableIndexes() : array
	{
		return [
			0 => [ 'STATUS' ],
			1 => [ 'PRIMARY' ],
		];
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\IntegerField('PUSH_ID', [
				'primary' => true,
			]),
			new ORM\Fields\IntegerField('ELEMENT_ID', [
				'primary' => true,
			]),
			new ORM\Fields\IntegerField('REGION_ID', [
				'required' => true,
				'primary' => true,
			]),
			new ORM\Fields\EnumField('TYPE', [
				'primary' => true,
				'values' => [
					Glossary::ENTITY_STOCKS,
					Glossary::ENTITY_PRICE,
				],
			]),
			new ORM\Fields\StringField('PRIMARY', [
				'required' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 100) ];
				},
			]),
			new ORM\Fields\StringField('VALUE', [
				'required' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 10) ];
				},
			]),
			new ORM\Fields\EnumField('STATUS', [
				'required' => true,
				'values' => [
					static::STATUS_WAIT,
					static::STATUS_DELETE,
					static::STATUS_READY,
					static::STATUS_FAILED,
					static::STATUS_UNPUBLISHED,
				],
			]),
			new ORM\Fields\IntegerField('REPEAT', [
				'default_value' => 0,
			]),
			new ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
			new ORM\Fields\DatetimeField('SUBMITTED_AT', [
				'required' => true,
			]),
			new ORM\Fields\Relations\Reference('SERVICE_PRIMARY', PrimaryMap\RepositoryTable::class, [
				'=this.PUSH_ID' => 'ref.PUSH_ID',
				'=this.PRIMARY' => 'ref.PRIMARY',
			]),
		];
	}

	public static function migrate(Main\DB\Connection $connection) : void
	{
		$tableName = static::getTableName();
		$existsFields = $connection->getTableFields($tableName);
		$sqlHelper = $connection->getSqlHelper();

		if (!isset($existsFields['SUBMITTED_AT']))
		{
			$submittedAtField = static::getEntity()->getField('SUBMITTED_AT');

			$connection->queryExecute(sprintf(
				'ALTER TABLE %s ADD COLUMN %s %s',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote('SUBMITTED_AT'),
				$sqlHelper->getColumnTypeByField($submittedAtField)
			));
		}
	}
}