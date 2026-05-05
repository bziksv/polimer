<?php
namespace Avito\Export\Watcher\Setup;

use Avito\Export\Concerns;
use Avito\Export\Utils;
use Avito\Export\Admin\UserField;

class RefreshFacade
{
	use Concerns\HasLocale;

	public const PERIOD_TEN_MINUTES = 600;
	public const PERIOD_THIRTY_MINUTES = 1800;
	public const PERIOD_ONE_HOUR = 3600;
	public const PERIOD_TWO_HOURS = 7200;
	public const PERIOD_THREE_HOURS = 10800;
	public const PERIOD_SIX_HOURS = 21600;
	public const PERIOD_HALF_DAY = 43200;
	public const PERIOD_ONE_DAY = 86400;

	public static function periodField(array $field) : array
	{
		$field['EDIT_IN_LIST'] = (Utils\Agent::cronConfigured() ? 'Y' : 'N');
		$field['USER_TYPE'] = UserField\Registry::description('enumeration');
		$field['VALUES'] = static::periodVariants();

		return $field;
	}

	protected static function periodVariants() : array
	{
		$result = [];
		$variants = [
			static::PERIOD_TEN_MINUTES,
			static::PERIOD_THIRTY_MINUTES,
			static::PERIOD_ONE_HOUR,
			static::PERIOD_TWO_HOURS,
			static::PERIOD_THREE_HOURS,
			static::PERIOD_SIX_HOURS,
			static::PERIOD_HALF_DAY,
			static::PERIOD_ONE_DAY,
		];

		foreach ($variants as $variant)
		{
			$result[] = [
				'ID' => $variant,
				'VALUE' => self::getLocale('PERIOD_' . $variant),
			];
		}

		return $result;
	}

	public static function timeField(array $field) : array
	{
		$field['HELP_MESSAGE'] = self::getLocale('TIME_HELP');
		$field['USER_TYPE'] = UserField\Registry::description('time');
		$field['DEPEND'] = [
			'REFRESH_PERIOD' => [
				'RULE' => 'EMPTY',
				'VALUE' => false,
			],
		];

		return $field;
	}
}