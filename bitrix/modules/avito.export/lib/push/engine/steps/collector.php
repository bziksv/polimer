<?php
namespace Avito\Export\Push\Engine\Steps;

use Bitrix\Main;
use Avito\Export\Config;
use Avito\Export\DB;
use Avito\Export\Feed;
use Avito\Export\Push;
use Avito\Export\Watcher;
use Avito\Export\Logger;
use Avito\Export\Glossary;

class Collector extends Step
{
	public const TYPE = 'collector';

	protected $fetcherPool;

	public function __construct(Push\Engine\Controller $controller)
	{
		parent::__construct($controller);
		$this->fetcherPool = new Feed\Source\FetcherPool();
	}

	public function getName() : string
	{
		return static::TYPE;
	}

	public function start(string $action, $offset = null) : void
	{
		$feed = $this->controller->getSetup()->getExchange()->fillFeed();

		if ($feed === null) { return; }

		$settings = $this->controller->getSetup()->getSettings();
		$fieldMapCollection = $settings->fieldMapCollection($feed);
		$changesFilter = null;

		if ($action === Watcher\Engine\Controller::ACTION_CHANGE)
		{
			$changesFilter = $this->feedChangesFilter();

			if ($changesFilter === null) { return; }
		}

		do
		{
			[$feedOffers, $offset, $hasNext] = $this->feedOffers($changesFilter, $offset);

			foreach ($this->groupByIblock($feedOffers) as $iblockId => $iblockOffers)
			{
				$context = $this->getFeed()->iblockContext($iblockId);
				$fieldMap = $fieldMapCollection->byIblockId($iblockId);
				$tagSources = $fieldMap->select();
				$queryBuilder = new Feed\Source\Routine\QueryBuilder($this->fetcherPool);

				$queryBuilder->bootSources($tagSources, $context);

				$mergedOffers = $this->mergedOffers($iblockOffers);
				$mergedOffers = $this->cloneMerged($mergedOffers, $iblockOffers);
				$allOffers = array_merge($iblockOffers, $mergedOffers);

				[$elements, $parents] = $this->emulateElements($allOffers, $context);
				$sourceValues = $this->fetchValues($queryBuilder, $tagSources, $elements, $parents, $context, $allOffers);
				$sourceValues = $this->cloneValues($sourceValues, $allOffers);

				$targetValues = $this->collectValues($fieldMap, $sourceValues);
				$avitoIds = $this->collectAvitoIds($fieldMap, $sourceValues);
                $targetValues = $this->extendValues($targetValues, $context);

				$this->writeAvitoIds($iblockOffers, $avitoIds);
				$this->writeStamp($iblockOffers, $targetValues);
			}
		}
		while ($hasNext);
	}

	public function afterChange() : void
	{
		$changesFilter = $this->storageChangesFilter();

		if ($changesFilter === null) { return; }

		$this->resetByFilter([
			'filter' => array_merge(
				[ '=PUSH_ID' => $this->getPush()->getId() ],
				$changesFilter,
				[ '<TIMESTAMP_X' => $this->getParameter('INIT_TIME') ]
 		    ),
		]);
	}

	public function afterRefresh() : void
	{
		$this->resetByFilter([
			'filter' => [
				'=PUSH_ID' => $this->getPush()->getId(),
				'<TIMESTAMP_X' => $this->getParameter('INIT_TIME'),
			],
		]);
	}

	protected function resetByFilter(array $filter) : void
	{
		$this->deleteUnsubmitted($filter);
		$this->resetUnchanged($filter);
	}

	protected function deleteUnsubmitted(array $filter) : void
	{
		$previousRows = null;
		$loopLimit = 500;
		$loopIndex = 0;
		$filter['filter'][] = [
			'LOGIC' => 'OR',
			[ '=STATUS' => Stamp\RepositoryTable::STATUS_FAILED ],
			[ '=STATUS' => Stamp\RepositoryTable::STATUS_UNPUBLISHED ],
			[
				'=STATUS' => Stamp\RepositoryTable::STATUS_READY,
				'=VALUE' => Stamp\RepositoryTable::VALUE_NULL,
			],
			[ '=TYPE' => Glossary::ENTITY_PRICE ], // no way to reset price
			[ '=SERVICE_PRIMARY.SERVICE_ID' => false ],
			[ '=SERVICE_PRIMARY.SERVICE_ID' => PrimaryMap\RepositoryTable::SERVICE_ID_NULL ],
		];

		do
		{
			$query = Stamp\RepositoryTable::getList($filter + [
				'select' => [ 'ELEMENT_ID', 'REGION_ID', 'TYPE' ],
				'limit' => $loopLimit,
			]);
			$rows = $query->fetchAll();

			if (++$loopIndex > 1000 || $previousRows === $rows)
			{
				throw new Main\SystemException('infinite loop on delete unsubmitted');
			}

			$this->deleteUnsubmittedRows($rows);
			$this->deleteUnsubmittedLogs($rows);

			$previousRows = $rows;
		}
		while (count($rows) >= $loopLimit);
	}

