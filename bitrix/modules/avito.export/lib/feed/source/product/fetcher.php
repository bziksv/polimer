<?php
namespace Avito\Export\Feed\Source\Product;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source;
use Avito\Export\Feed\Source\Context;
use Bitrix\Catalog;

class Fetcher extends Source\FetcherSkeleton
	implements Source\FetcherMergable
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

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
		return $this->once('fields', function() {
			return [
				new AvailableField([
					'ID' => 'AVAILABLE',
					'NAME' => self::getLocale('FIELD_AVAILABLE'),
				]),
				new Source\Field\NumberField([
					'ID' => 'QUANTITY',
					'NAME' => self::getLocale('FIELD_QUANTITY'),
				]),
				new Source\Field\NumberField([
					'ID' => 'WIDTH',
					'NAME' => self::getLocale('FIELD_WIDTH'),
				]),
				new Source\Field\NumberField([
					'ID' => 'HEIGHT',
					'NAME' => self::getLocale('FIELD_HEIGHT'),
				]),
				new Source\Field\NumberField([
					'ID' => 'LENGTH',
					'NAME' => self::getLocale('FIELD_LENGTH'),
				]),
				new Source\Field\NumberField([
					'ID' => 'WEIGHT',
					'NAME' => self::getLocale('FIELD_WEIGHT'),
				]),
				new Source\Field\NumberField([
					'ID' => 'MEASURE_RATIO',
					'NAME' => self::getLocale('FIELD_MEASURE_RATIO'),
					'FILTERABLE' => false,
				]),
				new Source\Field\EnumField([
					'ID' => 'TYPE',
					'NAME' => self::getLocale('FIELD_TYPE'),
					'VARIANTS' => array_filter(array_map(function (string $type) {
						$constant = $this->typeConstant($type);

						if ($constant === null) { return null; }

						return [
							'ID' => $constant,
							'VALUE' => self::getLocale('FIELD_TYPE_' . $type),
						];
					}, [
						'PRODUCT',
						'SET',
						'OFFER',
						'SERVICE',
					])),
				]),
				new MeasureField([
					'ID' => 'MEASURE',
					'NAME' => self::getLocale('FIELD_MEASURE'),
				]),
			];
		});
	}

	public function filter(array $conditions, Source\Context $context) : array
	{
		$typeConditions = array_filter($conditions, static function(array $condition) { return $condition['FIELD'] === 'TYPE'; });
		$otherConditions = array_diff_key($conditions, $typeConditions);

		return $this->typeFilter($typeConditions, $context) + $this->commonFilter($otherConditions, $context);
	}

	protected function typeFilter(array $conditions, Source\Context $context) : array
	{
		if (empty($conditions)) { return []; }

		$filter = Source\Routine\QueryFilter::make($conditions, $this->fields($context));
		$type = 'CATALOG';

		foreach ($filter as $field => $values)
		{
			if (!is_array($values)) { continue; }

			$inverse = (mb_strpos($field, '!') === 0);
			$elementValues = array_diff($values, [ $this->typeConstant('OFFER') ]);
			$offerValues = array_intersect($values, [ $this->typeConstant('OFFER') ]);

			if (!empty($elementValues) && !empty($offerValues)) { break; }

			if (!empty($elementValues))
			{
				$type = $inverse ? 'OFFER' : 'ELEMENT';
			}
			else
			{
				$type = $inverse ? 'ELEMENT' : 'OFFER';
			}
		}

		return [
			$type => $filter,
		];
	}

	protected function typeConstant(string $name) : ?int
	{
		$newConstant = Catalog\ProductTable::class . '::TYPE_' . $name;

		if (defined($newConstant)) { return constant($newConstant); }

		$oldConstant = \CCatalogProduct::class . '::TYPE_' . $name;

		if (defined($oldConstant)) { return constant($oldConstant); }

		return null;
	}

	protected function commonFilter(array $conditions, Source\Context $context) : array
	{
		if (empty($conditions)) { return []; }

		return [
			'CATALOG' => Source\Routine\QueryFilter::make($conditions, $this->fields($context)),
		];
	}

	public function values(array $elements, array $parents, array $siblings, array $select, Context $context) : array
	{
		if (in_array('TRACE_QUANTITY', $select, true))
		{
			$select = array_unique(array_merge($select, [
				'QUANTITY_TRACE',
				'CAN_BUY_ZERO',
				'QUANTITY',
			]));
		}

		$productSelect = array_diff($select, [
			'MEASURE_RATIO',
			'TRACE_QUANTITY',
		]);
		$elementIds = array_keys($elements);

		$result = $this->productValues($elementIds, $productSelect);
		$result = $this->measureRatioValues($elementIds, $select, $result);
		$result = $this->traceQuantityValues($elementIds, $select, $result);

		return $result;
	}

	public function productValues(array $elementIds, $select) : array
	{
		if (empty($select)) { return []; }

		$selectMap = array_flip($select);

		$query = Catalog\ProductTable::getList([
			'select' => array_merge(['ID'], $select),
			'filter' => [ '=ID' => $elementIds ],
		]);

		$result = [];

		while ($row = $query->fetch())
		{
			$result[$row['ID']] = array_intersect_key($row, $selectMap);
		}

		return $result;
	}

	protected function measureRatioValues(array $elementIds, array $select, array $result) : array
	{
		if (!in_array('MEASURE_RATIO', $select, true)) { return $result; }

		$ratioList = Catalog\MeasureRatioTable::getCurrentRatio($elementIds);

		foreach ($elementIds as $id)
		{
			$result[$id]['MEASURE_RATIO'] = $ratioList[$id];
		}

		return $result;
	}

	protected function traceQuantityValues(array $elementIds, array $select, array $result) : array
	{
		if (!in_array('TRACE_QUANTITY', $select, true)) { return $result; }

		foreach ($elementIds as $id)
		{
			$element = $result[$id];

			if (!isset($element['QUANTITY'], $element['QUANTITY_TRACE'], $element['CAN_BUY_ZERO'])) { continue; }

			if ($element['QUANTITY_TRACE'] === 'Y' && $element['CAN_BUY_ZERO'] === 'N')
			{
				$result[$id]['TRACE_QUANTITY'] = $element['QUANTITY'];
			}
		}

		return $result;
	}

	public function merge(array $sourceValues, array $mergeMap) : array
	{
		$result = Source\Routine\ValueMerger::merge($sourceValues, $mergeMap);
		$result = Source\Routine\ValueMerger::toBoolean($result, [ 'AVAILABLE' ]);
		$result = Source\Routine\ValueMerger::toSum($result, [ 'QUANTITY', 'TRACE_QUANTITY' ]);

		return $result;
	}
}