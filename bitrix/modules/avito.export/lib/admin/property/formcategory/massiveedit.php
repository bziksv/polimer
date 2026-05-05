<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Avito\Export\Concerns;
use Bitrix\Main;

/** @noinspection PhpUnused */
class MassiveEdit implements Behavior
{
	use Concerns\HasLocale;

	public function title() : string
	{
		throw new Main\NotImplementedException();
	}

	public function variants(array $property) : array
	{
		throw new Main\NotImplementedException();
	}

	public function options(array $property, string $field) : array
	{
		return [
			'name' => "VALUES[$field][CATEGORY]",
		];
	}

	public function value(array $form) : string
	{
		if (!isset($form['value']) || trim($form['value']) === '')
		{
			throw new Main\ArgumentException(self::getLocale('FORM_VALUE_REQUIRED'));
		}

		return (string)$form['value'];
	}

	public function elementValues(string $propertyId, array $elementIds) : array
	{
		throw new Main\NotImplementedException();
	}

	public function saveValues(string $propertyId, array $elementIds, string $value) : void
	{
		throw new Main\NotImplementedException();
	}
}