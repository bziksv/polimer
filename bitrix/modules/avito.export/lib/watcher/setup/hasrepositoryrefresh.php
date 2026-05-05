<?php
namespace Avito\Export\Watcher\Setup;

use Avito\Export\Data;
use Bitrix\Main\ORM;

trait HasRepositoryRefresh
{
	protected static function getRefreshMap() : array
	{
		return [
			new ORM\Fields\IntegerField('REFRESH_PERIOD'),
			new ORM\Fields\StringField('REFRESH_TIME', [
				'validation' => [static::class, 'refreshTimeValidation'],
			]),
		];
	}

	public static function refreshTimeValidation() : array
	{
		return [
			new ORM\Fields\Validators\LengthValidator(null, 5),
			[ static::class, 'refreshTimeValidate' ],
		];
	}

	/**
	 * @noinspection ProperNullCoalescingOperatorUsageInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function refreshTimeValidate($value, $primary, array $row, ORM\Fields\Field $field)
	{
		return Data\Time::validate($value) ?? true;
	}

	protected static function extendRefreshPeriodDescription(array $field) : array
	{
		return RefreshFacade::periodField($field);
	}

	protected static function extendRefreshTimeDescription(array $field) : array
	{
		return RefreshFacade::timeField($field);
	}
}