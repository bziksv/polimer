<?php

namespace Avito\Export\Data;

use Avito\Export\Concerns;
use Bitrix\Main;

class Date
{
	use Concerns\HasOnceStatic;

	public const SERVICE_FORMAT = 'Y-m-d';

	public static function cast($value, string $format = self::SERVICE_FORMAT) : ?Main\Type\Date
	{
		if ($value === null) { return null; }

		if (is_string($value))
		{
			return new Main\Type\Date($value, $format);
		}

		if ($value instanceof \DateTime)
		{
			return Main\Type\Date::createFromPhp($value);
		}

		if ($value instanceof Main\Type\Date)
		{
			return $value;
		}

		throw new Main\ArgumentException(sprintf('unsupported type %s for date', gettype($value)));
	}

	public static function forService(Main\Type\Date $date) : string
	{
		return $date->format(static::SERVICE_FORMAT);
	}

	public static function format(Main\Type\Date $date) : string
	{
		$format = static::cultureFormat();

		return $date->format($format);
	}

	protected static function cultureFormat() : string
	{
		return static::onceStatic('cultureFormat', static function() {
			$culture = Main\Application::getInstance()->getContext()->getCulture();
			$dateFormat = $culture !== null ? $culture->getDateFormat() : 'DD.MM.YYYY';

			return Main\Type\DateTime::convertFormatToPhp($dateFormat);
		});
	}
}
