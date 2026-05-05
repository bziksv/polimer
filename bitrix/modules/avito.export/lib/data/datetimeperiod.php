<?php
namespace Avito\Export\Data;

use Bitrix\Main;
use Avito\Export\Data;

class DateTimePeriod
{
	public static function format(Main\Type\Date $from = null, Main\Type\Date $to = null) : string
	{
		$dates = array_filter([
			$from,
			$to,
		]);

		if (empty($dates)) { return ''; }

		$datesFormatted = array_map(static function(Main\Type\DateTime $date) { return Data\Date::format($date); }, $dates);
		$datesUnique = array_unique($datesFormatted);
		$timesFormatted = array_map(static function(Main\Type\DateTime $date) { return $date->format('H:i'); }, $dates);
		$timesUnique = array_unique($timesFormatted);
		$useTime = (
			count($timesUnique) > 1
			|| (count($timesUnique) === 1 && reset($timesUnique) !== '00:00')
		);

		if (count($datesUnique) === 1)
		{
			$result =
				reset($datesUnique)
				. ($useTime ? ' ' . implode('-', $timesUnique) : '');
		}
		else
		{
			$parts = [];

			foreach ($datesFormatted as $key => $dateFormatted)
			{
				$timeFormatted = $timesFormatted[$key];

				$parts[] =
					$dateFormatted
					. ($useTime ? ' ' . $timeFormatted : '');
			}

			$result = implode(' – ', $parts);
		}

		return $result;
	}
}