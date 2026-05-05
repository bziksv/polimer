<?php
namespace Avito\Export\Feed\Source\Price;

use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Currency;
use Bitrix\Sale;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Config;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherMergable
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public const VIRTUAL_MINIMAL = 'MINIMAL';
	public const VIRTUAL_OPTIMAL = 'OPTIMAL';

	protected $needDiscountCache;
	protected $onlySaleDiscount;

	public function listener() : Source\Listener
	{
		return new Listener();
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function modules() : array
	{
		return [ 'catalog' ];
	}

	public function fields(Source\Context $context) : array
	{
		if (!$context->hasCatalog()) { return []; }

		return $this->once('fields', function() {
			return array_merge(
				$this->virtualFields(),
				$this->commonFields()
			);
		});
	}

	protected function virtualFields() : array
	{
		$result = [];
		$factory = new Source\Field\Factory();
		$innerFields = $this->innerFields();
		$types = [
			static::VIRTUAL_MINIMAL,
			static::VIRTUAL_OPTIMAL,
		];

		foreach ($types as $type)
		{
			foreach ($innerFields as $innerField)
			{
				$field = [
					'ID' => $type . '_' . $innerField['ID'],
					'NAME' => self::getLocale('FIELD_' . $innerField['ID'], [
						'#TYPE#' => self::getLocale('VIRTUAL_' . $type),
					]),
					'FILTERABLE' => false,
				];
				$field += $innerField;

				$result[] = $factory->make($field);
			}
		}

		return $result;
	}

	protected function commonFields() : array
	{
		$result = [];
		$factory = new Source\Field\Factory();
		$innerFields = $this->innerFields();

		$query = Catalog\GroupTable::getList([
			'select' => [
				'ID',
				'LANG_NAME' => 'CURRENT_LANG.NAME',
			],
		]);

		while ($row = $query->fetch())
		{
			$title = sprintf('[%s] %s', $row['ID'], $row['LANG_NAME']);

			foreach ($innerFields as $innerField)
			{
				$field = [
					'ID' => $row['ID'] . '_' . $innerField['ID'],
					'NAME' => self::getLocale('FIELD_' . $innerField['ID'], [
						'#TYPE#' => $title,
					]),
				];
				$field += $innerField;

				$result[] = $factory->make($field);
			}
		}

		return $result;
	}

	protected function innerFields() : array
	{
		return [
			[
				'ID' => 'DISCOUNT',
				'TYPE' => 'N',
				'FILTERABLE' => false,
			],
			[
				'ID' => 'VALUE',
				'TYPE' => 'N',
			],
			[
				'ID' => 'CURRENCY',
				'TYPE' => 'S',
			],
		];
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		$filter = [];
		$synonyms = [
			'VALUE' => 'PRICE',
		];

		foreach ($conditions as $condition)
		{
			[$priceType, $field] = explode('_', $condition['FIELD'], 2);
			$compareRule = Source\Field\Condition::some($condition['COMPARE']);
			$queryField = $compareRule['QUERY'] . ($synonyms[$field] ?? $field) . '_' . $priceType;

			if (!isset($filter[$queryField]))
			{
				$filter[$queryField] = $condition['VALUE'];
			}
			else
			{
				if (!is_array($filter[$queryField]))
				{
					$filter[$queryField] = [ $filter[$queryField] ];
				}

				if (!is_array($compareRule['VALUE']))
				{
					$filter[$queryField][] = $compareRule['VALUE'];
				}
				else
				{
					$filter[$queryField] = array_merge($filter[$queryField], $compareRule['VALUE']);
				}
			}
		}

		return [
			'CATALOG' => $filter,
		];
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		$groupSelect = $this->groupSelect($select);
		$result = [];

		foreach ($groupSelect as $priceType => $priceSelect)
		{
			$priceValues = $this->priceValues($priceType, $elements, $parents, $priceSelect, $context->siteId());

			foreach ($priceValues as $elementId => $values)
			{
				if (!isset($result[$elementId])) { $result[$elementId] = []; }

				foreach ($values as $name => $value)
				{
					$result[$elementId][$priceType . '_' . $name] = $value;
				}
			}
		}

		return $result;
	}

	protected function groupSelect(array $select) : array
	{
		$result = [];

		foreach ($select as $name)
		{
			[$type, $field] = explode('_', $name);

			if (!isset($result[$type]))
			{
				$result[$type] = [ $field ];
			}
			else if (!in_array($field, $result[$type], true))
			{
				$result[$type][] = $field;
			}
		}

		return $result;
	}

	protected function priceValues($type, array $elements, array $parents, array $select, string $siteId) : array
	{
		$elementIds = array_keys($elements);
		$priceIds = $this->typePriceIds($type);
		$priceRows = $this->priceRows($elementIds, $priceIds);
		$needDiscount = in_array('DISCOUNT', $select, true);
		$result = [];

		$this->preloadData($elements, $parents, $priceIds, $siteId, $needDiscount);

		foreach ($elements as $elementId => $element)
		{
			if (!isset($priceRows[$elementId])) { continue; }

			$elementPriceRows = $priceRows[$elementId];

			if ($type === static::VIRTUAL_OPTIMAL)
			{
				$optimalPrice = $this->elementOptimalPrice($element, $elementPriceRows, $siteId, $needDiscount);
			}
			else
			{
				if ($type !== 'MINIMAL' && count($elementPriceRows) > 1)
				{
					$elementPriceRows = [ reset($elementPriceRows) ];
				}

				$optimalPrice = $this->elementCatalogPrice($element, $elementPriceRows, $siteId, $needDiscount);
			}

			$result[$elementId] = $this->optimalPriceValues($optimalPrice, $select);
		}

		$this->releaseData();

		return $result;
	}

	protected function typePriceIds($type) : array
	{
		if ($type === static::VIRTUAL_MINIMAL)
		{
			$result = $this->publicPriceIds('view');
		}
		else if ($type === static::VIRTUAL_OPTIMAL)
		{
			$result = $this->publicPriceIds('buy');
		}
		else
		{
			$result = [ (int)$type ];
		}

		return $result;
	}

	protected function publicPriceIds(string $mode) : array
	{
		$permissions = \CCatalogGroup::GetGroupsPerms($this->userGroups());
		
		return $permissions[$mode] ?? [];
	}

	protected function priceRows(array $elementIds, array $priceIds): array
	{
		if (empty($elementIds) || empty($priceIds)) { return []; }

		$result = [];

		$query = Catalog\PriceTable::getList([
			'filter' => [
				'=PRODUCT_ID' => $elementIds,
				'=CATALOG_GROUP_ID' => $priceIds,
				[
					'LOGIC' => 'OR',
					['QUANTITY_FROM' => null],
					['<=QUANTITY_FROM' => 1]
				],
				[
					'LOGIC' => 'OR',
					['QUANTITY_TO' => null],
					['>=QUANTITY_TO' => 1]
				]
			],
			'select' => [
				'ID',
				'PRODUCT_ID',
				'CATALOG_GROUP_ID',
				'PRICE',
				'CURRENCY',
				'PRICE_SCALE',
			],
		]);

		while ($price = $query->fetch())
		{
			$productId = (int)$price['PRODUCT_ID'];

			if (!isset($result[$productId])) { $result[$productId] = []; }

			$result[$productId][] = $price;
		}

		return $result;
	}

	protected function preloadData(array $elements, array $parents, array $priceTypes, string $siteId, bool $needDiscount) : void
	{
		$this->initEnvironment($priceTypes, $siteId, $needDiscount);

		$this->preloadDiscountProperties($elements, $parents);
		$this->preloadDiscountCache($elements, $priceTypes);
		$this->preloadOptimalData($elements);
	}

	protected function initEnvironment(array $priceTypes, string $siteId, bool $needDiscount) : void
	{
		/** @noinspection PhpCastIsUnnecessaryInspection */
		$this->onlySaleDiscount = (string)Main\Config\Option::get('sale', 'use_sale_discount_only') === 'Y';
		$this->needDiscountCache = $needDiscount && \CIBlockPriceTools::SetCatalogDiscountCache($priceTypes, $this->userGroups(), $siteId);
	}

	protected function preloadDiscountCache(array $elements, array $priceTypes) : void
	{
		if ($this->needDiscountCache !== true) { return; }

		$elementIds = array_keys($elements);

		if ($this->onlySaleDiscount)
		{
			Catalog\Discount\DiscountManager::preloadPriceData($elementIds, $priceTypes);
			Catalog\Discount\DiscountManager::preloadProductDataToExtendOrder($elementIds, $this->userGroups());
		}
		else
		{
			\CCatalogDiscount::SetProductSectionsCache($elementIds);

			foreach ($this->groupByIblock($elements) as $iblockId => $iblockElements)
			{
				\CCatalogDiscount::SetDiscountProductCache(array_keys($iblockElements), ['IBLOCK_ID' => $iblockId, 'GET_BY_ID' => 'Y']);
			}
		}
	}

	protected function preloadOptimalData(array $elements) : void
	{
		$elementIds = array_keys($elements);

		\CIBlockElement::GetIBlockByIDList($elementIds);
		\CCatalogProduct::GetVATDataByIDList($elementIds);
	}

	protected function preloadDiscountProperties(array $elements, array $parents) : void
	{
		if ($this->needDiscountCache !== true || $this->onlySaleDiscount !== true) { return; }

		$iblockGroups = $this->groupByIblock($elements + $parents);
		$discountProperties = $this->discountUsedProperties();
		$offerLinkProperties = $this->offerLinkProperties(array_keys($iblockGroups));

		foreach ($iblockGroups as $iblockId => $iblockElements)
		{
			$elementIds = array_keys($iblockElements);
			$properties = array_fill_keys($elementIds, []);
			$needProperties = isset($discountProperties[$iblockId]) ? array_keys($discountProperties[$iblockId]) : [];

			if (!empty($offerLinkProperties[$iblockId]))
			{
				$needProperties[] = $offerLinkProperties[$iblockId];
			}

			if (!empty($needProperties))
			{
				\CIBlockElement::GetPropertyValuesArray(
					$properties,
					$iblockId,
					[ '=ID' => $elementIds ],
					[ 'ID' => $needProperties ]
				);
			}

			foreach ($properties as $elementId => $elementProperties)
			{
				Catalog\Discount\DiscountManager::setProductPropertiesCache($elementId, $elementProperties);
			}
		}
	}

	protected function groupByIblock(array $elements) : array
	{
		$result = [];

		foreach ($elements as $elementId => $element)
		{
			$iblockId = (int)$element['IBLOCK_ID'];

			if (!isset($result[$iblockId])) { $result[$iblockId] = []; }

			$result[$iblockId][$elementId] = $element;
		}

		return $result;
	}

	protected function discountUsedProperties() : array
	{
		return $this->once('discountUsedProperties', function() {
			if ($this->onlySaleDiscount !== true || !Main\Loader::includeModule('sale')) { return []; }

			$discountIds = $this->activeDiscounts();

			return $this->discountConditionFieldProperties($discountIds);
		});
	}

	protected function activeDiscounts() : array
	{
		$discountIds = [];

		$queryAvailableUserDiscounts = Sale\Internals\DiscountGroupTable::getList([
			'select' => ['DISCOUNT_ID'],
			'filter' => [
				'@GROUP_ID' => $this->userGroups(),
				'=ACTIVE' => 'Y'
			]
		]);

		while ($availableUserDiscount = $queryAvailableUserDiscounts->fetch())
		{
			$discountId = (int)$availableUserDiscount['DISCOUNT_ID'];

			if ($discountId > 0 && !isset($discountIds[$discountId]))
			{
				$discountIds[$discountId] = $discountId;
			}
		}

		return $discountIds;
	}

	protected function discountConditionFieldProperties(array $discountIds) : array
 	{
	    if (empty($discountIds)) { return []; }

	    $used = [];

	    $queryDiscountEntityList = Sale\Internals\DiscountEntitiesTable::getList([
		    'filter' => [
			    '=MODULE_ID' => 'catalog',
			    '=ENTITY' => 'ELEMENT_PROPERTY',
			    '=DISCOUNT_ID' => $discountIds,
		    ],
		    'select' => [ 'FIELD_TABLE' ],
	    ]);

	    while ($discountEntity = $queryDiscountEntityList->fetch())
	    {
		    $discountEntityFieldParts = explode(':', $discountEntity['FIELD_TABLE']);

		    if (is_array($discountEntityFieldParts) && count($discountEntityFieldParts) === 2)
		    {
			    $iblockId = (int)$discountEntityFieldParts[0];
			    $propertyId = (int)$discountEntityFieldParts[1];

			    if (!isset($used[$iblockId])) { $used[$iblockId] = []; }

			    $used[$iblockId][$propertyId] = true;
		    }
	    }

		return $used;
	}

	/** @noinspection RedundantSuppression */
	protected function offerLinkProperties(array $iblockIds) : array
	{
		$result = [];

		foreach ($iblockIds as $iblockId)
		{
			$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

			/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
			if (!isset($catalog['CATALOG_TYPE']) || $catalog['CATALOG_TYPE'] !== \CCatalogSku::TYPE_OFFERS) { continue; }

			$result[$iblockId] = $catalog['SKU_PROPERTY_ID'];
		}

		return $result;
	}

	protected function releaseData() : void
	{
		if ($this->onlySaleDiscount)
		{
			Catalog\Discount\DiscountManager::clearProductsCache();
			Catalog\Discount\DiscountManager::clearProductPricesCache();
			Catalog\Discount\DiscountManager::clearProductPropertiesCache();
		}
		else
		{
			\CCatalogDiscount::ClearDiscountCache(array(
				'PRODUCT' => true,
				'SECTIONS' => true,
				'SECTION_CHAINS' => true,
				'PROPERTIES' => true,
			));
		}
	}

	protected function elementCatalogPrice(array $element, array $prices, string $siteId, bool $needDiscount) : ?array
	{
		$vat = $this->productVat($element['ID']);
		$targetCurrency = $this->currency();
		$result = null;

		foreach ($prices as $priceRow)
		{
			if ((string)$priceRow['PRICE'] === '') { continue; }

			$priceTypeId = (int)$priceRow['CATALOG_GROUP_ID'];
			$price = (float)$priceRow['PRICE'];
			$currency = $priceRow['CURRENCY'];
			$discounts = [];

			if ($vat['VAT_INCLUDED'] !== 'Y')
			{
				$price *= (1 + $vat['RATE']);
			}

			if ($targetCurrency !== null && $targetCurrency !== $currency)
			{
				$price = \CCurrencyRates::ConvertCurrency($price, $currency, $targetCurrency);

				$currency = $targetCurrency;
			}

			if ($needDiscount)
			{
				\CCatalogDiscountSave::Disable();

				$discounts = \CCatalogDiscount::GetDiscount(
					$element['ID'],
					$element['IBLOCK_ID'],
					[$priceTypeId],
					$this->userGroups(),
					'N',
					$siteId,
					[]
				);

				\CCatalogDiscountSave::Enable();
			}

			$discountPrice = \CCatalogProduct::CountPriceWithDiscount($price, $currency, $discounts);

			if ($discountPrice === false) { continue; }

			$discountPrice = Catalog\Product\Price::roundPrice($priceTypeId, $discountPrice, $currency);
			$price = Catalog\Product\Price::roundPrice($priceTypeId, $price, $currency);

			$priceResult = [
				'PRICE' => $priceRow,
				'RESULT_PRICE' => [
					'PRICE_TYPE_ID' => $priceTypeId,
					'BASE_PRICE' => $price,
					'DISCOUNT_PRICE' => $discountPrice,
					'CURRENCY' => $currency,
				],
			];

			if (
				$result === null
				|| $result['RESULT_PRICE']['DISCOUNT_PRICE'] > $priceResult['RESULT_PRICE']['DISCOUNT_PRICE']
			)
			{
				$result = $priceResult;
			}
		}

		return $result;
	}

	protected function currency() : ?string
	{
		if (!Main\Loader::includeModule('currency')) { return null; }

		return Currency\CurrencyManager::getBaseCurrency();
	}

	protected function productVat(int $productId) : array
	{
		$vat = \CCatalogProduct::GetVATDataByID($productId);

		if (empty($vat))
		{
			return ['RATE' => 0.0, 'VAT_INCLUDED' => 'N'];
		}

		$vat['RATE'] = (float)$vat['RATE'] * 0.01;

		return $vat;
	}

	protected function elementOptimalPrice(array $element, array $prices, string $siteId, bool $needDiscount): array
	{
		/** @noinspection PhpCastIsUnnecessaryInspection */
		$previousUseDiscount = (bool)Catalog\Product\Price\Calculation::isAllowedUseDiscounts();

		if ($previousUseDiscount !== $needDiscount)
		{
			Catalog\Product\Price\Calculation::setConfig(array('USE_DISCOUNTS' => $needDiscount));
		}

		$result = \CCatalogProduct::GetOptimalPrice($element['ID'], 1, $this->userGroups(), 'N', $prices, $siteId, []);

		if ($previousUseDiscount !== $needDiscount)
		{
			Catalog\Product\Price\Calculation::setConfig(array('USE_DISCOUNTS' => $previousUseDiscount));
		}

		return $result;
	}

	protected function optimalPriceValues($price, array $select) : array
	{
		if (empty($price['RESULT_PRICE'])) { return []; }

		$map = [
			'VALUE' => 'BASE_PRICE',
			'DISCOUNT' => 'DISCOUNT_PRICE',
		];
		$result = [];

		foreach ($select as $name)
		{
			if (!isset($map[$name])) { continue; }

			$result[$name] = $price['RESULT_PRICE'][$map[$name]];
		}

		return $result;
	}

	protected function userGroups() : array
	{
		return $this->once('userGroups', function() {
			$result = Main\UserTable::getUserGroupIds(0);

			$optionGroup = Config::getOption('discounts_user_group');
			if (!empty($optionGroup) && !in_array($optionGroup, $result, false))
			{
				$result[] = $optionGroup;
			}

			return $result;
		});
	}

	public function merge(array $sourceValues, array $mergeMap) : array
	{
		return Source\Routine\ValueMerger::merge($sourceValues, $mergeMap);
	}
}