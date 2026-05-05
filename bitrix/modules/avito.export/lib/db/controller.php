<?php

namespace Avito\Export\DB;

use Avito\Export\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Controller
{
	public static function createTables(array $classList = null):void
	{
		if ($classList === null)
		{
			$classList = static::getTablesClassList(Table::class);
		}

		/** @var Table $className */
		foreach ($classList as $className)
		{
			$installer = new Installer($className::getEntity());
			$installer->recheck();
		}
	}

	/**
	 * @param $baseClassName
	 *
	 * @return array
	 */
	protected static function getTablesClassList($baseClassName):array
	{
		$baseDir = Config::getModulePath();
		$baseNamespace = Config::getNamespace();
		$directory = new RecursiveDirectoryIterator($baseDir);
		$iterator = new RecursiveIteratorIterator($directory);
		$result = [];

		/** @var \DirectoryIterator $entry */
		foreach ($iterator as $entry)
		{
			if ($entry->isFile()
				&& $entry->getExtension() === 'php')
			{
				$relativePath = str_replace($baseDir, '', $entry->getPath());
				$namespace = $baseNamespace . str_replace('/', '\\', $relativePath) . '\\';
				$className = $entry->getBasename('.php');

				if (!preg_match('/table$/', $className))
				{
					$className .= 'Table';
				}

				$fullClassName = $namespace . $className;

				if (
					class_exists($fullClassName)
					&& is_subclass_of($fullClassName, $baseClassName)
				)
				{
					$result[] = mb_strtolower($fullClassName);
				}
			}
		}

		return array_unique($result);
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function dropTables():void
	{
		$className = Table::class;
		$classList = static::getTablesClassList($className);

		/** @var Table $className */
		foreach ($classList as $className)
		{
			$entity = $className::getEntity();
			$connection = $entity->getConnection();
			$tableName = $entity->getDBTableName();

			if ($connection->isTableExists($tableName))
			{
				$connection->dropTable($tableName);
			}
		}
	}
}