	protected function deleteUnsubmittedRows(array $rows) : void
	{
		$typeRegionGroups = [];
		$filterPartials = [];
		$filter = [
			'=PUSH_ID' => $this->getPush()->getId(),
		];

		foreach ($rows as $row)
		{
			$typeRegionKey = $row['REGION_ID'] . ':' . $row['TYPE'];

			if (!isset($typeRegionGroups[$typeRegionKey]))
			{
				$typeRegionGroups[$typeRegionKey] = [
					'REGION_ID' => $row['REGION_ID'],
					'TYPE' => $row['TYPE'],
					'ELEMENT_ID' => [],
				];
			}

			$typeRegionGroups[$typeRegionKey]['ELEMENT_ID'][] = $row['ELEMENT_ID'];
		}

		foreach ($typeRegionGroups as $typeRegionGroup)
		{
			$filterPartials[] = [
				'=ELEMENT_ID' => $typeRegionGroup['ELEMENT_ID'],
				'=REGION_ID' => $typeRegionGroup['REGION_ID'],
				'=TYPE' => $typeRegionGroup['TYPE'],
			];
		}

		if (empty($filterPartials)) { return; }

		if (count($filterPartials) === 1)
		{
			$filter[] = reset($filterPartials);
		}
		else
		{
			$filter[] = [ 'LOGIC' => 'OR' ] + $filterPartials;
		}

		$batch = new DB\Facade\BatchDelete(Stamp\RepositoryTable::class);
		$batch->run([ 'filter' => $filter ]);
	}

	protected function deleteUnsubmittedLogs(array $rows) : void
	{
		$groups = [];

		foreach ($rows as $row)
		{
			if (!isset($groups[$row['TYPE']])) { $groups[$row['TYPE']] = []; }

			$groups[$row['TYPE']][] = [
				'ENTITY_ID' => $row['ELEMENT_ID'],
				'REGION_ID' => $row['REGION_ID'],
			];
		}

		if (empty($groups)) { return; }

		$logger = new Logger\Logger(Glossary::SERVICE_PUSH, $this->controller->getSetup()->getId());
		$logger->allowDelete();

		foreach ($groups as $type => $ids)
		{
			$logger->used($type, $ids);
		}

		$logger->flush();
	}

	protected function resetUnchanged(array $filter) : void
	{
		$batch = new DB\Facade\BatchUpdate(Stamp\RepositoryTable::class);
		$batch->run($filter, [
			'VALUE' => Stamp\RepositoryTable::VALUE_NULL,
			'STATUS' => Stamp\RepositoryTable::STATUS_DELETE,
			'REPEAT' => 0,
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		]);
	}

	protected function feedOffers(array $changesFilter = null, $offset = null) : array
	{
		$filter = [
			'=FEED_ID' => $this->getFeed()->getId(),
		];

		if ($offset !== null)
		{
			$filter['>ELEMENT_ID'] = (int)$offset;
		}

		if ($changesFilter !== null)
		{
			$filter = array_merge($filter, $changesFilter);
		}

		$filter['!=STATUS'] = Feed\Engine\Steps\Offer\Table::STATUS_FAIL;

		$query = Feed\Engine\Steps\Offer\Table::getList([
			'select' => [ 'ELEMENT_ID', 'PARENT_ID', 'REGION_ID', 'PRIMARY', 'IBLOCK_ID' ],
			'filter' => $filter,
			'order' => [ 'ELEMENT_ID' => 'ASC' ],
			'limit' => max(1, (int)Config::getOption('push_collect_limit', 500)),
		]);

		$elements = $query->fetchAll();
		$hasNext = false;

		if (!empty($elements))
		{
			$last = end($elements);
			$hasNext = true;
			$offset = $last['ELEMENT_ID'];
		}

		return [$elements, $offset, $hasNext];
	}

