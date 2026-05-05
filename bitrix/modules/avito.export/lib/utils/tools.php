<?php

namespace Avito\Export\Utils;

use Avito\Export\Config;

class Tools
{
	public static function getClassList($baseClassName):array
	{
		$baseDir = Config::getModulePath();
		$baseNamespace = Config::getNamespace();
		$directory = new \RecursiveDirectoryIterator($baseDir);
		$iterator = new \RecursiveIteratorIterator($directory);
		$result = [];

		/** @var \DirectoryIterator $entry */
		foreach ($iterator as $entry)
		{
			if ($entry->isFile()
				&& $entry->getExtension() === 'php')
			{
				$relativePath = str_replace($baseDir, '', $entry->getPath());
				$className =
					$baseNamespace . str_replace('/', '\\', $relativePath) . '\\' . $entry->getBasename('.php');
				$tableClassName = $className . 'Table';

				if (!empty($relativePath)
					&& !class_exists($tableClassName)
					&& class_exists($className)
					&& is_subclass_of($className, $baseClassName))
				{
					$result[] = $className;
				}
			}
		}

		return $result;
	}
}
