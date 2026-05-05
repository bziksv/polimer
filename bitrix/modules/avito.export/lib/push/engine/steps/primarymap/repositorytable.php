<?php
namespace Avito\Export\Push\Engine\Steps\PrimaryMap;

use Avito\Export\DB;
use Bitrix\Main\ORM;

class RepositoryTable extends DB\Table
{
	public const SERVICE_ID_NULL = 'N';

	public static function getTableName() : string
	{
		return 'avito_export_push_primary';
	}

	public static function getTableIndexes() : array
	{
		return [
			0 => [ 'SERVICE_ID' ],
			1 => [ 'TIMESTAMP_X' ],
		];
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\IntegerField('PUSH_ID', [
				'primary' => true,
			]),
			new ORM\Fields\StringField('PRIMARY', [
				'primary' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 100) ];
				},
			]),
			new ORM\Fields\StringField('SERVICE_ID', [
				'required' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 15) ];
				},
			]),
			new ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}
}