<?php
namespace Avito\Export\Feed\Source\Region;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Config;
use Avito\Export\Utils\Value;
use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Catalog;
use Avito\Export\Feed\Source\Field\Condition;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherCloneable, Source\FetcherMergable
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public const FIELD_PRICE = 'PRICE';
	public const FIELD_PRICE_DISCOUNT = 'PRICE_DISCOUNT';

	protected const PRICE_MAP = [
		self::FIELD_PRICE => 'VALUE',
		self::FIELD_PRICE_DISCOUNT => 'DISCOUNT'
	];

	public const FIELD_STORE = 'STORE';

	/** @var Source\Fetcher[] */
	protected $sources;
	protected $fetchedRegionIds;

	protected $filterConditions;

	public function __construct()
	{
		$this->sources = [
			'ELEMENT' => new Source\Element\Fetcher(),
			'PROPERTY' => new Source\ElementProperty\Fetcher(),
		];
	}

	public function listener() : Source\Listener
	{
		return new Source\NoValue\Listener();
	}

	public function title() : string
	{
		return self::getLocale('TITLE');
	}

	public function modules() : array
	{
		return [ 'iblock' ];
	}

	public function order() : int
	{
		return 600;
	}

	public function extend(array $fields, Source\Data\SourceSelect $sources, Source\Context $context) : void
	{
		$this->extendPrices($fields, $sources, $context);
		$this->extendStores($fields, $sources, $context);
	}

	public function extendPrices(array $fields, Source\Data\SourceSelect $sources, Source\Context $context) : void
	{
		$priceSelect = array_intersect_key(self::PRICE_MAP, array_flip($fields));

		if (empty($priceSelect)) { return; }

		$childContext = new Source\Context($context->regionIblockId(), $context->siteId());
		$regionPrices = $this->regionsPriceTypes($childContext);

		if (empty($regionPrices)) { return; }

		$priceTypes = array_unique(array_merge(...array_values($regionPrices)));

		foreach ($priceTypes as $priceType)
		{
			foreach ($priceSelect as $subfield)
			{
				$sources->add('PRICE', "{$priceType}_{$subfield}");
			}
		}
	}

	public function extendStores(array $fields, Source\Data\SourceSelect $sources, Source\Context $context) : void
	{
		if (!in_array(self::FIELD_STORE, $fields, true)) { return; }

		$childContext = new Source\Context($context->regionIblockId(), $context->siteId());
		$regionStores = $this->regionsStores($childContext);

		$stores = array_unique(array_merge(...array_values($regionStores)));

		foreach ($stores as $store)
		{
			$sources->add('STORE', "STORE_AMOUNT_{$store}");
		}

		$hasEmptyStores = array_reduce($regionStores, static function ($carry, $item) {
			return $carry || empty($item);
		}, false);

		if ($hasEmptyStores)
		{
			$sources->add('PRODUCT', 'QUANTITY');
		}
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		$this->filterConditions = $conditions;
		return [];
	}

	public function fields(Source\Context $context) : array
	{
		if ($context->regionIblockId() === null) { return []; }

		return $this->once('fields', function() use ($context) {
			$childContext = new Source\Context($context->regionIblockId(), $context->siteId());
			$result = [];

			foreach ($this->sources as $prefix => $source)
			{
				$childFields = $source->fields($childContext);

				foreach ($childFields as $childField)
				{
					$result[] = $childField->copy([
						'ID' => $prefix . '_' . $childField->id(),
						'FILTERABLE' => false,
					]);
				}
			}

			if (Main\ModuleManager::isModuleInstalled('catalog'))
			{
				$hasProperties = $this->hasProperties($childContext, [ $this->priceTypeCode(), $this->storeCode() ]);

				if (in_array($this->priceTypeCode(), $hasProperties, true))
				{
					$result[] = new Source\Field\NumberField([
						'ID' => static::FIELD_PRICE,
						'NAME' => self::getLocale('FIELD_PRICE'),
						'FILTERABLE' => true,
					]);
					$result[] = new Source\Field\NumberField([
						'ID' => static::FIELD_PRICE_DISCOUNT,
						'NAME' => self::getLocale('FIELD_PRICE_DISCOUNT'),
						'FILTERABLE' => true,
					]);
				}

				if (in_array($this->storeCode(), $hasProperties, true))
				{
					$result[] = new Source\Field\NumberField([
						'ID' => static::FIELD_STORE,
						'NAME' => self::getLocale('FIELD_STORE'),
						'FILTERABLE' => true,
					]);
				}
			}

			return $result;
		});
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Source\Context $context) : array
	{
		if ($context->regionIblockId() === null) { return []; }

		$priceSelect = array_intersect_key(static::PRICE_MAP, array_flip($select));
		$storeSelect = array_intersect($select, [ static::FIELD_STORE ]);
		$regionSelect = array_diff($select, array_keys($priceSelect), $storeSelect);
		$elementIds = array_keys($elements);

		$childContext = new Source\Context($context->regionIblockId(), $context->siteId());
		$regionValues = $this->regionValues($childContext, $regionSelect);
		$priceValues = $this->priceValues($elementIds, $siblings, $priceSelect, $childContext);
		$storeValues = $this->storeValues($elementIds, $siblings, $storeSelect, $childContext);

		return $this->filterValues($this->combineValues(
			array_fill_keys($elementIds, $regionValues),
			$priceValues,
			$storeValues
		));
	}

	protected function combineValues(array ...$partials) : array
	{
		$result = array_shift($partials);

		foreach ($partials as $partial)
		{
			foreach ($partial as $elementId => $regionValues)
			{
				foreach ($regionValues as $regionId => $values)
				{
					if (!isset($result[$elementId][$regionId]))
					{
						$result[$elementId][$regionId] = $values;
					}
					else
					{
						$result[$elementId][$regionId] += $values;
					}
				}
			}
		}

		return $result;
	}

	protected function priceValues(array $elementIds, array $siblings, array $priceSelect, Source\Context $context) : array
	{
		if (empty($priceSelect)) { return []; }

		$result = [];

		foreach ($this->regionsPriceTypes($context) as $regionId => $priceTypes)
		{
			foreach ($elementIds as $elementId)
			{
				foreach ($priceSelect as $field => $siblingField)
				{
					$fieldValues = [];

					foreach ($priceTypes as $priceType)
					{
						$name = "{$priceType}_{$siblingField}";
						$value = $siblings[$elementId][Source\Registry::PRICE_FIELD][$name] ?? null;

						if (Value::isEmpty($value)) { continue; }

						$fieldValues[] = (float)$value;
					}

					if (empty($fieldValues)) { continue; }

					$result[$elementId][$regionId][$field] = min($fieldValues);
				}
			}
		}

		return $result;
	}

	protected function storeValues(array $elementIds, array $siblings, array $storeSelect, Source\Context $context) : array
	{
		if (empty($storeSelect)) { return []; }

		$result = [];

		foreach ($this->regionsStores($context) as $regionId => $stores)
		{
			foreach ($elementIds as $elementId)
			{
				if (empty($stores))
				{
					$value = $siblings[$elementId][Source\Registry::CATALOG_FIELD]['QUANTITY'] ?? 0;

					$result[$elementId][$regionId][self::FIELD_STORE] = (float)$value;
				}
				else
				{
					$storesSum = 0;

					foreach ($stores as $store)
					{
						$name = "STORE_AMOUNT_{$store}";
						$value = $siblings[$elementId][Source\Registry::STORE_FIELD][$name] ?? 0;

						$storesSum += (float)$value;
					}

					$result[$elementId][$regionId][self::FIELD_STORE] = $storesSum;
				}
			}
		}

		return $result;
	}

	protected function regionValues(Source\Context $context, array $select) : array
	{
		$cacheSign = $context->iblockId() . ':' . implode('|', $select);

		return $this->once($cacheSign, function() use ($context, $select) {
			$regions = $this->regions($context, $this->splitSelect($select, 'ELEMENT'));
			$result = [];

			foreach ($this->sources as $prefix => $source)
			{
				$sourceSelect = $this->splitSelect($select, $prefix);

				if (empty($sourceSelect)) { continue; }

				$sourceValues = $source->values($regions, [], [], $sourceSelect, $context);

				foreach ($sourceValues as $regionId => $sourceData)
				{
					if (!isset($result[$regionId])) { $result[$regionId] = []; }

					foreach ($sourceData as $sourceCode => $sourceValue)
					{
						$fieldName = $prefix . '_' . $sourceCode;

						$result[$regionId][$fieldName] = $sourceValue;
					}
				}
			}

			return $result;
		});
	}

	protected function regions(Source\Context $context, array $select = []) : array
	{
		if ($this->fetchedRegionIds !== null && empty($this->fetchedRegionIds)) { return []; }

		$result = [];
		$select = array_merge(['ID'], $select);

		$query = \CIBlockElement::GetList(
			[
				'SORT' => 'ASC',
				'ID' => 'ASC',
			],
			$this->regionFilter($context),
			false,
			[ 'nTopCount' => 100 ],
			$select
		);

		while ($row = $query->Fetch())
		{
			$result[$row['ID']] = $row;
		}

		$this->fetchedRegionIds = array_keys($result);

		return $result;
	}

	protected function regionFilter(Source\Context $context) : array
	{
		if ($this->fetchedRegionIds !== null)
		{
			return [
				'IBLOCK_ID' => $context->iblockId(),
				'=ID' => $this->fetchedRegionIds,
			];
		}

		$filter = [
			'IBLOCK_ID' => $context->iblockId(),
			'ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
		];

		if ($this->hasAvitoExportProperty($context))
		{
			$filter['!=PROPERTY_AVITO_EXPORT'] = false;
		}

		return $filter;
	}

	protected function splitSelect(array $select, string $prefix) : array
	{
		$result = [];
		$prefixLength = mb_strlen($prefix . '_');

		foreach ($select as $selectCode)
		{
			if (mb_strpos($selectCode, $prefix . '_') !== 0) { continue; }

			$result[] = mb_substr($selectCode, $prefixLength);
		}

		return $result;
	}

	protected function hasAvitoExportProperty(Source\Context $context) : bool
	{
		$iterator = Iblock\PropertyTable::getList([
			'select' => [ 'ID' ],
			'filter' => [
				'=IBLOCK_ID' => $context->iblockId(),
				'=CODE' => 'AVITO_EXPORT'
			],
			'limit' => 1,
		]);

		return (bool)$iterator->fetch();
	}

	protected function hasProperties(Source\Context $context, array $propertyCodes) : array
	{
		$properties = Iblock\PropertyTable::getList([
			'select' => ['ID', 'CODE'],
			'filter' => [
				'=IBLOCK_ID' => $context->iblockId(),
				'@CODE' => $propertyCodes,
				'=ACTIVE' => true
			]
		])->fetchAll();

		return array_column($properties, 'CODE');
	}

	protected function regionsPriceTypes(Source\Context $context)
	{
		$cacheSign = 'regionsPriceTypes:' . $context->iblockId();

		return $this->once($cacheSign, function() use ($context) {
			$regionIds = array_keys($this->regions($context));
			$priceProperties = $this->propertyValues($regionIds, $context, $this->priceTypeCode());
			$propertyMap = $this->mapPriceProperties($priceProperties);

			return $this->combinePropertyValues($priceProperties, $propertyMap, $this->basePriceType());
		});
	}

	protected function regionsStores(Source\Context $context)
	{
		$cacheSign = 'regionsStores:' . $context->iblockId();

		return $this->once($cacheSign, function() use ($context) {
			$regionIds = array_keys($this->regions($context));
			$storeProperties = $this->propertyValues($regionIds, $context, $this->storeCode());
			$propertyMap = $this->mapStoreProperties($storeProperties);

			return $this->combinePropertyValues($storeProperties, $propertyMap);
		});
	}

	protected function propertyValues(array $regionIds, Source\Context $context, string $code) : array
	{
		if (empty($regionIds)) { return []; }

		$properties = [];

		\CIBlockElement::GetPropertyValuesArray($properties, $context->iblockId(), [ 'ID' => $regionIds ], [
			'CODE' => [ $code ],
		]);

		$result = array_fill_keys($regionIds, []);

		foreach ($properties as $regionId => $values)
		{
			if (empty($values[$code]['VALUE'])) { continue; }

			$result[$regionId] = (array)$values[$code]['VALUE'];
		}

		return $result;
	}

	protected function combinePropertyValues(array $regionProperties, array $propertyMap, $defaultValue = null) : array
	{
		$result = [];

		foreach ($regionProperties as $regionId => $values)
		{
			$item = [];

			foreach ($values as $value)
			{
				if (!isset($propertyMap[$value])) { continue; }

				$item[] = (int)$propertyMap[$value];
			}

			if (empty($item) && $defaultValue !== null)
			{
				$item[] = $defaultValue;
			}

			$result[$regionId] = $item;
		}

		return $result;
	}

	protected function mapPriceProperties(array $priceProperties) : array
	{
		if (empty($priceProperties) || !Main\Loader::includeModule('catalog')) { return []; }

		$values = array_merge(...array_values($priceProperties));
		$field = $this->priceTypeField();

		$query = Catalog\GroupTable::getList([
			'filter' => [ "=$field" => array_unique($values) ],
			'select' => [ 'ID', $field ]
		]);

		return array_column($query->fetchAll(), 'ID', $field);
	}

	protected function mapStoreProperties(array $storeProperties) : array
	{
		if (empty($storeProperties) || !Main\Loader::includeModule('catalog')) { return []; }

		$values = array_merge(...array_values($storeProperties));
		$field = $this->storeField();

		$query = Catalog\StoreTable::getList([
			'filter' => [ "=$field" => array_unique($values) ],
			'select' => [ 'ID', $field ]
		]);

		return array_column($query->fetchAll(), 'ID', $field);
	}

	protected function filterValues(array $values) : array
	{
		$conditions = $this->filterConditions;

		if (empty($conditions)) { return $values; }


		foreach ($values as $offerId => $regions)
		{
			foreach ($regions as $regionId => $regionValues)
			{
				foreach ($conditions as $condition)
				{
					$value = (float)($regionValues[$condition['FIELD']] ?? 0);

					if (!$this->compareValue($condition['COMPARE'], $value, $condition['VALUE']))
					{
						$values[$offerId][$regionId] = [];
					}
				}
			}
		}

		return $values;
	}

	protected function compareValue(string $condition, float $value, $compareWith) : bool
	{
		if (is_array($compareWith))
		{
			$compareWith = array_map(static function($value) {
				return (float)$value;
			}, $compareWith);
		}
		else
		{
			$compareWith = (float)$compareWith;
		}

		switch ($condition)
		{
			case Condition::EQUAL:          return $value === $compareWith;
			case Condition::NOT_EQUAL:      return $value !== $compareWith;
			case Condition::MORE_THEN:      return $value > $compareWith;
			case Condition::LESS_THEN:      return $value < $compareWith;
			case Condition::LESS_OR_EQUAL:  return $value <= $compareWith;
			case Condition::MORE_OR_EQUAL:  return $value >= $compareWith;
			case Condition::AT_LIST:        return in_array($value, $compareWith, true);
			case Condition::NOT_AT_LIST:    return !in_array($value, $compareWith, true);
		}
		return true;
	}

	protected function basePriceType() : ?int
	{
		if (!Main\Loader::includeModule('catalog')) { return null; }

		return \CCatalogGroup::GetBaseGroup()['ID'] ?? null;
	}

	protected function priceTypeCode() : string
	{
		return Config::getOption('region_price_code', 'AVITO_PRICE_TYPE');
	}

	protected function priceTypeField() : string
	{
		$allowedFields = ['ID', 'NAME', 'XML_ID'];
		$field = Config::getOption('region_price_field', 'NAME');

		return in_array($field, $allowedFields, true) ? $field : 'NAME';
	}

	protected function storeCode() : string
	{
		return Config::getOption('region_store_code', 'AVITO_CATALOG_STORE');
	}

	protected function storeField() : string
	{
		$allowedFields = ['ID', 'CODE', 'XML_ID'];
		$field = Config::getOption('region_store_field', 'CODE');

		return in_array($field, $allowedFields, true) ? $field : 'CODE';
	}

	public function merge(array $sourceValues, array $mergeMap) : array
	{
		$result = array_diff_key($sourceValues, $mergeMap);
		$from = $this->mergeFrom($sourceValues, $mergeMap);

		return $this->mergeApply($result, $from);
	}

	protected function mergeFrom(array $sourceValues, array $mergeMap) : array
	{
		$result = [];
		$supported = [
			self::FIELD_PRICE,
			self::FIELD_PRICE_DISCOUNT,
			self::FIELD_STORE,
		];

		foreach ($mergeMap as $from => $to)
		{
			if (!isset($sourceValues[$from])) { continue; }

			foreach ($sourceValues[$from] as $regionId => $regionValues)
			{
				foreach ($supported as $name)
				{
					if (!isset($regionValues[$name]) || !is_numeric($regionValues[$name])) { continue; }

					$result[$to][$regionId][$name][] = $regionValues[$name];
				}
			}
		}

		return $result;
	}

	protected function mergeApply(array $sourceValues, array $from) : array
	{
		foreach ($from as $to => $regions)
		{
			foreach ($regions as $regionId => $regionValues)
			{
				foreach ($regionValues as $field => $valuesGroup)
				{
					if (isset($sourceValues[$to][$regionId][$field]) && is_numeric($sourceValues[$to][$regionId][$field]))
					{
						array_unshift($valuesGroup, $sourceValues[$to][$regionId][$field]);
					}

					if ($field === self::FIELD_STORE)
					{
						$sourceValues[$to][$regionId][$field] = array_sum($valuesGroup);
					}
					else
					{
						$valuesGroup = array_unique($valuesGroup);

						$sourceValues[$to][$regionId][$field] = count($valuesGroup) === 1 ? reset($valuesGroup) : $valuesGroup;
					}
				}
			}
		}

		return $sourceValues;
	}
}