<?php
namespace Avito\Export\DB\Facade;

use Bitrix\Main;

class BatchUpdate
{
	protected $dataManager;

	/** @param class-string<Main\ORM\Data\DataManager> $dataManager */
	public function __construct(string $dataManager)
	{
		$this->dataManager = $dataManager;
	}

	public function run(array $parameters, array $values) : void
	{
		$dataClass = $this->dataClass();
		$entity = $dataClass::getEntity();
		$connection = $entity->getConnection();

		$selectQuery = $this->makeQuery($parameters);
		$selectParts = $this->parseSql($selectQuery->getQuery());

		$connection->queryExecute(sprintf(
			'UPDATE %s %s SET %s %s',
			$selectParts['from'],
			$selectParts['join'] ?? '',
			$this->valuesSql($selectQuery->getInitAlias(), $values),
			$selectParts['where'] ?? ''
		));
	}

	protected function valuesSql(string $tableAlias, array $values) : string
	{
		$dataClass = $this->dataClass();
		$entity = $dataClass::getEntity();
		$tableName = $entity->getDBTableName();
		$helper = $entity->getConnection()->getSqlHelper();

		[$update] = $helper->prepareUpdate($tableName, $values);
		$update = $tableAlias . '.' . str_replace(', ', ', ' . $tableAlias . '.', $update);

		return $update;
	}

	/** @noinspection DuplicatedCode */
	protected function makeQuery(array $parameters) : Main\ORM\Query\Query
	{
		$dataClass = $this->dataClass();
		$query = $dataClass::query();

		foreach ($parameters as $name => $value)
		{
			if ($name === 'filter')
			{
				$value instanceof Main\ORM\Query\Filter\ConditionTree ? $query->where($value) : $query->setFilter($value);
			}
			else if ($name === 'runtime')
			{
				foreach ($value as $runtimeName => $runtime)
				{
					$query->registerRuntimeField($runtimeName, $runtime);
				}
			}
			else
			{
				throw new Main\ArgumentException(sprintf('Unknown parameter: %s', $name));
			}
		}

		return $query;
	}

	protected function parseSql(string $sql) : array
	{
		if (!preg_match('/^SELECT\s.*?\sFROM(?<from>\s.*)(?<join>\s(?:LEFT |RIGHT |INNER )?JOIN\s.*?)?(?<where>\sWHERE\s.*?)$/si', $sql, $matches))
		{
			throw new Main\SystemException('cant parse sql select');
		}

		return $matches;
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