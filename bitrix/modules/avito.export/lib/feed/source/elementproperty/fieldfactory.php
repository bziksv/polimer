<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Avito\Export\Feed\Source\Field;

class FieldFactory extends Field\Factory
{
	public function make(array $field) : Field\Field
	{
		$userType = $field['USER_TYPE'] ?? null;
		$type = $field['PROPERTY_TYPE'] ?? 'S';

		if ($userType === 'directory') // need load highload for check constant
		{
			$result = new DictionaryField($field);
		}
		else if ($userType === \CIBlockPropertyDate::USER_TYPE)
		{
			$result = new DateField($field);
		}
		else if ($userType === \CIBlockPropertyDateTime::USER_TYPE)
		{
			$result = new DateTimeField($field);
		}
		else if ($type === 'E')
		{
			$result = new ElementField($field);
		}
		else if ($type === 'L')
		{
			$result = new ListField($field);
		}
		else
		{
			$result = parent::make($field + [
				'TYPE' => $type,
			]);
		}

		return $result;
	}
}