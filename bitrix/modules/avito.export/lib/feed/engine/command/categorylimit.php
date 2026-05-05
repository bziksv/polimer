<?php
namespace Avito\Export\Feed\Engine\Command;

use Avito\Export\Concerns;
use Avito\Export\DB;
use Avito\Export\Feed;
use Avito\Export\Feed\Engine\Steps\Offer;
use Avito\Export\Logger;
use Avito\Export\Glossary;
use Avito\Export\Psr;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class CategoryLimit
{
	use Concerns\HasLocale;

	protected $format;
	protected $feedId;
	protected $limitMap;
	protected $logger;

	public function __construct(
		Feed\Tag\Format $format,
		int $feedId,
		Feed\Setup\CategoryLimitMap $limitMap,
		Logger\Logger $logger
	)
	{
		$this->format = $format;
		$this->feedId = $feedId;
		$this->limitMap = $limitMap;
		$this->logger = $logger;
	}

	public function resolve(array $tags, array $fieldsStorage, array $elementList) : array
	{
		$restrictions = $this->searchRestrictionsFromTags($tags, $fieldsStorage);

		if (empty($restrictions))
		{
			return [ $tags, $fieldsStorage ];
		}

		$stored = $this->storedUsed($restrictions);
		$used = $this->fillUsed($restrictions, $fieldsStorage, $elementList, $stored);
		$used = $this->sortUsed($used);
		$used = $this->testUsed($restrictions, $fieldsStorage, $used);
		$used = $this->sliceUsed($restrictions, $used);

		$fieldsStorage = $this->invalidateNew($restrictions, $fieldsStorage, $used);
		$fieldsStorage += $this->invalidateMissing($stored, $used, $fieldsStorage);

		$this->storeNew($fieldsStorage, $elementList, $restrictions);
		$this->removeMissing($stored, $used);

		return [ $tags, $fieldsStorage ];
	}

	protected function searchRestrictionsFromTags(array $tags, array $fieldsStorage) : array
	{
		$result = [];

		/** @var \Avito\Export\Feed\Engine\Data\TagCompiled $tag */
		foreach ($tags as $key => $tag)
		{
			if ((int)$fieldsStorage[$key]['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$category = $this->tagValue($tag, 'Category');
			$goodsType = $this->tagValue($tag,'GoodsType');

			if ($category === null) { continue; }

			$restrictions = $this->limitMap->filter($this->format, $category, $goodsType);

			if (empty($restrictions)) { continue; }

			$result[$key] = array_map(static function(array $restriction) {
				return (int)$restriction['COUNT'];
			}, $restrictions);
		}

		return $result;
	}

	protected function tagValue(Feed\Engine\Data\TagCompiled $tag, string $name) : ?string
	{
		$child = $tag->getChild($name);

		return ($child !== null ? $child->getValue() : null);
	}

	protected function fillUsed(array $restrictions, array $fieldsStorage, array $elementList, array $stored) : array
	{
		$used = $stored + array_fill_keys($this->restrictionIndexes($restrictions), []);

		foreach ($fieldsStorage as $key => $fields)
		{
			if (!isset($restrictions[$key]) || (int)$fields['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$primary = $fields['PRIMARY'];
			$priority = (int)$elementList[$key]['SORT'] ?: 5000;

			foreach ($restrictions[$key] as $index => $limit)
			{
				$used[$index][$primary] = $priority;
			}
		}

		return $used;
	}

	protected function sortUsed(array $used) : array
	{
		foreach ($used as &$usage)
		{
			$keysOrder = array_flip(array_keys($usage));

			uksort($usage, static function($aKey, $bKey) use ($usage, $keysOrder) {
				$aSort = (int)$usage[$aKey];
				$bSort = (int)$usage[$bKey];

				if ($aSort !== $bSort)
				{
					return $aSort < $bSort ? -1 : 1;
				}

				$aOrder = $keysOrder[$aKey];
				$bOrder = $keysOrder[$bKey];

				return $aOrder <=> $bOrder;
			});
		}
		unset($usage);

		return $used;
	}

	protected function testUsed(array $restrictions, array $fieldsStorage, array $used) : array
	{
		foreach ($fieldsStorage as $key => $fields)
		{
			if (!isset($restrictions[$key]) || (int)$fields['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$primary = (string)$fields['PRIMARY'];
			$valid = true;

			foreach ($restrictions[$key] as $index => $limit)
			{
				$position = $this->arrayKeyPosition($used[$index], $primary);

				if ($position === null || $position >= $limit)
				{
					$valid = false;
					break;
				}
			}

			if ($valid) { continue; }

			foreach ($used as &$usage)
			{
				if (!isset($usage[$primary])) { continue; }

				unset($usage[$primary]);
			}
			unset($usage);
		}

		return $used;
	}

	protected function arrayKeyPosition(array $haystack, $needle) : ?int
	{
		$result = null;
		$position = 0;

		foreach ($haystack as $key => $value)
		{
			if ((string)$key === (string)$needle)
			{
				$result = $position;
				break;
			}

			++$position;
		}

		return $result;
	}

	protected function sliceUsed(array $restrictions, array $used) : array
	{
		$limits = $this->restrictionLimits($restrictions);

		foreach ($used as $index => &$usage)
		{
			$limit = $limits[$index];

			if (count($usage) <= $limit) { continue; }

			$usage = array_slice($usage, 0,  $limit, true);
		}
		unset($usage);

		return $used;
	}

	protected function invalidateNew(array $restrictions, array $fieldsStorage, array $used) : array
	{
		foreach ($fieldsStorage as $key => &$fields)
		{
			if (!isset($restrictions[$key]) || (int)$fields['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$primary = $fields['PRIMARY'];
			$valid = true;

			foreach ($restrictions[$key] as $index => $limit)
			{
				if (!isset($used[$index][$primary]))
				{
					$valid = false;
					break;
				}
			}

			if ($valid) { continue; }

			$fields['STATUS'] = Offer\Table::STATUS_FAIL;
			$this->logExceed($fields['STORAGE_PRIMARY']);
		}
		unset($fields);

		return $fieldsStorage;
	}

	protected function invalidateMissing(array $stored, array $used, array $fieldsStorage) : array
	{
		$storedPrimaries = $this->flatUsedPrimaries($stored);
		$usedPrimaries = $this->flatUsedPrimaries($used);
		$missingPrimaries = array_diff_key($storedPrimaries, $usedPrimaries);

		if (empty($missingPrimaries)) { return []; }

		$result = [];
		$timestamp = new Main\Type\DateTime();
		$tableEntity = Offer\Table::getEntity();
		$primaryFields = array_diff_key(array_flip($tableEntity->getPrimaryArray()), [
			'FEED_ID' => true,
		]);
		$additionFields = array_diff_key($tableEntity->getScalarFields(), $primaryFields, [
			'STATUS' => true,
			'TIMESTAMP_X' => true,
			'PRIMARY' => true,
			'HASH' => true,
		]);

		$query = Offer\Table::getList([
			'filter' => [
				'=FEED_ID' => $this->feedId,
				'=PRIMARY' => array_keys($missingPrimaries),
				'=STATUS' => Offer\Table::STATUS_OK,
			],
			'select' => array_keys($primaryFields + $additionFields),
		]);

		while ($row = $query->fetch())
		{
			$storagePrimary = array_intersect_key($row, $primaryFields);
			$additionValues = array_intersect_key($row, $additionFields);

			$key = $row['ELEMENT_ID'] . '-' . $row['REGION_ID'];

			// search inside fields

			$fieldKeyVariants = [
				$row['ELEMENT_ID'],
				$key,
			];
			$foundFields = false;

			foreach ($fieldKeyVariants as $fieldKeyVariant)
			{
				if (!isset($fieldsStorage[$fieldKeyVariant])) { continue; }

				$storagePrimaryDiff = array_diff_assoc($storagePrimary, $fieldsStorage[$fieldKeyVariant]['STORAGE_PRIMARY']);

				if (empty($storagePrimaryDiff))
				{
					$foundFields = true;
					break;
				}
			}

			if ($foundFields) { continue; }

			// write result

			$result[$key] = [
				'FEED_ID' => $this->feedId,
				'STORAGE_PRIMARY' => $storagePrimary,
				'TIMESTAMP_X' => $timestamp,
				'STATUS' => Offer\Table::STATUS_FAIL,
			];
			$result[$key] += $additionValues;

			$this->logExceed($storagePrimary);
		}

		return $result;
	}

	protected function flatUsedPrimaries(array $used) : array
	{
		$result = [];

		foreach ($used as $usage)
		{
			$result += $usage;
		}

		return $result;
	}

	protected function storedUsed(array $restrictions) : array
	{
		if (empty($restrictions)) { return []; }

		$result = [];

		$query = Feed\Engine\Steps\Offer\CategoryLimitTable::getList([
			'select' => [ 'INDEX', 'PRIMARY', 'PRIORITY' ],
			'filter' => [
				'=FEED_ID' => $this->feedId,
				'=INDEX' => $this->restrictionIndexes($restrictions),
			],
		]);

		while ($row = $query->fetch())
		{
			$index = $row['INDEX'];
			$primary = $row['PRIMARY'];

			if (!isset($result[$index])) { $result[$index] = []; }

			$result[$index][$primary] = (int)$row['PRIORITY'];
		}

		return $result;
	}

	protected function restrictionIndexes(array $restrictions) : array
	{
		return array_keys($this->restrictionLimits($restrictions));
	}

	protected function restrictionLimits(array $restrictions) : array
	{
		$result = [];

		foreach ($restrictions as $restriction)
		{
			$result += $restriction;
		}

		return $result;
	}

	protected function storeNew(array $fieldsStorage, array $elementList, array $restrictions) : void
	{
		$rows = [];

		foreach ($fieldsStorage as $key => $fields)
		{
			if ((int)$fields['STATUS'] === Offer\Table::STATUS_FAIL || !isset($restrictions[$key])) { continue; }

			foreach ($restrictions[$key] as $index => $limit)
			{
				$rows[] = [
					'FEED_ID' => $this->feedId,
					'INDEX' => $index,
					'PRIMARY' => $fields['PRIMARY'],
					'PRIORITY' => (int)$elementList[$key]['SORT'] ?: 5000,
				];
			}
		}

		if (empty($rows)) { return; }

		$batch = new DB\Facade\BatchInsert(Feed\Engine\Steps\Offer\CategoryLimitTable::class);
		$batch->run($rows, [
			'PRIORITY',
		]);
	}

	protected function removeMissing(array $stored, array $used) : void
	{
		$partials = [];

		foreach ($stored as $index => $storedMap)
		{
			$missingMap = array_diff_key($storedMap, $used[$index]);

			if (empty($missingMap)) { continue; }

			$partials[] = [
				'=INDEX' => $index,
				'=PRIMARY' => array_keys($missingMap),
			];
		}

		if (empty($partials)) { return; }

		$filter = count($partials) > 1
			? [ 'LOGIC' => 'OR' ] + $partials
			: $partials;

		$batch = new DB\Facade\BatchDelete(Feed\Engine\Steps\Offer\CategoryLimitTable::class);
		$batch->run([
			'filter' => [
				'=FEED_ID' => $this->feedId,
				$filter,
			],
		]);
	}

	protected function logExceed(array $storagePrimary) : void
	{
		$this->logger->warning(self::getLocale('EXCEED_LIMIT'), ArrayHelper::renameKeys($storagePrimary, [
			'ELEMENT_ID' => 'ENTITY_ID',
		]) + [
			'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
		]);
	}
}