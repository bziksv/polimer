<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionRange extends Iblock\Template\Functions\FunctionBase
{
	private const SEPARATOR = '-';
	private const SIZES = [
		'XXS' => 1,
		'XS' => 2,
		'S' => 3,
		'S/M' => 4,
		'M' => 5,
		'L' => 6,
		'L/XL' => 7,
		'XL' => 8,
		'XXL' => 9,
		'3XL' => 10,
		'4XL' => 11,
		'5XL' => 12,
		'6XL' => 13,
		'7XL' => 14,
		'8XL' => 15,
		'9XL' => 16,
		'10XL' => 17,
	];

	public function calculate(array $parameters)
	{
		$values = $parameters[0] ?? null;
		$separator = $parameters[1] ?? self::SEPARATOR;

		if (empty($values) || !is_array($values)) { return $values; }
		if (count($values) === 1) { return reset($values); }

		[$min, $max] = $this->sort($values);

		if ($min === $max) { return $min; }

		return $min . $separator . $max;
	}

	private function sort(array $values) : array
	{
		$minUpper = null;
		$min = null;
		$maxUpper = null;
		$max = null;
		$unit = null;
		$unitFailed = false;

		foreach ($values as $value)
		{
			if (!is_scalar($value) || $value === '') { continue; }

			$valueUpper = mb_strtoupper((string)$value);

			if (isset(self::SIZES[$valueUpper]))
			{
				$valueUpper = self::SIZES[$valueUpper];
			}

			if (is_numeric($valueUpper))
			{
				$valueUpper = (float)$valueUpper;
				$unitFailed = true;
			}
			else if (preg_match('/^(\d+)(?:[.,](\d+))?(.*)$/', (string)$value, $matches))
			{
				if ($unit !== null && $unit !== $matches[3])
				{
					$unitFailed = true;
				}

				$valueUpper = (float)((int)$matches[1] . '.' . (int)$matches[2]);
				$unit = $matches[3];
			}
			else
			{
				$unitFailed = true;
			}

			if ($minUpper === null || $minUpper > $valueUpper)
			{
				$minUpper = $valueUpper;
				$min = $value;
			}

			if ($maxUpper === null || $maxUpper < $valueUpper)
			{
				$maxUpper = $valueUpper;
				$max = $value;
			}
		}

		if (!$unitFailed && $unit !== null && $min !== $max)
		{
			$min = str_replace($unit, '', $min);
		}

		return [$min, $max];
	}
}