	protected function feedChangesFilter() : ?array
	{
		$changes = $this->getParameter('CHANGES');
		$offerChanges = $changes[Feed\Engine\Steps\Offer::TYPE] ?? null;

		if (empty($offerChanges) || !is_array($offerChanges)) { return null; }

		$query = Feed\Engine\Steps\Offer\Table::getList([
			'filter' => [
				'=FEED_ID' => $this->getFeed()->getId(),
				'=PARENT_ID' => $offerChanges,
			],
			'select' => [ 'ELEMENT_ID', 'PARENT_ID' ],
		]);

		$rows = $query->fetchAll();
		$existsParents = array_column($rows, 'PARENT_ID', 'PARENT_ID');

		if (empty($existsParents))
		{
			return [ '=ELEMENT_ID' => $offerChanges ];
		}

		$aloneElements = array_diff($offerChanges, $existsParents);
		$aloneElements = array_diff($aloneElements, array_column($rows, 'ELEMENT_ID'));

		if (empty($aloneElements))
		{
			return [ '=PARENT_ID' => array_values($existsParents) ];
		}

		return [
			[
				'LOGIC' => 'OR',
				[ '=ELEMENT_ID' => $aloneElements ],
				[ '=PARENT_ID' => array_values($existsParents) ],
			],
		];
	}

	protected function storageChangesFilter() : ?array
	{
		$changes = $this->getParameter('CHANGES');
		$offerChanges = $changes[Feed\Engine\Steps\Offer::TYPE] ?? null;

		if (empty($offerChanges) || !is_array($offerChanges)) { return null; }

		$query = Feed\Engine\Steps\Offer\Table::getList([
			'filter' => [
				'=FEED_ID' => $this->getFeed()->getId(),
				'=PARENT_ID' => $offerChanges,
			],
			'select' => [ 'PARENT_ID', 'ELEMENT_ID' ],
		]);

		$rows = $query->fetchAll();
		$changesMap = array_flip($offerChanges);
		$changesMap = array_diff_key($changesMap, array_column($rows, 'PARENT_ID', 'PARENT_ID'));
		$changesMap += array_column($rows, 'ELEMENT_ID', 'ELEMENT_ID');

		return [
			'=ELEMENT_ID' => array_keys($changesMap),
		];
	}

	protected function mergedOffers(array $iblockOffers) : array
	{
		$offerIds = array_column($iblockOffers, 'ELEMENT_ID');

		if (empty($offerIds)) { return []; }

		$query = Feed\Engine\Steps\Offer\Table::getList([
			'select' => [ 'ELEMENT_ID', 'PARENT_ID', 'REGION_ID', 'PRIMARY', 'IBLOCK_ID', 'MERGED_ID' ],
			'filter' => [
				'=FEED_ID' => $this->getFeed()->getId(),
				'=MERGED_ID' => $offerIds,
			],
			'order' => [ 'ELEMENT_ID' => 'ASC' ],
		]);

		return $query->fetchAll();
	}

	protected function cloneMerged(array $mergedOffers, array $iblockOffers) : array
	{
		if (empty($mergedOffers)) { return []; }

		$regionsMap = [];

		foreach ($iblockOffers as $offer)
		{
			if ((int)$offer['ELEMENT_ID'] > 0 && (int)$offer['REGION_ID'] > 0)
			{
				$regionsMap[$offer['ELEMENT_ID']][] = $offer['REGION_ID'];
			}
		}

		if (empty($regionsMap)) { return $mergedOffers; }

		$result = [];

		foreach ($mergedOffers as $mergedOffer)
		{
			if (empty($regionsMap[$mergedOffer['MERGED_ID']]))
			{
				$result[] = $mergedOffer;
			}
			else
			{
				foreach ($regionsMap[$mergedOffer['MERGED_ID']] as $regionId)
				{
					$result[] = [ 'REGION_ID' => $regionId ] + $mergedOffer;
				}
			}
		}

		return $result;
	}

	protected function groupByIblock(array $elements) : array
	{
		$result = [];

		foreach ($elements as $element)
		{
			$iblockId = $element['IBLOCK_ID'];

			if (!isset($result[$iblockId]))
			{
				$result[$iblockId] = [];
			}

			$result[$iblockId][] = $element;
		}

		return $result;
	}

