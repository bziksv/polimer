<?php

namespace Avito\Export\DB;

use Bitrix\Main;

class Installer
{
	protected $entity;
	protected $connection;

	public function __construct(Main\ORM\Entity $entity)
	{
		$this->entity = $entity;
		$this->connection = $entity->getConnection();
	}

	public function recheck() : void
	{
		if ($this->isInstalled() || $this->isOldInstalled())
		{
			$this->migrate();
		}
		else if (!$this->isDeprecated())
		{
			$this->install();
		}
	}

	public function isInstalled() : bool
	{
		return $this->connection->isTableExists($this->entity->getDBTableName());
	}

	public function install() : void
	{
		$this->createTable();
		$this->createIndexes();
		$this->alterArrayText();
	}

	public function migrate() : void
	{
		if (!$this->isInstalled() && $this->isOldInstalled())
		{
			$this->renameOldTable();
		}

		$this->userMigration();
		$this->createIndexes();
	}

	protected function createTable() : void
	{
		$this->entity->createDbTable();
	}

	protected function alterArrayText() : void
	{
		foreach ($this->entity->getFields() as $field)
		{
			if (!($field instanceof Main\ORM\Fields\ArrayField)) { continue; }

			$sqlHelper = $this->connection->getSqlHelper();
			$tableName = $this->entity->getDBTableName();
			$columnName = $field->getColumnName();

			$this->connection->queryExecute(sprintf(
				'ALTER TABLE %s MODIFY COLUMN %s text',
				$sqlHelper->quote($tableName),
				$sqlHelper->quote($columnName)
			));
		}
	}

	protected function createIndexes() : void
	{
		$className = $this->entity->getDataClass();
		$connection = $this->entity->getConnection();
		$tableName = $this->entity->getDBTableName();

		if (!is_subclass_of($className, Table::class)) { return; }

		foreach ($className::getTableIndexes() as $index => $fields)
		{
			$name = 'IX_' . $tableName . '_' . $index;

			if ($connection->isIndexExists($tableName, $fields)) { continue; }

			$connection->createIndex($tableName, $name, $fields);
		}
	}

	protected function isDeprecated() : bool
	{
		$dataClass = $this->entity->getDataClass();

		if (!method_exists($dataClass, 'migrateDeprecated')) { return false; }

		return $dataClass::migrateDeprecated();
	}

	protected function isOldInstalled() : bool
	{
		$oldName = $this->oldName();

		return $oldName !== null && $this->connection->isTableExists($oldName);
	}

	protected function renameOldTable() : void
	{
		$oldName = $this->oldName();

		if ($oldName === null) { return; }

		$this->connection->queryExecute(sprintf(
			'ALTER TABLE %s RENAME TO %s',
			$oldName,
			$this->entity->getDBTableName()
		));
	}

	protected function oldName() : ?string
	{
		$dataClass = $this->entity->getDataClass();

		if (!method_exists($dataClass, 'migrateTableName')) { return null; }

		return $dataClass::migrateTableName();
	}

	protected function userMigration() : void
	{
		$dataClass = $this->entity->getDataClass();

		if (!method_exists($dataClass, 'migrate')) { return; }

		$dataClass::migrate($this->connection);
		$this->connection->clearCaches();
	}
}