<?php
namespace Avito\Export\Admin\Page;

use Avito\Export\Assert;
use Avito\Export\DB;
use Bitrix\Main\ORM;

abstract class TableGrid extends Grid
{
	protected function loadItems(array $queryParameters = []) : array
	{
		$entity = $this->getTableEntity();
		$dataClass = $entity->getDataClass();
		$query = $dataClass::getList($queryParameters);
		$result = [];

		while ($row = $query->fetch())
		{
			$result[] = $this->prepareItem($row);
		}

		return $result;
	}

	protected function prepareItem(array $row) : array
	{
		return $row;
	}

	protected function loadTotalCount(array $queryParameters = []) : int
	{
		$entity = $this->getTableEntity();
		$dataClass = $entity->getDataClass();

		return $dataClass::getCount($queryParameters['filter'] ?? []);
	}

	protected function loadFields() : array
	{
		return $this->loadTableFields();
	}

	protected function loadTableFields() : array
	{
		$entity = $this->getTableEntity();
		/** @var class-string<DB\Table> $dataClass */
		$dataClass = $entity->getDataClass();

		Assert::isSubclassOf($dataClass, DB\Table::class);

		return $dataClass::getMapDescription();
	}

	abstract protected function getTableEntity() : ORM\Entity;
}