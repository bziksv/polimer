<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Main\Type\DateTime;
use Avito\Export\Concerns;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionWeekday extends Iblock\Template\Functions\FunctionBase
{
	use Concerns\HasOnce;

	/** @var DateTime */
	private $today;

	public function setToday(DateTime $dateTime) : void
	{
		$this->today = $dateTime;
	}

	private function today() : DateTime
	{
		return $this->today ?? new DateTime();
	}

    public function calculate(array $parameters) : ?DateTime
    {
		return $this->once('calculate', function(array $parameters) {
			$weekday = $this->sanitizeWeekday($parameters[0]);
			$time = $this->parseTime($parameters[1]);

			if ($weekday === null) { return null; }

			$dateTime = $this->today();
			$currentWeekday = $this->sanitizeWeekday($dateTime->format('w'));
			$currentTime = $this->parseTime($dateTime->format('H:i:s'));

			if (isset($parameters[2]))
			{
				$hasExpire = true;
				$expireWeekday = $this->sanitizeWeekday($parameters[2]) ?? $weekday;
				$expireTime = isset($parameters[3]) ? $this->parseTime($parameters[3]) : $time;
			}
			else
			{
				$hasExpire = false;
				$expireWeekday = $weekday;
				$expireTime = $time;
			}

			/** @noinspection PhpIfWithCommonPartsInspection */
			if ($this->compare($currentWeekday, $currentTime, $expireWeekday, $expireTime) === -1)
			{
				$days = $weekday - $currentWeekday;
				$days -= ($currentWeekday <= $weekday ? 7 : 0);
				$days += ($hasExpire ? 0 : 7);
			}
			else
			{
				$days = $weekday - $currentWeekday;
				$days += ($currentWeekday >= $weekday ? 7 : 0);
			}

			$dateTime->add(sprintf('%sP%sD', $days >= 0 ? '' : '-', abs($days)));
			$dateTime->setTime($time[0], $time[1]);

			return $dateTime;
		}, $parameters);
    }

	private function sanitizeWeekday($weekday) : ?int
	{
		if (!is_numeric($weekday)) { return null; }

		$weekday = (int)$weekday;

		if ($weekday === 0) { $weekday = 7; }

		if ($weekday < 1 || $weekday > 7) { return null; }

		return $weekday;
	}

	private function parseTime($time) : array
	{
		if (!is_string($time)) { return [ 0, 0, 0 ]; }

		[$hour, $minutes, $seconds] = explode(':', $time);

		return [ (int)$hour, (int)$minutes, (int)$seconds ];
	}

	private function compare(int $aWeekday, array $aTime, int $bWeekday, array $bTime) : int
	{
		if ($aWeekday !== $bWeekday)
		{
			return $aWeekday <=> $bWeekday;
		}

		for ($i = 0; $i <= 2; ++$i)
		{
			if ($aTime[$i] === $bTime[$i]) { continue; }

			return $aTime[$i] <=> $bTime[$i];
		}

		return 0;
	}
}