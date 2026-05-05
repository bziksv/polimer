<?php
namespace Avito\Export\Utils;

class ArrayHelper
{
	public static function mapColumns(array $array, array $map) : array
	{
		foreach ($array as &$values)
		{
			foreach ($map as $from => $to)
			{
				if (isset($values[$from]) || array_key_exists($from, $values))
				{
					$values[$to] = $values[$from];
					unset($values[$from]);
				}
			}
		}
		unset($values);

		return $array;
	}

	public static function keysByColumn(array $array, string $column) : array
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			$value = $values[$column] ?? null;

			if ($value === null || isset($result[$value])) { continue; }

			$result[$value] = $key;
		}

		return $result;
	}

	public static function column(array $array, string $column) : array
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			$value = $values[$column] ?? null;

			if ($value === null) { continue; }

			$result[$key] = $value;
		}

		return $result;
	}

	public static function columnToKey(array $array, string $column) : array
	{
		$result = [];

		foreach ($array as $values)
		{
			$value = $values[$column] ?? null;

			if ($value === null || isset($result[$value])) { continue; }

			$result[$value] = $values;
		}

		return $result;
	}

	public static function renameKeys(array $array, array $map) : array
	{
		foreach ($map as $from => $to)
		{
			if (isset($array[$from]) || array_key_exists($from, $array))
			{
				$array[$to] = $array[$from];
				unset($array[$from]);
			}
		}

		return $array;
	}

	public static function prefixKeys(array $array, string $prefix) : array
	{
		$result = [];

		foreach ($array as $key => $value)
		{
			$result[$prefix . $key] = $value;
		}

		return $result;
	}

	public static function groupBy(array $array, string $column, $fallback = null) : array
	{
		$result = [];

		foreach ($array as $key => $values)
		{
			$value = $values[$column] ?? $fallback;

			if ($value === null) { continue; }

			if (!isset($result[$value])) { $result[$value] = []; }

			$result[$value][$key] = $values;
		}

		return $result;
	}

	public static function countColumn(array $array, ?string $column = null, $fallback = null) : array
	{
		$result = [];

		foreach ($array as $values)
		{
			$value = $column !== null ? ($values[$column] ?? $fallback) : $values;

			if ($value === null) { continue; }

			if (!isset($result[$value])) { $result[$value] = 0; }

			++$result[$value];
		}

		return $result;
	}
}