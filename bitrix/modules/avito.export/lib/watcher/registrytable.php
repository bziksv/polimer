<?php
namespace Avito\Export\Watcher;

use Avito\Export\DB;
use Avito\Export\Glossary;
use Bitrix\Main\ORM;

class RegistryTable extends DB\Table
{
	public static function getTableName() : string
	{
		return 'avito_export_watcher_registry';
	}

	public static function getTableIndexes() : array
	{
		return [
			0 => ['ENTITY_TYPE', 'ENTITY_ID'],
			1 => ['IBLOCK_ID', 'SOURCE'],
		];
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new ORM\Fields\EnumField('ENTITY_TYPE', [
				'required' => true,
				'values' => [
					Glossary::SERVICE_FEED,
					Glossary::SERVICE_PUSH,
				],
			]),
			new ORM\Fields\IntegerField('ENTITY_ID', [
				'required' => true,
			]),
			new ORM\Fields\IntegerField('IBLOCK_ID', [
				'required' => true,
			]),
			new ORM\Fields\StringField('SOURCE', [
				'required' => true,
				'validation' => [ static::class, 'sourceValidation' ],
			]),
		];
	}

	public static function sourceValidation() : array
	{
		return [
			new ORM\Fields\Validators\LengthValidator(null, 20),
		];
	}
}