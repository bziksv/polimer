<?php
namespace Avito\Export\Feed\Source\Routine;

class Values
{
	public static function isEmpty($value) : bool
	{
		if ($value === null)
		{
			$result = true;
		}
		else if (is_scalar($value))
		{
			$result = (string)$value === '';
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}

	public static function catalogElements(array $elements, array $parents) : array
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			if (isset($element['PARENT_ID']))
			{
				$parentId = $element['PARENT_ID'];

				if (!isset($parents[$parentId])) { continue; }

				$result[$elementId] = $parents[$parentId];
			}
			else
			{
				$result[$elementId] = $element;
			}
		}

		return $result;
	}

	public static function offerElements(array $elements) : array
	{
		return array_filter($elements, static function(array $element) {
			return isset($element['PARENT_ID']);
		});
	}
}
