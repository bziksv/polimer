<?php

namespace Avito\Export\Admin\UserField\Concerns;

use Bitrix\Main;

trait HasCompatibleExtends
{
	public static function getCommonExtends() : string
	{
		throw new Main\NotImplementedException();
	}

	public static function getCompatibleExtends() : string
	{
		throw new Main\NotImplementedException();
	}

	protected static function callParent($name, array $arguments = [])
	{
		$classes = [
			static::getCommonExtends(),
			static::getCompatibleExtends(),
		];

		foreach ($classes as $className)
		{
			if (!method_exists($className, $name)) { continue; }

			return $className::{$name}(...$arguments);
		}

		throw new Main\NotImplementedException(sprintf(
			'method %s not implemented for parents of %s',
			$name,
			static::class
		));
	}
}
