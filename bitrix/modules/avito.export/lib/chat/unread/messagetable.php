<?php
namespace Avito\Export\Chat\Unread;

use Avito\Export\DB;
use Avito\Export\Psr;
use Avito\Export\Exchange;
use Avito\Export\Concerns;
use Bitrix\Main;

class MessageTable extends DB\Table
{
	use Concerns\HasLocale;

	public static function getObjectClass() : string
	{
		return Message::class;
	}

	public static function getTableName() : string
	{
		return 'avito_export_chat_unread';
	}

	public static function getTableIndexes() : array
	{
		return [
			0 => ['SETUP_ID', 'CHAT_ID'],
			1 => ['READ'],
		];
	}

	public static function getMap() : array
	{
		return [
			new Main\ORM\Fields\StringField('EXTERNAL_ID', [
				'primary' => true,
				'validation' => function() {
					return [
						new Main\ORM\Fields\Validators\LengthValidator(0, 60),
					];
				},
			]),
			new Main\ORM\Fields\IntegerField('SETUP_ID', [
				'required' => true,
			]),
			new Main\ORM\Fields\StringField('CHAT_ID', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\ORM\Fields\Validators\LengthValidator(0, 60),
					];
				},
			]),
			new Main\ORM\Fields\IntegerField('AUTHOR_ID'),
			new Main\ORM\Fields\StringField('CHAT_TYPE', [
				'validation' => function() {
					return [
						new Main\ORM\Fields\Validators\LengthValidator(0, 3),
					];
				},
			]),
			new Main\ORM\Fields\ArrayField('CONTENT'),
			new Main\ORM\Fields\DatetimeField('CREATED'),
			new Main\ORM\Fields\IntegerField('ITEM_ID'),
			new Main\ORM\Fields\DatetimeField('READ'),
			new Main\ORM\Fields\StringField('TYPE', [
				'required' => true,
				'validation' => function() {
					return [
						new Main\ORM\Fields\Validators\LengthValidator(0, 10),
					];
				},
			]),
			new Main\ORM\Fields\IntegerField('USER_ID'),
		];
	}
}
