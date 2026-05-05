<?php
namespace Avito\Export\Feed\Source\Routine\QueryBuilder;

use Bitrix\Catalog;
use Avito\Export\Feed\Setup;
use Avito\Export\Feed\Source;

class Filter
{
	protected $fetcherPool;
	protected $used = [];

	public function __construct(Source\FetcherPool $fetcherPool)
	{
		$this->fetcherPool = $fetcherPool;
	}

	public function compile(Setup\FilterMap $filterMap, Source\Context $context, array $changes = null) : array
	{
		[$common, $additional] = $this->group($filterMap, $context, $changes);

		return $this->build($context, $common, $additional);
	}

	protected function group(Setup\FilterMap $filterMap, Source\Context $context, array $changes = null) : array
	{
		$common = [];
		$additional = [];

		foreach ($filterMap->groups() as $group)
		{
			$groupPartials = [];

			foreach ($group['ITEMS'] as $type => $conditions)
			{
				$this->used[$type] = ($this->used[$type] ?? []) + array_column($conditions, 'FIELD', 'FIELD');

				$fetcher = $this->fetcherPool->some($type);

				foreach ($fetcher->filter($conditions, $context) as $elementType => $elementFilter)
				{
					if (!isset($groupPartials[$elementType]))
					{
						$groupPartials[$elementType] = $elementFilter;
					}
					else
					{
						$groupPartials[$elementType] = array_merge(
							$groupPartials[$elementType],
							$elementFilter
						);
					}
				}
			}

			if ($group['LOGIC'] === 'OR')
			{
				$additional[] = $groupPartials;
			}
			else
			{
				foreach ($groupPartials as $elementType => $groupPartial)
				{
					if (isset($common[$elementType]))
					{
						$common[$elementType] = array_merge(
							$common[$elementType],
							$groupPartial
						);
					}
					else
					{
						$common[$elementType] = $groupPartial;
					}
				}
			}
		}

		$common = $this->applyChanges($common, $changes);

		return [$common, $additional];
	}

	protected function applyChanges(array $queryFilter, array $changes = null) : array
	{
		if ($changes === null) { return $queryFilter; }

		foreach ($changes as $entityType => $entityFilter)
		{
			if (!isset($queryFilter[$entityType]))
			{
				$queryFilter[$entityType] = $entityFilter;
			}
			else
			{
				$queryFilter[$entityType][] = $entityFilter;
			}
		}

		return $queryFilter;
	}

