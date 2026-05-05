<?php
namespace Avito\Export\Trading\Queue;

use Avito\Export;
use Bitrix\Main\ORM;

class Table extends Export\DB\Table
{
	public static function getTableName() : string
	{
		return 'avito_export_trading_queue';
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new ORM\Fields\IntegerField('SETUP_ID', [
				'required' => true,
			]),
			new ORM\Fields\StringField('PATH', [
				'required' => true,
				'validation' => [__CLASS__, 'validatePath'],
			]),
			new ORM\Fields\ArrayField('DATA', [
				'required' => true,
			]),
			new ORM\Fields\DatetimeField('EXEC_DATE', [
				'required' => true,
			]),
			new ORM\Fields\IntegerField('EXEC_COUNT', [
				'default_value' => 0,
			]),
			new ORM\Fields\IntegerField('INTERVAL', [
				'required' => true,
				'default_value' => 3600,
			])
		];
	}

	public static function validatePath() : array
	{
		return [
			new ORM\Fields\Validators\LengthValidator(null, 30),
		];
	}
}