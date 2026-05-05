<?php
namespace Avito\Export\Trading\Entity\SaleCrm\Internals;

use Avito\Export\DB;
use Bitrix\Main\ORM;
use Bitrix\Main\Type;

class WaitChatTable extends DB\Table
{
	public static function getTableName() : string
	{
		return 'avito_export_wait_chat';
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\StringField('CHAT_ID', [
				'primary' => true,
			]),
			new ORM\Fields\IntegerField('ORDER_ID', [
				'primary' => true,
			]),
			new ORM\Fields\DatetimeField('TIMESTAMP_X', [
				'default_value' => function() { return new Type\DateTime(); },
			]),
		];
	}
}