	protected function emulateElements(array $feedOffers, Feed\Source\Context $context) : array
	{
		$elements = [];
		$parents = [];

		foreach ($feedOffers as $feedOffer)
		{
			if (!empty($feedOffer['PARENT_ID']) && $context->hasOffers())
			{
				$parents[$feedOffer['PARENT_ID']] = [
					'IBLOCK_ID' => $context->iblockId(),
					'ID' => $feedOffer['PARENT_ID'],
				];

				$elements[$feedOffer['ELEMENT_ID']] = [
					'IBLOCK_ID' => $context->offerIblockId(),
					'ID' => $feedOffer['ELEMENT_ID'],
					'PARENT_ID' => $feedOffer['PARENT_ID'],
				];
			}
			else
			{
				$elements[$feedOffer['ELEMENT_ID']] = [
					'IBLOCK_ID' => $context->iblockId(),
					'ID' => $feedOffer['ELEMENT_ID'],
				];
			}
		}

		return [$elements, $parents];
	}

	protected function fetchValues(Feed\Source\Routine\QueryBuilder $queryBuilder, Feed\Source\Data\SourceSelect $tagSources, array $elements, array $parents, Feed\Source\Context $context, array $allOffers) : array
	{
		[$commonSelect, $delayedSelect] = $this->splitTagSources($tagSources);
		$sourceValues = $queryBuilder->fetch($commonSelect, $elements, $parents, $context);
		$sourceValues = $this->mergeValues($sourceValues, $allOffers);

		if (!$delayedSelect->isEmpty())
		{
			$delayedElements = array_intersect_key($elements, $sourceValues);
			$sourceValues = $queryBuilder->fetch($delayedSelect, $delayedElements, $parents, $context, $sourceValues);
		}

		return $sourceValues;
	}

	protected function splitTagSources(Feed\Source\Data\SourceSelect $sourceSelect) : array
	{
		$commonSelect = new Feed\Source\Data\SourceSelect();
		$delayedSelect = new Feed\Source\Data\SourceSelect();

		foreach ($sourceSelect->sources() as $type)
		{
			$fetcher = $this->fetcherPool->some($type);

			foreach ($sourceSelect->fields($type) as $field)
			{
				if (!($fetcher instanceof Feed\Source\FetcherDelayed))
				{
					$commonSelect->add($type, $field);
				}
				else
				{
					$delayedSelect->add($type, $field);
				}
			}
		}

		return [$commonSelect, $delayedSelect];
	}

	protected function mergeValues(array $sourceValues, array $elements) : array
	{
		$merger = new Feed\Source\Routine\ElementMerger($this->fetcherPool);
		$mergeMap = array_column($elements, 'MERGED_ID', 'ELEMENT_ID');
		$mergeMap = array_filter($mergeMap);

		return $merger->apply($sourceValues, $mergeMap);
	}

	protected function cloneValues(array $sourceValues, array $offers) : array
	{
		$result = [];

		foreach ($offers as $offer)
		{
			$elementId = $offer['ELEMENT_ID'];
			$regionId = $offer['REGION_ID'] ?? 0;

			$resultKey = $elementId . '-' . $regionId;

			if (!isset($sourceValues[$elementId])) { continue; }

			foreach ($sourceValues[$elementId] as $type => $typeValues)
			{
				$isCloneable = $this->fetcherPool->some($type) instanceof Feed\Source\FetcherCloneable;

				foreach ($typeValues as $key => $fieldValues)
				{
					if (!$isCloneable || !is_numeric($key) || !is_array($fieldValues))
					{
						$result[$resultKey][$type][$key] = $fieldValues;
					}
				}

				if ($isCloneable && isset($typeValues[$regionId]) && is_array($typeValues[$regionId]))
				{
					foreach ($typeValues[$regionId] as $key => $fieldValues)
					{
						$result[$resultKey][$type][$key] = $fieldValues;
					}
				}
			}
		}
		return $result;
	}

	protected function collectValues(Push\Setup\FieldMap $fieldMap, array $sourceValues) : array
	{
		$result = $this->collectTargetValues($fieldMap, $sourceValues);
		$result = $this->applyStocksLimit($fieldMap, $sourceValues, $result);

		return $result;
	}

