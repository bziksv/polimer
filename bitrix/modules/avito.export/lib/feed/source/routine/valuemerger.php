<?php
namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Utils;

class ValueMerger
{
	public static function merge(array $sourceValues, array $mergeMap) : array
	{
		if (empty($mergeMap)) { return $sourceValues; }

		$result = array_diff_key($sourceValues, $mergeMap);

		foreach (self::groupFrom($sourceValues, $mergeMap) as $to => $fields)
		{
			if (!isset($result[$to])) { continue; }

			foreach ($fields as $field => $valuesGroup)
			{
				$result[$to][$field] = self::mergeFieldValue(
					$valuesGroup,
					$result[$to][$field] ?? null
				);
			}
		}

		return $result;
	}

	private static function groupFrom(array $sourceValues, array $mergeMap) : array
	{
		$result = [];

		foreach ($mergeMap as $from => $to)
		{
			if (!isset($sourceValues[$from])) { continue; }

			if (!isset($result[$to]))
			{
				$result[$to] = array_fill_keys(array_keys($sourceValues[$from]), []);
			}

			foreach ($sourceValues[$from] as $key => $fieldValue)
			{
				if (Utils\Value::isEmpty($fieldValue)) { continue; }

				$result[$to][$key][] = $fieldValue;
			}
		}

		return $result;
	}

	private static function mergeFieldValue(array $valuesGroup, $originValue)
	{
		if (!Utils\Value::isEmpty($originValue))
		{
			array_unshift($valuesGroup, $originValue);
		}

		if (empty($valuesGroup)) { return $originValue; }

		$isArray = array_reduce($valuesGroup, static function (bool $isArray, $value) {
			return $isArray && is_array($value);
		}, true);

		if ($isArray)
		{
			$result = array_merge(...$valuesGroup);
		}
		else
		{
			$result = $valuesGroup;
		}

		return $result;
	}

	public static function knownKeys(array $sourceValues, string $startsWith = null) : array
	{
		$processed = [];
		$result = [];

		foreach ($sourceValues as $elementValues)
		{
			foreach ($elementValues as $field => $unused)
			{
				if (isset($processed[$field])) { continue; }

				$processed[$field] = true;

				if ($startsWith !== null && mb_strpos($field, $startsWith) !== 0) { continue; }

				$result[] = $field;
			}
		}

		return $result;
	}

	public static function toBoolean(array $sourceValues, array $fields) : array
	{
		foreach ($sourceValues as &$elementValues)
		{
			foreach ($fields as $field)
			{
				if (!isset($elementValues[$field]) || !is_array($elementValues[$field])) { continue; }

				$export = null;

				foreach ($elementValues[$field] as $value)
				{
					if ($value === true || $value === 'Y')
					{
						$export = $value;
						break;
					}

					if ($export === null)
					{
						$export = $value;
					}
				}

				$elementValues[$field] = $export;
			}
		}
		unset($elementValues);

		return $sourceValues;
	}

	public static function toSum(array $sourceValues, array $fields) : array
	{
		foreach ($sourceValues as &$elementValues)
		{
			foreach ($fields as $field)
			{
				if (!isset($elementValues[$field]) || !is_array($elementValues[$field])) { continue; }

				$elementValues[$field] = array_sum($elementValues[$field]);
			}
		}
		unset($elementValues);

		return $sourceValues;
	}
}