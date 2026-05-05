<?php
namespace Avito\Export\Trading\Entity;

use Bitrix\Main;

class Registry
{
	/** @var Sale\Container */
	protected static $container;

	public static function environment() : Sale\Container
	{
		if (static::$container === null)
		{
			$container = static::buildEnvironment();
			$container->load();

			static::$container = $container;
		}

		return static::$container;
	}

	protected static function buildEnvironment() : Sale\Container
	{
		if (
			Main\ModuleManager::isModuleInstalled('crm')
			&& Main\ModuleManager::isModuleInstalled('intranet')
		)
		{
			$result = new SaleCrm\Container();
		}
		else
		{
			$result = new Sale\Container;
		}

		return $result;
	}
}

