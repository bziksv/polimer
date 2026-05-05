<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Avito\Export\Assert;
use Bitrix\Main;
use Avito\Export\Concerns;
use Avito\Export\Admin\Property\Utils;

class SectionSelf implements Behavior
{
	use Concerns\HasLocale;

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function variants(array $property) : array
	{
		throw new Main\NotImplementedException();
	}

	public function options(array $property, string $field) : array
	{
		return [
			'name' => $field,
			'parentName' => 'IBLOCK_SECTION_ID',
			'iblockId' => Utils\SectionField::parseIblockId($property),
		];
	}

	public function value(array $form) : string
	{
		Assert::notNull($form['name'], 'name');
		Assert::notNull($form['iblockId'], 'iblockId');

		$result = trim($form['value'] ?? '');

		if (empty($result) && !empty($form['parentId']) && is_numeric($form['parentId']))
		{
			$result = Utils\SectionField::chainValue($form['name'], $form['iblockId'], (int)$form['parentId']);
		}

		if (empty($result))
		{
			throw new Main\ArgumentException(self::getLocale('FORM_VALUE_REQUIRED'));
		}

		return $result;
	}

	public function saveValues(string $propertyId, array $elementIds, string $value) : void
	{
		throw new Main\NotImplementedException();
	}

	public function elementValues(string $propertyId, array $elementIds) : array
	{
		throw new Main\NotImplementedException();
	}
}