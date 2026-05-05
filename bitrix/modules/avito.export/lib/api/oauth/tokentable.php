<?php
namespace Avito\Export\Api\OAuth;

use Avito\Export\DB;
use Bitrix\Main\ORM;

class TokenTable extends DB\Table
{
	public const CLIENT_OWNER = 'owner';

	public static function getObjectClass() : string
	{
		return Token::class;
	}

	public static function getTableName() : string
	{
		return 'avito_export_oauth_token';
	}

	public static function getMap() : array
	{
		return [
			new ORM\Fields\StringField('CLIENT_ID', [
				'required' => true,
				'primary' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 30) ];
				},
			]),
			new ORM\Fields\StringField('SERVICE_ID', [
				'required' => true,
				'primary' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 60) ];
				},
			]),
			new ORM\Fields\StringField('NAME', [
				'required' => true,
				'validation' => static function() {
					return [ new ORM\Fields\Validators\LengthValidator(null, 100) ];
				},
			]),
			new ORM\Fields\TextField('ACCESS_TOKEN', [
				'required' => true,
			]),
			new ORM\Fields\TextField('REFRESH_TOKEN', [
				'required' => true,
			]),
			new ORM\Fields\DatetimeField('EXPIRES', [
				'required' => true,
			]),
			new ORM\Fields\StringField('TYPE', [
				'required' => true,
			]),
		];
	}
}
