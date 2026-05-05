<?php
namespace Avito\Export\DB\Facade;

use Bitrix\Main;

class BatchInsert
{
	protected $dataManager;

	/** @param class-string<Main\ORM\Data\DataManager> $dataManager */
	public function __construct(string $dataManager)
	{
		$this->dataManager = $dataManager;
	}

	public function run(array $rows, array $onDuplicate = null) : void
	{
		$dataClass = $this->dataClass();
		$entity = $dataClass::getEntity();
		$connection = $entity->getConnection();
		$helper = $entity->getConnection()->getSqlHelper();
		$tableName = $entity->getDBTableName();

		$valuesSql = $this->valuesSql($rows);
		$duplicateSql = $this->duplicateSql($onDuplicate);
		$rule = $duplicateSql === '' ? 'INSERT IGNORE INTO' : 'INSERT INTO';

		if ($valuesSql === '') { return; }

		$sql = implode(' ', [
			$rule,
			$helper->quote($tableName),
			$valuesSql,
			$duplicateSql ?? ''
		]);

		$connection->queryExecute($sql);
	}

	protected function valuesSql(array $rows) : string
	{
		if (empty($rows)) { return ''; }

		$firstRow = reset($rows);
		/* @description If the table `avito_export_feed_offer` has incorrect entries, perhaps the first entry in $rows has the wrong PRIMARY and HASH sequences */
		$names = array_keys($firstRow);
		$dataClass = $this->dataClass();
		$entity = $dataClass::getEntity();
		$tableName = $entity->getDBTableName();
		$fields = $entity->getFields();
		$helper = $entity->getConnection()->getSqlHelper();
		$fieldPart = null;
		$valuesPartials = [];

		foreach ($rows as $row)
		{
			$insertRow = [];

			foreach ($names as $name)
			{
				if (!isset($fields[$name]))
				{
					throw new Main\ArgumentException(sprintf(
						'%s entity has no `%s` field.',
						$entity->getName(),
						$name
					));
				}

				$insertRow[$name] = $fields[$name]->modifyValueBeforeSave($row[$name], $row);
			}

			$insert = $helper->prepareInsert($tableName, $insertRow);

			if ($fieldPart === null)
			{
				$fieldPart = $insert[0];
			}

			$valuesPartials[] = '(' . $insert[1] . ')';
		}

		if ($fieldPart === null || empty($valuesPartials)) { return ''; }

		return sprintf(
			'(%s) VALUES %s',
			$fieldPart,
			implode(',' . PHP_EOL, $valuesPartials)
		);
	}

	protected function duplicateSql(array $onDuplicate = null) : ?string
	{
		if ($onDuplicate === null) { return null; }
		if (empty($onDuplicate)) { return ''; }

		$dataClass = $this->dataClass();
		$helper = $dataClass::getEntity()->getConnection()->getSqlHelper();

		$partials = [];

		foreach ($onDuplicate as $name)
		{
			$nameQuoted = $helper->quote($name);

			$partials[] .= $nameQuoted . ' = VALUES(' . $nameQuoted . ')';
		}

		return sprintf(
			'ON DUPLICATE KEY UPDATE %s',
			implode(', ', $partials)
		);
	}

	/**
	 * for ide support
	 *
	 * @return Main\ORM\Data\DataManager
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function dataClass() : string
	{
		return $this->dataManager;
	}
}