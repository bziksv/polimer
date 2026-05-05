<?php
namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;

class CharacteristicProvider
{
	use Concerns\HasLocale;
	use Concerns\HasOnceStatic;

	public static function exportFields(string $type) : array
	{
		return [
			[
				'ID' => 'self',
				'TITLE' => self::getLocale('EXPORT_FIELD_SELF'),
				'TYPE' => $type,
			],
		];
	}

	public static function exportValue($property, $value, string $field)
	{
		$isMultiple = ($property['MULTIPLE'] === 'Y');
		$characteristics = static::combineValues($value, $isMultiple);

		if ($field === 'self')
		{
			$result = $characteristics;
		}
		else
		{
			$result = $characteristics[$field] ?? null;
		}

		return $result;
	}

	protected static function combineValues($value, $isMultiple) : ?array
	{
		if ($isMultiple)
		{
			if (!is_array($value['VALUE']) || !is_array($value['DESCRIPTION'])) { return null; }

			$result = [];

			foreach ($value['VALUE'] as $key => $oneValue)
			{
				if (!isset($value['DESCRIPTION'][$key])) { continue; }

				$name = $value['DESCRIPTION'][$key];
				$result[$name] = $oneValue;
			}
		}
		else
		{
			if (!is_array($value['VALUE'])) { return null; }

			$result = $value['VALUE'];
		}

		return $result;
	}
}