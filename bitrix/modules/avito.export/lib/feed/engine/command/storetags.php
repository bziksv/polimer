<?php
namespace Avito\Export\Feed\Engine\Command;

use Avito\Export\Concerns;
use Avito\Export\DB\Facade\BatchInsert;
use Avito\Export\DB\Facade\BatchDelete;
use Avito\Export\Feed\Engine;
use Avito\Export\Feed\Engine\Steps\Offer;
use Avito\Export\Logger;
use Avito\Export\Glossary;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class StoreTags
{
	use Concerns\HasLocale;

	private $add = [];
	private $update = [];
	protected $logger;
	protected $storage;

	public function __construct(Main\ORM\Entity $storage, Logger\Logger $logger)
	{
		$this->storage = $storage;
		$this->logger = $logger;
	}

	public function export(array $tags, array $fieldsStorage, array $elementList, array $filterExist): void
	{
		[$tags, $fieldsStorage] = $this->normalizeKeys($tags, $fieldsStorage);
		[$tags, $fieldsStorage] = $this->sanitizeFields($tags, $fieldsStorage);
		$hashMap = $this->getHashMap($fieldsStorage);

		$exists = $this->getExists($fieldsStorage, $elementList, $filterExist);
		$existsHash = ArrayHelper::column($exists, 'HASH');
		$changedHash = array_diff_assoc($existsHash, $hashMap);

		$add = array_diff_key($tags, $exists);
		$update = array_intersect_key($tags, $changedHash);
		$delete = array_diff_key($exists, $tags);

		$this->makeAdd($add, $fieldsStorage);
		$this->makeUpdate($update, $fieldsStorage);
		$this->makeDelete($delete);

		$this->insert($fieldsStorage);
		$this->remove($delete, $filterExist);
		$this->log($fieldsStorage);
	}

	private function sanitizeFields(array $tags, array $fieldsStorage) : array
	{
		$valid = [];

		foreach ($fieldsStorage as $key => &$row)
		{
			if ((int)$row['STATUS'] === Offer\Table::STATUS_OK)
			{
				$valid[$key] = true;
			}
			else
			{
				$row = array_diff_key($row, [
					'PRIMARY' => true,
					'HASH' => true,
				]);
			}
		}
		unset($row);

		$tags = array_intersect_key($tags, $valid);

		return [ $tags, $fieldsStorage ];
	}

	private function normalizeKeys(array $tags, array $fieldsStorage) : array
	{
		$resultTags = [];
		$resultFields = [];

		foreach ($fieldsStorage as $key => $fields)
		{
			$newKey = $this->storagePrimarySign($fields['STORAGE_PRIMARY']);

			if (isset($tags[$key]))
			{
				$resultTags[$newKey] = $tags[$key];
			}

			$resultFields[$newKey] = $fields;
		}

		return [ $resultTags, $resultFields ];
	}

	public function remove(array $exists, array $filterExists): void
	{
		foreach ($this->groupExists($exists) as [$elementIds, $commonValues])
		{
			$this->removeStorage($elementIds, $commonValues, $filterExists);
			$this->logger->remove(Glossary::ENTITY_OFFER, $elementIds, $commonValues);
		}
	}

	private function removeStorage(array $elementIds, array $commonValues, array $filterExists) : void
	{
		$commonFilter = [];

		foreach ($commonValues as $name => $value)
		{
			$commonFilter['=' . $name] = $value;
		}

		foreach (array_chunk($elementIds, 500) as $elementChunk)
		{
			$batch = new BatchDelete($this->storage->getDataClass());
			$batch->run([
				'filter' =>
					$filterExists
					+ [ '=ELEMENT_ID' => $elementChunk ]
					+ $commonFilter,
			]);
		}
	}

	private function getExists(array $fieldsStorage, array $elementList, array $filterExists) : array
	{
		$result = [];
		$storageClass = $this->storage->getDataClass();
		$elementFilter = $this->existsElementFilter($fieldsStorage, $elementList);

		if (empty($elementFilter)) { return $result; }

		$filter = [
			$elementFilter,
			'=STATUS' => Engine\Steps\Offer\Table::STATUS_OK,
		];

		$query = $storageClass::getList([
			'filter' => array_merge($filterExists, $filter),
			'select' => array_merge(
				$this->storagePrimaryFields(),
				['PRIMARY', 'HASH']
			)
		]);

		while ($storageElement = $query->fetch())
		{
			$result[$this->storagePrimarySign($storageElement)] = $storageElement;
		}

		return $result;
	}

	private function existsElementFilter(array $fieldsStorage, array $elementList) : array
	{
		$usedElementIds = array_column($elementList, 'ID', 'ID');
		$partials = [];

		if (!empty($usedElementIds))
		{
			$partials[] = [
				'=ELEMENT_ID' => array_values($usedElementIds),
			];
		}

		foreach ($fieldsStorage as $row)
		{
			if (isset($usedElementIds[$row['STORAGE_PRIMARY']['ELEMENT_ID']])) { continue; }

			$rowFilter = [];

			foreach ($row['STORAGE_PRIMARY'] as $field => $value)
			{
				$rowFilter['=' . $field] = $value;
			}

			$partials[] = $rowFilter;
		}

		if (count($partials) > 1)
		{
			$partials = [ 'LOGIC' => 'OR' ] + $partials;
		}
		else if (count($partials) === 1)
		{
			$partials = reset($partials);
		}

		return $partials;
	}

	private function groupExists(array $rows) : array
	{
		$commonFields = array_diff($this->storagePrimaryFields(), [ 'FEED_ID', 'ELEMENT_ID' ]);
		$keyElementIds = 0;
		$keyCommonFilter = 1;

		if (empty($commonFields))
		{
			return [
				[
					$keyElementIds => array_column($rows, 'ELEMENT_ID'),
					$keyCommonFilter => [],
				],
			];
		}

		$commonFieldsMap = array_flip($commonFields);
		$result = [];

		foreach ($rows as $row)
		{
			$commonValues = array_intersect_key($row, $commonFieldsMap);
			$commonSign = implode(':', $commonValues);

			if (!isset($result[$commonSign]))
			{
				$result[$commonSign] = [
					$keyElementIds => [],
					$keyCommonFilter => $commonValues,
				];
			}

			$result[$commonSign][$keyElementIds][] = $row['ELEMENT_ID'];
		}

		return $result;
	}

	private function storagePrimaryFields() : array
	{
		$storageClass = $this->storage->getDataClass();

		return $storageClass::getEntity()->getPrimaryArray();
	}

	private function storagePrimarySign(array $row) : string
	{
		$partials = [];

		foreach ($this->storagePrimaryFields() as $name)
		{
			if ($name === 'FEED_ID') { continue; }

			$partials[] = $row[$name];
		}

		return implode('-', $partials);
	}

	private function getHashMap(array $fieldsStorage): array
	{
		$result = [];

		foreach ($fieldsStorage as $fields)
		{
			if ((int)$fields['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$sign = $this->storagePrimarySign($fields['STORAGE_PRIMARY']);

			$result[$sign] = $fields['HASH'];
		}

		return $result;
	}

	public function getAdd() : array
	{
		return $this->add;
	}

	public function getUpdate() : array
	{
		return $this->update;
	}

	private function makeAdd(array $tags, array $fieldsStorage) : void
	{
		$this->add += $this->combineWrite($tags, $fieldsStorage);
	}

	private function makeUpdate(array $tags, array $fieldsStorage) : void
	{
		$this->update += $this->combineWrite($tags, $fieldsStorage);
	}

	public function makeDelete(array $exists) : void
	{
		$primaries = array_column($exists, 'PRIMARY');

		$this->update += array_fill_keys($primaries, '');
	}

	/**
	 * @param Engine\Data\TagCompiled[] $tags
	 * @param array $fieldsStorage
	 *
	 * @return array
	 */
	protected function combineWrite(array $tags, array $fieldsStorage) : array
	{
		$write = [];

		foreach ($tags as $id => $tag)
		{
			$fields = $fieldsStorage[$id];

			$write[$fields['PRIMARY']] = $tag->content();
		}

		return $write;
	}

	private function insert(array $fieldsStorage) : void
	{
		$storageClass = $this->storage->getDataClass();
		$timestamp = new Main\Type\DateTime();

		foreach (array_chunk($fieldsStorage, 500, true) as $insertChunk)
		{
			foreach ($insertChunk as $elementId => $fields)
			{
				$fields['TIMESTAMP_X'] = $timestamp;
				$fields += $fields['STORAGE_PRIMARY'];
				$fields += [
					'PRIMARY' => '',
					'HASH' => '',
				];

				$insertChunk[$elementId] = array_diff_key($fields, [
					'STORAGE_PRIMARY' => true,
				]);
			}

			$batch = new BatchInsert($storageClass);

			$batch->run($insertChunk, [
				'PRIMARY',
				'HASH',
				'TIMESTAMP_X',
				'STATUS',
			]);
		}
	}

	private function log(array $fieldsStorage) : void
	{
		foreach ($fieldsStorage as $fields)
		{
			if ((int)$fields['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			$this->logger->info(self::getLocale('LOG_WRITTEN'), ArrayHelper::renameKeys($fields['STORAGE_PRIMARY'], [
				'ELEMENT_ID' => 'ENTITY_ID',
			]) + [
				'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
			]);
		}
	}
}