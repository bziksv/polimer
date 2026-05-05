<?php

namespace Avito\Export\Admin\Property\ValueInherit;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Admin\Property\FormCategory;
use Avito\Export\Utils\ArrayHelper;

class Characteristic
{
	use Concerns\HasOnceStatic;

	public static function properties(int $iblockId) : array
	{
		$cacheKey = implode(':', ['properties', $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($iblockId) {
			$result = [];

			$context = Source\ContextPool::iblockInstance($iblockId);
			$productIblockId = $context->productIblockId();

			if ($productIblockId !== null)
			{
				$properties = [
					FormCategory\Registry::ELEMENT => static::elementProperties($iblockId),
					FormCategory\Registry::PRODUCT_ELEMENT => static::elementProperties($productIblockId),
					FormCategory\Registry::PRODUCT_SECTION => static::sectionFields($productIblockId)
				];
			}
			else
			{
				$properties = [
					FormCategory\Registry::ELEMENT => static::elementProperties($iblockId),
					FormCategory\Registry::SECTION => static::sectionFields($iblockId),
				];
			}

			foreach ($properties as $type => $propertyList)
			{
				foreach ($propertyList as $property)
				{
					$result[] = [
						$type,
						$property['ID'],
					];
				}
			}

			return $result;
		});
	}

	public static function parentValues(array $elementIds, int $iblockId) : array
	{
		if (empty($elementIds)) { return []; }

		$context = Source\ContextPool::iblockInstance($iblockId);
		$productIblockId = $context->productIblockId();

		if ($productIblockId === null) { return static::sectionValues($elementIds, $iblockId); }

		$productMap = \CCatalogSKU::getProductList($elementIds, $iblockId);

		if (empty($productMap)) { return []; }

		$result = [];
		$productToOffers = ArrayHelper::groupBy($productMap, 'ID');
		$productIds = array_keys($productToOffers);

		$parts = [
			static::sectionValues($productIds, $productIblockId),
			static::elementValues($productIds, $productIblockId),
		];

		foreach ($parts as $part)
		{
			foreach ($part as $productId => $values)
			{
				foreach ($productToOffers[$productId] as $offerId => $offerLink)
				{
					if (!isset($result[$offerId])) { $result[$offerId] = []; }

					$result[$offerId] += $values;
				}
			}
		}

		return $result;
	}

	public static function parentValue(int $elementId, int $iblockId)
	{
		$cacheKey = implode(':', ['parentValue', $elementId, $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($elementId, $iblockId) {
			$context = Source\ContextPool::iblockInstance($iblockId);
			$productIblockId = $context->productIblockId();

			if ($productIblockId === null) { return static::sectionValue($elementId, $iblockId); }

			$productInfo = \CCatalogSKU::GetProductInfo($elementId, $iblockId);

			if ($productInfo === false) { return null; }

			$productId = $productInfo['ID'];

			return static::sectionValue($productId, $productIblockId) + static::elementValue($productId, $productIblockId);
		});
	}

	protected static function elementValues(array $elementIds, int $iblockId) : array
	{
		$leftElements = array_combine($elementIds, $elementIds);
		$result = [];

		foreach (static::elementProperties($iblockId) as $property)
		{
			$elementValues = static::propertyValues(array_keys($leftElements), $property);

			foreach ($leftElements as $elementId)
			{
				if (empty($elementValues[$elementId])) { continue; }

				$value = static::extractValue($elementValues[$elementId], $property);

				if (empty($value)) { continue; }

				$result[$elementId] = $value;

				unset($leftElements[$elementId]);
			}

			if (empty($leftElements)) { break; }
		}

		return $result;
	}

	protected static function sectionValues(array $elementIds, int $iblockId) : array
	{
		if (empty($elementIds)) { return []; }

		$fields = array_column(static::sectionFields($iblockId), 'FIELD_NAME');
		$fields = array_map(static function(string $fieldName) { return $fieldName . '.self'; }, $fields);

		if (empty($fields)) { return []; }

		$result = [];
		$sourceValues = Source\Routine\QueryFacade::fetch(
			$iblockId,
			$elementIds,
			Source\Routine\QueryFacade::sourceSelect([
				Source\Registry::SECTION_PROPERTY => array_values($fields),
			])
		);

		foreach ($sourceValues as $elementId => $elementValues)
		{
			if (empty($elementValues[Source\Registry::SECTION_PROPERTY])) { continue; }

			$sectionValues = $elementValues[Source\Registry::SECTION_PROPERTY];

			foreach ($fields as $field)
			{
				if (empty($sectionValues[$field])) { continue; }

				$result[$elementId] = $sectionValues[$field];
				break;
			}
		}

		return $result;
	}

	protected static function elementValue(int $elementId, int $iblockId) : array
	{
		$result = static::elementValues([ $elementId ], $iblockId);

		return $result[$elementId] ?? [];
	}

	protected static function sectionValue(int $elementId, int $iblockId) : array
	{
		$result = static::sectionValues([ $elementId ], $iblockId);

		return $result[$elementId] ?? [];
	}

	protected static function elementProperties(int $iblockId) : array
	{
		$cacheKey = implode(':', ['elementProperties', $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($iblockId) {
			if (!Main\Loader::includeModule('iblock')) { return []; }

			$query = Iblock\PropertyTable::getList([
				'select' => [ 'IBLOCK_ID', 'ID', 'MULTIPLE' ],
				'filter' => [
					'=IBLOCK_ID' => $iblockId,
					'=ACTIVE' => true,
					'=USER_TYPE' => Admin\Property\CharacteristicProperty::USER_TYPE,
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC',
				],
			]);

			return $query->fetchAll();
		});
	}

	public static function sectionFields(int $iblockId) : array
	{
		$cacheKey = implode(':', ['sectionFields', $iblockId]);

		return static::onceStatic($cacheKey, static function() use ($iblockId) {
			global $USER_FIELD_MANAGER;

			$userFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_' . $iblockId . '_SECTION', 0, LANGUAGE_ID);
			$result = [];

			foreach ($userFields as $field)
			{
				if ($field['USER_TYPE_ID'] !== Admin\Property\CharacteristicField::USER_TYPE_ID || $field['MULTIPLE'] === 'Y') { continue; }

				$field['ID'] = $field['FIELD_NAME'];
				$field['IBLOCK_ID'] = $iblockId;

				$result[] = $field;
			}

			return $result;
		});
	}

	protected static function propertyValues(array $elementIds, array $property) : array
	{
		if (empty($elementIds)) { return []; }

		$isMultiple = ($property['MULTIPLE'] === 'Y');

		$query = \CIBlockElement::GetPropertyValues($property['IBLOCK_ID'], [ '=ID' => $elementIds ], $isMultiple, [ 'ID' => $property['ID'] ]);
		$result = [];

		while ($row = $query->Fetch())
		{
			$elementId = (int)$row['IBLOCK_ELEMENT_ID'];

			$result[$elementId] = $row;
		}

		return $result;
	}

	protected static function extractValue(array $row, array $property) : ?array
	{
		$isMultiple = ($property['MULTIPLE'] === 'Y');

		if (empty($row[$property['ID']])) { return null; }

		if ($isMultiple)
		{
			if (empty($row['DESCRIPTION'][$property['ID']])) { return null; }

			$value = array_map(static function ($value) use ($property) {
				return Admin\Property\CharacteristicProperty::convertFromDB($property, [ 'VALUE' => $value ])['VALUE'];
			}, (array)$row[$property['ID']]);

			return array_combine(
				(array)$row['DESCRIPTION'][$property['ID']],
				$value
			);
		}

		$value = Admin\Property\CharacteristicProperty::convertFromDB($property, [ 'VALUE' => $row[$property['ID']] ]);

		return $value['VALUE'];
	}
}