<?php
namespace Avito\Export\Feed\Engine\Command;

use Avito\Export\Concerns;
use Avito\Export\Logger;
use Avito\Export\Feed\Source;
use Avito\Export\Glossary;
use Avito\Export\Psr;
use Avito\Export\Utils\ArrayHelper;
use Bitrix\Main;
use Avito\Export\Feed\Engine\Steps\Offer;

class PrimaryCollision
{
	use Concerns\HasLocale;

	protected $storage;
	protected $logger;

	public function __construct(Main\ORM\Entity $storage, Logger\Logger $logger)
	{
		$this->storage = $storage;
		$this->logger = $logger;
	}

	public function resolve(array $tags, array $fieldsStorage, array $filterExist) : array
	{
		$fieldsStorage = $this->localCollision($fieldsStorage);
		$fieldsStorage = $this->storageCollision($fieldsStorage, $filterExist);

		return [ $tags, $fieldsStorage ];
	}

	protected function localCollision(array $fieldsStorage) : array
	{
		$used = [];

		foreach ($fieldsStorage as &$row)
		{
			if ((int)$row['STATUS'] === Offer\Table::STATUS_FAIL) { continue; }

			if (isset($used[$row['PRIMARY']]))
			{
				if (empty($row['MERGED_ID']))
				{
					$this->log($row['STORAGE_PRIMARY']);
				}

				$row['STATUS'] = Offer\Table::STATUS_FAIL;
			}

			$used[$row['PRIMARY']] = true;
		}
		unset($row);

		return $fieldsStorage;
	}

	protected function storageCollision(array $fieldsStorage, array $filterExist) : array
	{
		$validFields = array_filter($fieldsStorage, static function(array $fields) {
			return (int)$fields['STATUS'] === Offer\Table::STATUS_OK;
		});
		$used = array_column($validFields, 'STORAGE_PRIMARY', 'PRIMARY');
		$keysMap = ArrayHelper::keysByColumn($validFields, 'PRIMARY');

		if (empty($used)) { return $fieldsStorage; }

		$dataClass = $this->storage->getDataClass();

		$query = $dataClass::getList([
			'filter' => $filterExist + [
				'=PRIMARY' => array_keys($used),
				'=STATUS' => Offer\Table::STATUS_OK,
			],
			'select' => array_merge($this->storagePrimaryFields(), [
				'PRIMARY',
			]),
		]);

		while ($row = $query->fetch())
		{
			if (!isset($keysMap[$row['PRIMARY']])) { continue; }

			$storagePrimary = $used[$row['PRIMARY']];
			$key = $keysMap[$row['PRIMARY']];

			if ($this->compareStoragePrimary($storagePrimary, $row)) { continue; }

			$this->log($storagePrimary);
			$fieldsStorage[$key]['STATUS'] = Offer\Table::STATUS_FAIL;
		}

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
		$this->logger->error(self::getLocale('DOUBLE_ITEMS'), ArrayHelper::renameKeys($storagePrimary, [
			'ELEMENT_ID' => 'ENTITY_ID',
		]) + [
			'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
		]);
	}

	public function need(array $tagLink, Source\Context $context) : bool
	{
		if ($tagLink['FIELD'] !== 'ID') { return true; }

		return (
			$tagLink['TYPE'] !== Source\Registry::OFFER_FIELD
			&& ($tagLink['TYPE'] !== Source\Registry::IBLOCK_FIELD || $context->hasOffers())
		);
	}
}