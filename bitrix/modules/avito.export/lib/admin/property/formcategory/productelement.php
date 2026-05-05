<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Admin\Property\CategoryProperty;

/** @noinspection PhpUnused */
class ProductElement extends Element
{
	use HasSku;
	use Concerns\HasLocale;

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function options(array $property, string $field) : array
	{
		$skuIblockId = $this->propertyIblockId($property);
		$skuPropertyId = $this->skuPropertyId($property);

		if ($skuIblockId <= 0 || $skuPropertyId <= 0) { return []; }

		return [
			'skuName' => 'PROP[' . $skuPropertyId . ']',
			'skuIblockId' => $skuIblockId,
			'property' => $field,
		];
	}

	public function value(array $form) : string
	{
		Assert::notNull($form['skuIblockId'], 'iblockId');
		Assert::notNull($form['property'], 'property');

		$sku = (int)($form['sku'] ?? 0);

		if ($sku <= 0)
		{
			throw new Main\ArgumentException(self::getLocale('FORM_SKU_REQUIRED'));
		}

		if (!Main\Loader::includeModule('iblock'))
		{
			throw new Main\SystemException('IBLOCK_REQUIRED');
		}

		$properties = [];

		\CIBlockElement::GetPropertyValuesArray($properties, $form['skuIblockId'], [ 'ID' => $sku ], [ 'ID' => $form['property'] ], [
			'USE_PROPERTY_ID' => 'Y',
		]);

		$property = $properties[$sku][$form['property']] ?? null;

		if ($property === null)
		{
			throw new Main\ArgumentException(self::getLocale('PROPERTY_VALUE_NOT_FOUND'));
		}

		if ($property['USER_TYPE'] !== CategoryProperty::USER_TYPE)
		{
			throw new Main\ArgumentException(self::getLocale('PROPERTY_UNKNOWN_USER_TYPE'));
		}

		$value = (string)(is_array($property['VALUE']) ? reset($property['VALUE']) : $property['VALUE']);

		if ($value === '')
		{
			throw new Main\ArgumentException(self::getLocale('SKU_CATEGORY_REQUIRED'));
		}

		return $value;
	}

	public function elementValues(string $propertyId, array $elementIds) : array
	{
		$skuMap = $this->mapOffersSku($elementIds);
		$skuValues = parent::elementValues($propertyId, array_unique($skuMap));

		return $this->makeOfferValues($skuValues, $skuMap);
	}

	public function saveValues(string $propertyId, array $elementIds, string $value) : void
	{
		$skuMap = $this->mapOffersSku($elementIds);
		$skuIds = array_unique($skuMap);

		parent::saveValues($propertyId, $skuIds, $value);
	}
}