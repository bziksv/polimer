<?php
namespace Avito\Export\Feed\Engine\Steps;

use Bitrix\Catalog;
use Bitrix\Main;
use Avito\Export\Feed;
use Avito\Export\Watcher;
use Avito\Export\Concerns;
use Avito\Export\Assert;
use Avito\Export\Glossary;
use Avito\Export\Structure;
use Avito\Export\DB;
use Avito\Export\Config;
use Avito\Export\Utils;

class Offer extends Step
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public const TYPE = 'offer';

	protected $format;
	protected $fetcherPool;
	protected $categoryFinder;

	public function __construct(Feed\Engine\Controller $controller)
	{
		parent::__construct($controller);
		$this->format = new Feed\Tag\Format(new Structure\CategoryChain([
			new Structure\DictionaryCategoryProxy(new Structure\Index()),
		]));
		$this->fetcherPool = new Feed\Source\FetcherPool();
		$this->categoryFinder = new Structure\NameFinder();
	}

	public function getName() : string
	{
		return static::TYPE;
	}

	public function getTitle() : string
	{
		return self::getLocale('TITLE', null, 'offer');
	}

	public function getTag(): string
	{
		return 'Ad';
	}

	public function getParentTag(): string
	{
		return 'Ads';
	}

	public function clear($isStrict = false) : void
	{
		parent::clear($isStrict);

		$this->clearCategoryLimit();
		$this->logger->clear();
	}

	protected function clearCategoryLimit(): void
	{
		$batch = new DB\Facade\BatchDelete(Offer\CategoryLimitTable::class);

		$batch->run([
			'filter' => [ '=FEED_ID' => $this->getFeed()->getId() ],
		]);
	}

	public function start(string $action, $offset = null) : void
	{
		$offset = Offer\Offset::fromString($offset);

		if ($offset->getPointer() !== null)
		{
			$this->getWriter()->setPointer($offset->getPointer());
		}

		foreach ($this->getFeed()->getIblock() as $iblockId)
		{
			if (!$offset->tickIblock()) { continue; }

			$context = $this->getFeed()->iblockContext($iblockId);
			$context->extend([
				'DOMAIN' => $this->getFeed()->getDomain($iblockId),
			]);

			$changesFilter = null;

			if ($action === Watcher\Engine\Controller::ACTION_CHANGE)
			{
				$changes = $this->getChanges();
				$changesFilter = $this->getQueryFilterChanges($context, $changes);

				if ($changesFilter === null) { continue; }
			}

			$tagMap = $this->getFeed()->getTagMap($context->iblockId());
			$tagSources = $tagMap->select();
			$filterCollection = $this->getFeed()->getFilterCollection($context->iblockId());
			$queryBuilder = new Feed\Source\Routine\QueryBuilder($this->fetcherPool);

			$queryBuilder->bootSources($tagSources, $context);

			$select = $this->makeQuerySelect($tagSources, $context);
			$select = $this->extendQuerySelectByCategoryLimit($select, $this->getFeed()->getCategoryLimitMap());

			foreach ($filterCollection as $filterMap)
			{
				if (!$offset->tickFilterCollection()) { continue; }

				foreach ($queryBuilder->compileFilters($filterMap, $context, $changesFilter) as $oneFilter)
				{
					if (!$offset->tickFilter()) { continue; }

					do
					{
						[$elements, $queryOffset, $hasNext] = $this->getElements($oneFilter['ELEMENT'], $select['ELEMENT'], $offset->getQuery());

						$parents = [];

						if ($context->hasOffers())
						{
							[$elements, $parents] = $this->splitSkuElements($elements);
							$elements += $this->getOffers($context, array_keys($parents), $oneFilter['OFFER'], $select['OFFER']);
						}

						$elements = $this->filterProcessed($elements, $action === Watcher\Engine\Controller::ACTION_FULL);

						$this->logger->allowDelete();
						$this->logger->delayFlush();
						$this->logger->used(Glossary::ENTITY_OFFER, array_keys($elements));

						[$sourceValues, $elements] = $this->fetchValues($queryBuilder, $tagMap, $tagSources, $elements, $parents, $context);
						[$sourceValues, $elements] = $this->cloneValues($tagMap, $sourceValues, $elements);
						$tagValues = $this->collectValues($tagMap, $sourceValues);
						$tagValues = $this->extendValues($tagValues, $elements, $context);
						$tagValues = $this->mapCategories($tagValues);
						$tagValues = $this->filterValues($tagValues, $elements);

						$this->writeDataStep($tagValues, $elements, $context);

						$this->logger->flush();

						$offset->setQuery($queryOffset);

						if ($this->controller->isTimeExpired())
						{
							$offset->setPointer($this->getWriter()->getPointer());

							if (!$hasNext) { $offset->tickFilter(); }

							throw new Watcher\Exception\TimeExpired($this, (string)$offset);
						}
					}
					while($hasNext);
				}
			}
		}
	}

	public function progressDetails(string $offset = null) : string
	{
		$storage = $this->getStorageDataEntity()->getDataClass();
		$count = $storage::getCount([
			'=FEED_ID' => $this->getFeed()->getId(),
		]);

		return self::getLocale('PROGRESS', [
			'#COUNT#' => $count,
		]);
	}

	protected function buildTag(Feed\Engine\Data\TagValues $tagValues, Feed\Source\Context $context = null) : Feed\Engine\Data\TagCompiled
	{
		Assert::notNull($context, 'context');

		$arrayValues = $tagValues->asArray();
		$tagCompiled = new Feed\Engine\Data\TagCompiled($this->getTag(), null, [], true);
		$format = $tagValues->getFormat() ?? $this->format;

		foreach ($format->tags() as $tag)
		{
			$code = $tag->name();
			$value = $arrayValues[$code] ?? null;

			if ($tag->multiple())
			{
				$tag->exportMultiple($tagCompiled, $value, $arrayValues, $context);
			}
			else
			{
				$tag->exportSingle($tagCompiled, $value, $arrayValues, $context);
			}
		}

		return $tagCompiled;
	}

	protected function getQueryFilterChanges(Feed\Source\Context $context, array $changes): ?array
	{
		$changesFilter = [];
		$needFullFilter = false;

		foreach ($changes as $changeType => $entityIds)
		{
			if ($changeType !== static::TYPE)
			{
				$changesFilter = [];
				$needFullFilter = true;
				break;
			}

			if (!$context->hasOffers())
			{
				$entityType = 'ELEMENT';
				$entityFilter = [
					'=ID' => $entityIds,
				];
			}
			else
			{
				$elementIdsMap = array_flip($entityIds);

				$queryOffers = \CIBlockElement::GetList(
					array(),
					array(
						'IBLOCK_ID' => $context->offerIblockId(),
						'=ID' => $entityIds,
					),
					false,
					false,
					array(
						'IBLOCK_ID',
						'ID',
						'PROPERTY_' . $context->offerPropertyId()
					)
				);

				while ($offer = $queryOffers->Fetch())
				{
					$offerId = (int)$offer['ID'];
					$offerElementId = (int)$offer['PROPERTY_' . $context->offerPropertyId() . '_VALUE'];

					if ($offerElementId > 0 && !isset($elementIdsMap[$offerElementId]))
					{
						$elementIdsMap[$offerElementId] = true;
					}

					if (isset($elementIdsMap[$offerId]))
					{
						unset($elementIdsMap[$offerId]);
					}
				}

				if (empty($elementIdsMap)) { continue; }

				$entityType = 'ELEMENT';
				$entityFilter = [
					'=ID' => array_keys($elementIdsMap),
				];
			}

			if (isset($entityType, $entityFilter))
			{
				if (!isset($changesFilter[$entityType]))
				{
					$changesFilter[$entityType] = [];
				}
				else if (count($changesFilter[$entityType]) === 1)
				{
					$changesFilter[$entityType]['LOGIC'] = 'OR';
				}

				$changesFilter[$entityType][] = $entityFilter;
			}
		}

		if (!$needFullFilter && empty($changesFilter))
		{
			$changesFilter = null;
		}

		return $changesFilter;
	}

	protected function makeQuerySelect(Feed\Source\Data\SourceSelect $tagSources, Feed\Source\Context $context): array
	{
		$result = [
			'ELEMENT' => ['IBLOCK_ID', 'ID'],
			'OFFER' => ['IBLOCK_ID', 'ID'],
		];

		if ($context->hasOffers())
		{
			$result['ELEMENT'][] = 'TYPE';
		}

		foreach ($tagSources->sources() as $type)
		{
			$select = $tagSources->fields($type);
			$fetcher = $this->fetcherPool->some($type);

			foreach ($fetcher->select($select) as $elementType => $elementFields)
			{
				$result[$elementType] = array_unique(array_merge(
					$result[$elementType],
					$elementFields
				));
			}
		}

		return $result;
	}

	protected function extendQuerySelectByCategoryLimit(array $select, Feed\Setup\CategoryLimitMap $categoryLimitMap) : array
	{
		if ($categoryLimitMap->isEmpty()) { return $select; }

		$elementTypes = [
			'ELEMENT',
			'OFFER',
		];

		foreach ($elementTypes as $elementType)
		{
			if (!isset($select[$elementType])) { continue; }

			$select[$elementType][] = 'SORT';
		}

		return $select;
	}

	protected function splitSkuElements(array $elements) : array
	{
		if (!Main\Loader::includeModule('catalog')) { return [$elements, []]; }

		$simple = [];
		$sku = [];

		foreach ($elements as $key => $element)
		{
			$type = $element['TYPE'] ?? null;

			if ((int)$type === Catalog\ProductTable::TYPE_SKU)
			{
				$sku[$key] = $element;
			}
			else
			{
				$simple[$key] = $element;
			}
		}

		return [$simple, $sku];
	}

	protected function getElements(array $filter, array $select, $offset = 0): array
	{
		$result = [];
		$count = 0;
		$limit = max(1, (int)$this->getParameter('ELEMENT_LIMIT', 50));

		if ($offset > 0)
		{
			$filter[] = [ '>ID' => $offset ];
		}

		$query = \CIBlockElement::GetList(['ID' => 'ASC'], $filter, false, [ 'nTopCount' => $limit ], $select);

		while ($fields = $query->GetNext())
		{
			$id = (int)$fields['ID'];

			$offset = $id;
			++$count;

			if (isset($result[$id])) { continue; }

			$result[$id] = $fields;
		}

		$hasNext = ($count >= $limit);

		return [$result, $offset, $hasNext];
	}

	protected function getOffers(Feed\Source\Context $context, array $elementIds, array $filter, array $select) : array
	{
		if (empty($elementIds)) { return []; }

		$offerIblockId = $context->offerIblockId();
		$propertyOfferId = $context->offerPropertyId();

		if ($offerIblockId === null || $propertyOfferId === null) { return []; }

		$result = [];

		$filter['IBLOCK_ID'] = $offerIblockId;
		$filter['=PROPERTY_' . $propertyOfferId] = $elementIds;
		$select[] = 'PROPERTY_' . $propertyOfferId;

		$query = \CIBlockElement::GetList(['ID' => 'ASC'], $filter, false, false, $select);

		while ($fields = $query->GetNext())
		{
			$fields['PARENT_ID'] = (int)$fields['PROPERTY_' . $propertyOfferId . '_VALUE'];

			if (!isset($result[$fields['ID']]))
			{
				$result[$fields['ID']] = $fields;
			}
		}

		return $result;
	}

	protected function cloneValues(Feed\Setup\TagMap $tagMap, array $sourceValues, array $elements) : array
	{
		$resultElements = $elements;
		$resultValues = $sourceValues;
		$idSource = $tagMap->one('Id');
		$firstRegionId = null;

		foreach ($sourceValues as $elementId => $elementValues)
		{
			foreach ($elementValues as $type => $typeValues)
			{
				$source = $this->fetcherPool->some($type);

				if (!($source instanceof Feed\Source\FetcherCloneable)) { continue; }

				$commonValues = [];
                $regionValues = [];

				foreach ($typeValues as $key => $fieldValues)
				{
					if (!is_numeric($key) || !is_array($fieldValues))
					{
						$commonValues[$key] = $fieldValues;
					}
                    else
                    {
                        $regionValues[$key] = $fieldValues;
                    }
				}

				foreach ($regionValues as $regionId => $fieldValues)
				{
					$regionOverrides = [
						$type => $commonValues + $fieldValues,
					];

					if ($firstRegionId === null || $firstRegionId === $regionId)
					{
						$firstRegionId = $regionId;
						$resultKey = $elementId;
					}
					else
					{
						$resultKey = $elementId . '-' . $regionId;
					}

					if (isset($resultValues[$resultKey]))
					{
						$resultValues[$resultKey] = array_merge($resultValues[$resultKey], $regionOverrides);
						$resultElements[$resultKey] += [ 'REGION_ID' => $regionId ];
					}
					else
					{
						$resultElements[$resultKey] = $elements[$elementId] + [ 'REGION_ID' => $regionId ];
						$resultValues[$resultKey] = $regionOverrides + $elementValues;
					}

					$sourceId = $resultValues[$elementId][$idSource['TYPE']][$idSource['FIELD']] ?? null;

					if ($firstRegionId !== $regionId && !empty($sourceId))
					{
						$resultId = $sourceId . '-' . $regionId;
						$resultValues[$resultKey][$idSource['TYPE']][$idSource['FIELD']] = $resultId;
					}
				}
			}
		}

		return [$resultValues, $resultElements];
	}

	protected function collectValues(Feed\Setup\TagMap $tagMap, array $sourceValues) : array
	{
		$result = [];

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$result[$elementId] = new Feed\Engine\Data\TagValues();

			foreach ($tagMap->all() as $tagLink)
			{
				$value = null;

				if (isset($tagLink['TYPE'], $tagLink['FIELD']))
				{
					$value = $elementValues[$tagLink['TYPE']][$tagLink['FIELD']] ?? null;
				}
				else if (isset($tagLink['TEXT']))
				{
					$value = $tagLink['TEXT'];
				}

				$tagName = $tagLink['CODE'];
				$tag = $this->format->tag($tagName);

				if ($tag === null) { continue; }

				if ($tag instanceof Feed\Tag\TagExtractable)
				{
					[$selfValue, $siblingValues] = $tag->extract($value, $tagLink, $this->format);

					$this->pushValue($result[$elementId], $tag, $selfValue);

					foreach ((array)$siblingValues as $siblingName => $siblingValue)
					{
						$siblingTag = $this->format->tag($siblingName);

						Assert::notNull($siblingTag, 'siblingTag');

						$this->pushValue($result[$elementId], $siblingTag, $siblingValue);
					}
				}
				else
				{
					$this->pushValue($result[$elementId], $tag, $value);
				}
			}
		}

		return $result;
	}

	protected function pushValue(Feed\Engine\Data\TagValues $result, Feed\Tag\Tag $tag, $value) : void
	{
		if (Utils\Value::isEmpty($value)) { return; }

		$tagName = $tag->name();

		if ($tag->multiple())
		{
			if (!is_array($value)) { $value = [ $value ]; }

			$newValue = $result->getRaw($tagName) ?? [];

			foreach ($value as $key => $one)
			{
				if (is_numeric($key))
				{
					$newValue[] = $one;
				}
				else if (!isset($newValue[$key]) || !Utils\Value::isEmpty($one))
				{
					$newValue[$key] = $one;
				}
			}

			$result->setRaw($tagName, $newValue);
		}
		else if ($result->getRaw($tagName) === null || !Utils\Value::isEmpty($value))
		{
			$result->setRaw($tagName, $value);
		}
	}

	protected function extendValues(array $tagValues, array $elements, Feed\Source\Context $context) : array
	{
		$event = new Main\Event(Config::getModuleName(), Feed\EventActions::OFFER_EXTEND, [
			'VALUES' => $tagValues,
			'ELEMENTS' => $elements,
			'FEED_NAME' => $this->getFeed()->getName(),
			'FEED_ID' => $this->getFeed()->getId(),
			'FILE_NAME' => $this->getFeed()->getFileName(),
			'CONTEXT' => $context,
		]);

		$event->send();

		return $tagValues;
	}

	/**
	 * @param Feed\Engine\Data\TagValues[] $tagValues
	 *
	 * @return Feed\Engine\Data\TagValues[]
	 */
	protected function mapCategories(array $tagValues) : array
	{
		$categoryTags = Structure\CategoryLevelRegistry::tags();
		$formatCache = [];
		$commonTags = $this->format->tags();

		foreach ($tagValues as $tagValue)
		{
			$categoryValues = $tagValue->getFew($categoryTags);
			$categoryValues = $this->mergeCategoryValues($categoryValues);
			$categoryValues = $this->flatValues($categoryValues);

			if (empty($categoryValues)) { continue; }

			$categoryChain = $this->categoryFinder->category(
				$this->format->categoryChain()->index(),
				$categoryValues
			);

			if ($categoryChain === null) { continue; }

			$categoryChainId = spl_object_id($categoryChain);

			if (!isset($formatCache[$categoryChainId]))
			{
				$formatCache[$categoryChainId] = $this->format->cloneWithCategoryChain($categoryChain);
			}

			$tagValue->setFormat($formatCache[$categoryChainId]);
			$tagValue->spawnTags(array_keys(array_diff_key($formatCache[$categoryChainId]->tags(), $commonTags)));
		}

		return $tagValues;
	}

	protected function mergeCategoryValues(array $categoryValues) : array
	{
		$categoryName = 'Category';

		if (!empty($categoryValues[$categoryName]) && is_array($categoryValues[$categoryName]))
		{
			$categoryValue = null;

			foreach ($categoryValues['Category'] as $name => $value)
			{
				if ($name === $categoryName)
				{
					$categoryValue = $value;
				}
				else if (empty($categoryValues[$name]))
				{
					$categoryValues[$name] = $value;
				}
			}

			$categoryValues[$categoryName] = $categoryValue;
		}

		return $categoryValues;
	}

	protected function flatValues(array $values) : array
	{
		foreach ($values as $name => $value)
		{
			$values[$name] = $this->flatString($value);
		}

		return $values;
	}

	protected function flatString($value)
	{
		if ($value === null) { return null; }

		if (is_array($value))
		{
			if (empty($value)) { return null; }

			$result = $this->flatString(reset($value));
		}
		else
		{
			$result = (string)$value;
		}

		return $result;
	}

	protected function filterValues(array $groupValues, array $elements): array
	{
		$result = [];

		/** @var Feed\Engine\Data\TagValues $tagValues */
		foreach ($groupValues as $elementId => $tagValues)
		{
			$element = $elements[$elementId];

			if (!$tagValues->isSuccess())
			{
				$message = implode(', ', $tagValues->getErrorMessages()) ?: self::getLocale('TAG_VALUE_ERROR_UNKNOWN');

				$this->logger->error($message, [
					'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
					'ENTITY_ID' => $element['ID'],
					'REGION_ID' => $element['REGION_ID'] ?? 0,
				]);

				continue;
			}

			$arrayValues = $tagValues->asArray();
			$format = $tagValues->getFormat() ?? $this->format;
			$isValid = true;

			foreach ($format->tags() as $tag)
			{
				$code = $tag->name();
				$value = $arrayValues[$code] ?? null;

				if (empty($value))
				{
					$error = $tag->checkRequired($value, $arrayValues, $format);

					if ($error === null) { continue; }

					$isValid = false;
				}
				else
				{
					$error = $tag->checkValue($value, $arrayValues, $format);

					if ($error === null) { continue; }

					$isValid = !$tag->required();
				}

				$message = self::getLocale('CHECK_ERROR', ['#TAG#' => $code, '#MESSAGE#' => $error->getMessage()]);
				$context = [
					'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
					'ENTITY_ID' => $element['ID'],
					'REGION_ID' => $element['REGION_ID'] ?? 0,
				];

				if ($isValid)
				{
					$this->logger->warning($message, $context);
					$tagValues->remove($code);
				}
				else
				{
					$this->logger->error($message, $context);
					break;
				}
			}

			if (!$isValid) { continue; }

			$result[$elementId] = $tagValues;
		}

		return $result;
	}

	protected function checkDataStorage(array $tags, array $fieldsStorage, array $elementList, Feed\Source\Context $context) : array
	{
		[$tags, $fieldsStorage] = parent::checkDataStorage($tags, $fieldsStorage, $elementList, $context);
		[$tags, $fieldsStorage] = $this->checkCategoryLimit($tags, $fieldsStorage, $elementList);

		return [$tags, $fieldsStorage];
	}

	protected function checkCategoryLimit(array $tags, array $fieldsStorage, array $elementList) : array
	{
		$limitMap = $this->getFeed()->getCategoryLimitMap();

		$command = new Feed\Engine\Command\CategoryLimit(
			$this->format,
			$this->getFeed()->getId(),
			$limitMap,
			$this->logger
		);

		return $command->resolve($tags, $fieldsStorage, $elementList);
	}

	protected function getStorageDataEntity() : Main\ORM\Entity
	{
		return Offer\Table::getEntity();
	}

	protected function getStoragePrimary(array $element) : array
	{
		return [
			'ELEMENT_ID' => $element['ID'],
			'REGION_ID' => $element['REGION_ID'] ?? 0,
		];
	}

	protected function getStorageAdditionalData($element, Feed\Source\Context $context): array
	{
		return [
			'PARENT_ID' => $element['PARENT_ID'] ?? '',
			'IBLOCK_ID' => $context->iblockId(),
			'MERGED_ID' => $element['MERGED_ID'] ?? '',
		];
	}

	protected function getStorageChangesFilter(array $changes, int $feedId) : ?array
	{
		$needFull = false;
		$result = [];

		foreach ($changes as $changeType => $entityIds)
		{
			if ($changeType !== static::TYPE)
			{
				$needFull = true;
				break;
			}

			$dataClass = $this->getStorageDataEntity()->getDataClass();
			$elementFilter = [];
			$parentFilter = [];

			$query = $dataClass::getList([
				'filter' => [
					'=FEED_ID' => $feedId,
					[
						'LOGIC' => 'OR',
						[ '=ELEMENT_ID' => $entityIds ],
						[ '=PARENT_ID' => $entityIds ],
					],
				],
				'select' => [
					'ELEMENT_ID',
					'PARENT_ID'
				]
			]);

			while ($row = $query->fetch())
			{
				$parentId = (int)$row['PARENT_ID'];

				if ($parentId > 0)
				{
					$parentFilter[$parentId] = true;
				}
				else
				{
					$elementFilter[] = (int)$row['ELEMENT_ID'];
				}
			}

			if (!empty($parentFilter))
			{
				$result[] = [
					'=PARENT_ID' => array_keys($parentFilter)
				];
			}

			if (!empty($elementFilter))
			{
				$result[] = [
					'=ELEMENT_ID' => $elementFilter
				];
			}
		}

		if ($needFull)
		{
			$result = [];
		}
		else if (empty($result))
		{
			$result = null;
		}
		else if (count($result) > 1)
		{
			$result['LOGIC'] = 'OR';
		}

		return $result;
	}

	protected function filterProcessed(array $elements, bool $full = false) : array
	{
		if (empty($elements)) { return []; }

		$filter = [
			'=FEED_ID' => $this->controller->getFeed()->getId(),
			'=ELEMENT_ID' => array_keys($elements),
			'!=STATUS' => Offer\Table::STATUS_WAIT,
		];

		if (!$full)
		{
			$filter['>=TIMESTAMP_X'] = $this->getParameter('INIT_TIME');
		}

		$iterator = Offer\Table::getList([
			'select' => [ 'ELEMENT_ID' ],
			'filter' => $filter,
			'group' => [ 'ELEMENT_ID' ],
		]);

		$found = array_column($iterator->fetchAll(), 'ELEMENT_ID', 'ELEMENT_ID');

		return array_diff_key($elements, $found);
	}

	protected function fetchValues(Feed\Source\Routine\QueryBuilder $queryBuilder, Feed\Setup\TagMap $tagMap, Feed\Source\Data\SourceSelect $tagSources, array $elements, array $parents, Feed\Source\Context $context) : array
	{
		[$commonSelect, $delayedSelect] = $this->splitTagSources($tagSources, $tagMap);
		$sourceValues = $queryBuilder->fetch($commonSelect, $elements, $parents, $context);
		[$sourceValues, $elements] = $this->mergeValues($tagMap, $sourceValues, $elements);

		if (!$delayedSelect->isEmpty())
		{
			$delayedElements = array_intersect_key($elements, $sourceValues);
			$sourceValues = $queryBuilder->fetch($delayedSelect, $delayedElements, $parents, $context, $sourceValues);
		}

		return [$sourceValues, $elements];
	}

	protected function splitTagSources(Feed\Source\Data\SourceSelect $sourceSelect, Feed\Setup\TagMap $tagMap) : array
	{
		$commonSelect = new Feed\Source\Data\SourceSelect();
		$delayedSelect = new Feed\Source\Data\SourceSelect();
		$idSource = $tagMap->one('Id');

		foreach ($sourceSelect->sources() as $type)
		{
			$fetcher = $this->fetcherPool->some($type);

			foreach ($sourceSelect->fields($type) as $field)
			{
				if (
					($idSource['TYPE'] === $type && $idSource['FIELD'] === $field)
					|| !($fetcher instanceof Feed\Source\FetcherDelayed)
				)
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

	protected function mergeValues(Feed\Setup\TagMap $tagMap, array $sourceValues, array $elements) : array
	{
		$merger = new Feed\Source\Routine\ElementMerger($this->fetcherPool);
		$mergeMap = $this->mergeMap($tagMap, $sourceValues, $elements);

		return [
			$merger->apply($sourceValues, $mergeMap),
			$this->markMerged($elements, $mergeMap),
		];
	}

	protected function mergeMap(Feed\Setup\TagMap $tagMap, array $sourceValues, array $elements) : array
	{
		$idSource = $tagMap->one('Id');
		$unique = [];
		$result = [];

		foreach ($sourceValues as $elementId => $elementValues)
		{
			$tagId = $elementValues[$idSource['TYPE']][$idSource['FIELD']] ?? null;
			$parentId = $elements[$elementId]['PARENT_ID'] ?? null;

			if (empty($tagId) || empty($parentId)) { continue; }

			$uniqueId = $unique[$tagId] ?? null;

			if ($uniqueId !== null && $parentId === $elements[$uniqueId]['PARENT_ID'])
			{
				$result[$elementId] = $uniqueId;
			}
			else
			{
				$unique[$tagId] = $elementId;
			}
		}

		return $result;
	}

	protected function markMerged(array $elements, array $mergeMap) : array
	{
		foreach ($mergeMap as $from => $to)
		{
			if (!isset($elements[$from])) { continue; }

			$elements[$from]['MERGED_ID'] = $to;
		}

		return $elements;
	}
}