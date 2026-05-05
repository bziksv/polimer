<?php
namespace Avito\Export\Feed\Source\Routine;

use Avito\Export\Feed\Source;

class QueryFilter
{
	public static function make(array $conditions, array $fields) : array
	{
		/** @var array<string, Source\Field\Field> $fieldMap */
		$fieldMap = array_combine(
			array_map(static function(Source\Field\Field $field) { return $field->id(); }, $fields),
			$fields
		);

		$conflicts = self::searchConflicts($conditions);
		
		$result = [];

		foreach ($conditions as $key => $condition)
		{
			if (!isset($fieldMap[$condition['FIELD']])) { continue; }

			$field = $fieldMap[$condition['FIELD']];
			$fieldFilter = $field->filter($condition['COMPARE'], $condition['VALUE']);

			if (isset($conflicts[$key]))
			{
				$result[] = $fieldFilter;
				continue;
			}

			foreach ($fieldFilter as $queryField => $queryValue)
			{
				if (!isset($result[$queryField]))
				{
					$result[$queryField] = $queryValue;
				}
				else
				{
					if ($result[$queryField] === $queryValue) { continue; }

					if (!is_array($result[$queryField]))
					{
						$result[$queryField] = [$result[$queryField]];
					}

					if (!is_array($queryValue))
					{
						$result[$queryField][] = $queryValue;
					}
					else
					{
						$result[$queryField] = array_merge($result[$queryField], $queryValue);
					}
				}
			}
		}

		return $result;
	}

	protected static function searchConflicts(array $conditions) : array
	{
		$atList = [];
		$result = [];

		foreach ($conditions as $condition)
		{
			$field = $condition['FIELD'];
			if ($condition['COMPARE'] === Source\Field\Condition::AT_LIST)
			{
				$atList[$field] = true;
			}
		}

		foreach ($conditions as $key => $condition)
		{
			$field = $condition['FIELD'];
			if ($condition['COMPARE'] === Source\Field\Condition::NOT_AT_LIST && isset($atList[$field]))
			{
				$result[$key] = true;
			}
		}

		return $result;
	}
}