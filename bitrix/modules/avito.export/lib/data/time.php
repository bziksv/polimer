<?php
namespace Avito\Export\Data;

use Avito\Export\Concerns;

class Time
{
	use Concerns\HasLocale;

	public static function validate(string $value) : ?string
	{
		$value = trim($value);

		if ($value === '')
		{
			$result = null;
		}
		else if (preg_match('/^(\d{1,2})(?::(\d{1,2}))?$/', $value, $matches))
		{
			$hours = (int)$matches[1];
			$minutes = isset($matches[2]) ? (int)$matches[2] : 0;

			if ($hours > 23)
			{
				$result = self::getLocale('VALIDATE_HOUR_MORE_THAN', [ '#LIMIT#' => 23 ]);
			}
			else if ($minutes > 59)
			{
				$result = self::getLocale('VALIDATE_MINUTE_MORE_THAN', [ '#LIMIT#' => 59 ]);
			}
			else
			{
				$result = null;
			}
		}
		else
		{
			$result = self::getLocale('VALIDATE_INVALID');
		}

		return $result;
	}
}