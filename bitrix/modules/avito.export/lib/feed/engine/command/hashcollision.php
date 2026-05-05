<?php
namespace Avito\Export\Feed\Engine\Command;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Feed\Engine\Steps\Offer;
use Avito\Export\Logger;
use Avito\Export\Glossary;
use Avito\Export\Psr;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;

class HashCollision
{
	use Concerns\HasLocale;

	protected $logger;
	protected $storage;

	public function __construct(Main\ORM\Entity $storage, Logger\Logger $logger)
	{
		$this->storage = $storage;
		$this->logger = $logger;
	}

	public function resolve(array $tags, array $fieldsStorage, array $filterExist) : array
	{
		$fieldsStorage = $this->storageCollision($fieldsStorage, $filterExist);
		$fieldsStorage = $this->localCollision($fieldsStorage);

		return [ $tags, $fieldsStorage ];
	}

	protected function localCollision(array $fieldsStorage) : array
	{
		$hashMap = ArrayHelper::keysByColumn($this->onlyValid($fieldsStorage), 'HASH');

		foreach ($fieldsStorage as $key => &$fields)
		{
			if ((int)$fields['STATUS'] === Offer\Table::STATUS_OK && $hashMap[$fields['HASH']] !== $key)
			{
				$this->log($fields['STORAGE_PRIMARY']);
				$fields['STATUS'] = Offer\Table::STATUS_FAIL;
			}
		}
		unset($fields);

		return $fieldsStorage;
	}

	protected function storageCollision(array $fieldsStorage, array $filterExist) : array
	{
		$hashMap = array_column($this->onlyValid($fieldsStorage), 'HASH', 'HASH');

		if (empty($hashMap)) { return $fieldsStorage; }

		$dataClass = $this->storage->getDataClass();

		$query = $dataClass::getList([
			'filter' => $filterExist + [
				'=HASH' => array_keys($hashMap),
				'=STATUS' => Offer\Table::STATUS_OK,
			],
			'select' => array_merge($this->storagePrimaryFields(), [
				'HASH',
			]),
		]);

		$existRows = ArrayHelper::columnToKey($query->fetchAll(), 'HASH');

		foreach ($fieldsStorage as &$fields)
		{
			if (!isset($existRows[$fields['HASH']])) { continue; }

			$existRow = $existRows[$fields['HASH']];

			if (!$this->compareStoragePrimary($fields['STORAGE_PRIMARY'], $existRow))
			{
				$this->log($fields['STORAGE_PRIMARY']);
				$fields['STATUS'] = Offer\Table::STATUS_FAIL;
			}
		}
		unset($fields);

		return $fieldsStorage;
	}

	protected function storagePrimaryFields() : array
	{
		$dataClass = $this->storage->getDataClass();

		return $dataClass::getEntity()->getPrimaryArray();
	}

	protected function compareStoragePrimary(array $aPrimary, array $bPrimary) : bool
	{
		$result = true;

		foreach ($aPrimary as $key => $value)
		{
			if ((string)$value !== (string)$bPrimary[$key])
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	protected function log(array $storagePrimary) : void
	{
		$this->logger->warning(self::getLocale('DUPLICATE_ITEM'), ArrayHelper::renameKeys($storagePrimary, [
			'ELEMENT_ID' => 'ENTITY_ID',
		]) + [
			'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
		]);
	}

	protected function onlyValid(array $fieldsStorage) : array
	{
		return array_filter($fieldsStorage, static function(array $row) {
			return (int)$row['STATUS'] === Offer\Table::STATUS_OK;
		});
	}
}