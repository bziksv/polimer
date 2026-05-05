<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Concerns;
use Avito\Export\Assert;
use Avito\Export\Admin\Property\CategoryProperty;

class Element implements Behavior
{
	use Concerns\HasLocale;

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function variants(array $property) : array
	{
		$iblockId = $this->propertyIblockId($property);

		if ($iblockId <= 0 || !Main\Loader::includeModule('iblock')) { return []; }

		$query = Iblock\PropertyTable::getList([
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=ACTIVE' => 'Y',
				'=USER_TYPE' => CategoryProperty::USER_TYPE,
			],
			'select' => [
				'ID',
				'NAME',
			],
		]);

		return $query->fetchAll();
	}

	public function options(array $property, string $field) : array
	{
		return [
			'name' => 'PROP[' . $field . ']',
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

	protected function propertyIblockId(array $property) : int
	{
		return (int)($property['IBLOCK_ID'] ?? 0);
	}

	public function elementValues(string $propertyId, array $elementIds) : array
	{
		if (empty($elementIds) || !Main\Loader::includeModule('iblock')) { return []; }

		$result = [];
		$property = $this->iblockProperty($propertyId);
		$query = \CIBlockElement::GetPropertyValues($property['IBLOCK_ID'], [ '=ID' => $elementIds ], false, [ 'ID' => $property['ID'] ]);

		while ($row = $query->Fetch())
		{
			$value = $row[$property['ID']] ?? null;

			if (empty($value)) { continue; }

			$result[$row['IBLOCK_ELEMENT_ID']] = $value;
		}

		return $result;
	}

	public function saveValues(string $propertyId, array $elementIds, string $value) : void
	{
		if (!Main\Loader::includeModule('iblock')) { return; }

		$property = $this->iblockProperty($propertyId);

		foreach ($elementIds as $elementId)
		{
			\CIBlockElement::SetPropertyValuesEx($elementId, $property['IBLOCK_ID'], [ $property['ID'] => $value ]);
		}
	}

	/** @noinspection CallableParameterUseCaseInTypeContextInspection */
	protected function iblockProperty(string $propertyId) : array
	{
		$propertyId = (int)$propertyId;

		if ($propertyId <= 0)
		{
			throw new Main\ArgumentException(sprintf('unknown property id %s', $propertyId));
		}

		$query = Iblock\PropertyTable::getList([
			'filter' => [ '=ID' => $propertyId ],
			'limit' => 1,
		]);
		$property = $query->fetch();

		Assert::isArray($property, '$property');

		return $property;
	}
}