	protected function collectTargetValues(Push\Setup\FieldMap $fieldMap, array $sourceValues) : array
	{
		$result = [];

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$targetValues = new Push\Engine\Data\TargetValues();

			foreach ($fieldMap->all() as $tagLink)
			{
				if (!isset($tagLink['TYPE'], $tagLink['FIELD'])) { continue; }

				$target = $tagLink['TARGET'];
				$value = $elementValues[$tagLink['TYPE']][$tagLink['FIELD']] ?? null;

				if ($value === null) { continue; }

				if ($target === Glossary::ENTITY_PRICE || $target === Glossary::ENTITY_STOCKS)
				{
					$targetValues->add($target, $this->flatValue($target, $value));
				}
			}

			$result[$elementId] = $targetValues;
		}

		return $result;
	}

	protected function applyStocksLimit(Push\Setup\FieldMap $fieldMap, array $sourceValues, array $result) : array
	{
		$stocksLimitTag = $fieldMap->one(Glossary::ENTITY_STOCKS_LIMIT);

		if (!isset($stocksLimitTag['TYPE'], $stocksLimitTag['FIELD'])) { return $result; }

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$stocksValue = $result[$elementId]->getStocks();
			$stocksLimitValue = $elementValues[$stocksLimitTag['TYPE']][$stocksLimitTag['FIELD']] ?? null;

			if ($stocksLimitValue !== null && $stocksValue !== null)
			{
				$result[$elementId]->setStocks(min($stocksLimitValue, $stocksValue));
			}
		}

		return $result;
	}

	protected function collectAvitoIds(Push\Setup\FieldMap $fieldMap, array $sourceValues) : array
	{
		$result = [];

		$tagLink = $fieldMap->one(Glossary::ENTITY_AVITO_ID);

		if (!isset($tagLink['TYPE'], $tagLink['FIELD'])) { return []; }

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$value = $elementValues[$tagLink['TYPE']][$tagLink['FIELD']] ?? null;

			if ($value === null) { continue; }

			$result[$elementId] = $this->flatValue($tagLink['TARGET'], $value);
		}

		return $result;
	}

	protected function flatValue(string $target, $value)
	{
		if (!is_array($value)) { return $value; }

		if ($target === Glossary::ENTITY_STOCKS)
		{
			$stocks = array_filter($value, static function ($item) {
				return is_numeric($item);
			});

			return !empty($stocks) ? array_sum($stocks) : null;
		}

		return !empty($value) ? reset($value) : null;
	}

    protected function extendValues(array $targetValues, Feed\Source\Context $context) : array
    {
        $event = new Main\Event(Config::getModuleName(), Push\EventActions::OFFER_EXTEND, [
            'VALUES' => $targetValues,
            'FEED_NAME' => $this->getFeed()->getName(),
            'FEED_ID' => $this->getFeed()->getId(),
            'FILE_NAME' => $this->getFeed()->getFileName(),
            'CONTEXT' => $context,
        ]);

        $event->send();

		return $targetValues;
    }

	protected function writeStamp(array $feedOffers, array $targetValues) : void
	{
		$command = new Push\Engine\Command\StampWriter($this->controller->getSetup());

		$command->write($feedOffers, $targetValues);
	}

	protected function writeAvitoIds(array $feedOffers, array $avitoIds) : void
	{
		if (empty($avitoIds)) { return; }

		$primaryMap = $this->offersPrimaryMap($feedOffers, $avitoIds);

		if (empty($primaryMap)) { return; }

		foreach (array_chunk($primaryMap, 500, true) as $primaryMapChunk)
		{
			$batch = new DB\Facade\BatchInsert(PrimaryMap\RepositoryTable::class);
			$batch->run($this->makePrimaryMapRows($primaryMapChunk), [
				'SERVICE_ID',
				'TIMESTAMP_X',
			]);
		}
	}

	protected function offersPrimaryMap(array $feedOffers, $avitoIds) : array
	{
		$result = [];

		foreach ($feedOffers as $feedOffer)
		{
			$sign = $feedOffer['ELEMENT_ID'] . '-' . $feedOffer['REGION_ID'];

			if (empty($avitoIds[$sign])) { continue; }

			$result[$feedOffer['PRIMARY']] = $avitoIds[$sign];
		}

		return $result;
	}

	protected function makePrimaryMapRows(array $primaryMap) : array
	{
		$result = [];
		$common = [
			'PUSH_ID' => $this->getPush()->getId(),
			'TIMESTAMP_X' => new Main\Type\DateTime(),
		];

		foreach ($primaryMap as $primary => $serviceId)
		{
			$result[] = $common + [
					'PRIMARY' => $primary,
					'SERVICE_ID' => $serviceId,
				];
		}

		return $result;
	}
}