	protected function build(Source\Context $context, array $filter, array $additional = []): array
	{
		$splitFilters = null;
		$defaultFilter = array_filter([
			'=ACTIVE' => 'Y',
			'=ACTIVE_DATE' => 'Y',
			'=AVAILABLE' => $context->hasCatalog() && !isset($this->used[Source\Registry::CATALOG_FIELD]['AVAILABLE']) ? 'Y' : null,
		]);

		$filter['ELEMENT'] = array_merge(
			[ 'IBLOCK_ID' => $context->iblockId() ],
			$defaultFilter,
			$filter['ELEMENT'] ?? []
		);

		if ($context->hasOffers())
		{
			$offerFilter = $filter['OFFER'] ?? [];
			$catalogFilter = $filter['CATALOG'] ?? [];

			$filter['OFFER'] = array_merge(
				[ 'IBLOCK_ID' => $context->offerIblockId() ],
				$defaultFilter,
				$offerFilter
			);

			if (!empty($offerFilter) || $this->hasAdditionalRequired($additional, 'OFFER'))
			{
				$filter['OFFER'] = array_merge($filter['OFFER'], $catalogFilter);
				$splitFilters = [];

				foreach ($this->groupAdditional($additional, [ 'CATALOG' => 'OFFER' ]) as $additionalGroup)
				{
					$oneFilter = $filter;

					foreach ($additionalGroup as $additionalType => $additionalPart)
					{
						$oneFilter[$additionalType][] = $additionalPart;
					}

					$oneFilter['ELEMENT'][] = [
						'ID' => \CIBlockElement::SubQuery('PROPERTY_' . $context->offerPropertyId(), $oneFilter['OFFER']),
					];

					$splitFilters[] = $oneFilter;
				}
			}
			else if (!empty($catalogFilter) && $this->canMergeCatalogFilter($catalogFilter))
			{
				$filter['ELEMENT'] = array_merge($filter['ELEMENT'], $catalogFilter);
				$filter['OFFER'] = array_merge($filter['OFFER'], $catalogFilter);
			}
			else if (!empty($catalogFilter) || $this->hasAdditionalSomeone($additional, 'CATALOG'))
			{
				$splitFilters = [];

				// simple product filter

				foreach ($this->groupAdditional($additional, [ 'CATALOG' => 'ELEMENT' ], [ 'OFFER' ]) as $additionalGroup)
				{
					$simpleFilter = $filter;
					$simpleFilter['ELEMENT'] = array_merge($filter['ELEMENT'], $catalogFilter, [
						'!=TYPE' => Catalog\ProductTable::TYPE_SKU,
					]);

					foreach ($additionalGroup as $additionalType => $additionalPart)
					{
						$simpleFilter[$additionalType][] = $additionalPart;
					}

					$splitFilters[] = $simpleFilter;
				}

				// offer filter

				foreach ($this->groupAdditional($additional, [ 'CATALOG' => 'OFFER' ]) as $additionalGroup)
				{
					$skuFilter = $filter;
					$skuFilter['OFFER'] = array_merge($skuFilter['OFFER'], $catalogFilter);
					$skuFilter['ELEMENT'][] = [ '=TYPE' => Catalog\ProductTable::TYPE_SKU ];

					foreach ($additionalGroup as $additionalType => $additionalPart)
					{
						$skuFilter[$additionalType][] = $additionalPart;
					}

					$skuFilter['ELEMENT'][] = [
						'ID' => \CIBlockElement::SubQuery('PROPERTY_' . $context->offerPropertyId(), $skuFilter['OFFER']),
					];

					$splitFilters[] = $skuFilter;
				}
			}
		}
		else if (!empty($filter['CATALOG']))
		{
			$filter['ELEMENT'] = array_merge(
				$filter['ELEMENT'],
				$filter['CATALOG']
			);
		}

		if ($splitFilters === null)
		{
			$splitFilters = [];

			foreach ($this->groupAdditional($additional, [ 'CATALOG' => 'ELEMENT' ]) as $additionalGroup)
			{
				$oneFilter = $filter;

				foreach ($additionalGroup as $additionalType => $additionalPart)
				{
					$oneFilter[$additionalType][] = $additionalPart;
				}

				if (!empty($additionalGroup['OFFER']))
				{
					$oneFilter['ELEMENT'][] = [
						'ID' => \CIBlockElement::SubQuery('PROPERTY_' . $context->offerPropertyId(), $oneFilter['OFFER']),
					];
				}

				$splitFilters[] = $oneFilter;
			}
		}

		return $splitFilters;
	}

	protected function canMergeCatalogFilter(array $filter) : bool
	{
		return (
			isset($filter['=AVAILABLE'])
			&& $filter['=AVAILABLE'] === 'Y'
			&& count($filter) === 1
		);
	}

	protected function groupAdditional(array $additional, array $overrides = [], array $exclude = []) : array
	{
		$result = [ 0 => [] ];

		foreach ($additional as $filter)
		{
			$filterGroup = [];

			foreach ($filter as $type => $conditions)
			{
				if (in_array($type, $exclude, true)) { continue; }
				if (isset($overrides[$type])) { $type = $overrides[$type]; }

				if (!isset($filterGroup[$type]))
				{
					$filterGroup[$type] = $conditions;
				}
				else
				{
					$filterGroup[$type] = array_merge($filterGroup[$type], $conditions);
				}

				if (!isset($filterGroup[$type]['LOGIC']) && count($filterGroup[$type]) > 1)
				{
					$filterGroup[$type] = [ 'LOGIC' => 'OR' ] + $filterGroup[$type];
				}
			}

			$newResult = [];

			foreach ($filterGroup as $type => $conditions)
			{
				foreach ($result as $parentFilter)
				{
					if (!isset($parentFilter[$type]))
					{
						$parentFilter[$type] = [];
					}

					$parentFilter[$type][] = $conditions;

					$newResult[] = $parentFilter;
				}
			}

			$result = $newResult;
		}

		return $result;
	}

	protected function hasAdditionalRequired(array $additional, string $type) : bool
	{
		$result = false;

		foreach ($additional as $filter)
		{
			$targetFilter = array_intersect_key($filter, [ $type => true ]);
			$siblingFilter = array_diff_key($filter, [ $type => true ]);

			if (!empty($targetFilter) && empty($siblingFilter))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function hasAdditionalSomeone(array $additional, string $type) : bool
	{
		$result = false;

		foreach ($additional as $filter)
		{
			if (!empty($filter[$type]))
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}