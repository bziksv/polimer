<?php
namespace Avito\Export\Data;

use Bitrix\Main;

class DateTime
{
	public static function cast($value, string $format = \DateTimeInterface::ATOM) : ?Main\Type\DateTime
	{
		if ($value === null) { return null; }

		if (is_string($value))
		{
			return new Main\Type\DateTime($value, $format);
		}

		if ($value instanceof \DateTime)
		{
			return Main\Type\DateTime::createFromPhp($value);
		}

		if ($value instanceof Main\Type\DateTime)
		{
			return $value;
		}

		throw new Main\ArgumentException(sprintf('unsupported type %s for dateTime', gettype($value)));
	}

	public static function format(Main\Type\Date $date) : string
	{
		if ($date instanceof Main\Type\DateTime)
		{
			return sprintf('%s %s', Date::format($date), $date->format('H:i'));
		}

		return Date::format($date);
	}

	public static function stringify(Main\Type\DateTime $date) : string
	{
		return $date->format(\DateTimeInterface::ATOM);
	}

	public static function compare(Main\Type\DateTime $a, Main\Type\DateTime $b) : int
	{
		return ($a->getTimestamp() <=> $b->getTimestamp());
	}

	public static function max(Main\Type\DateTime $dateA, Main\Type\DateTime ...$compareDates) : Main\Type\DateTime
	{
		$result = $dateA;

		foreach ($compareDates as $compareDate)
		{
			if (static::compare($result, $compareDate) === -1)
			{
				$result = $compareDate;
			}
		}

		return $result;
	}
}