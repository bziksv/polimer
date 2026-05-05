<?php
namespace Avito\Export\Feed\Source;

class ContextPool
{
	protected static $instances = [];

	public static function iblockInstance(int $iblockId) : Context
	{
		if (!isset(static::$instances[$iblockId]))
		{
			static::$instances[$iblockId] = new Context($iblockId);
		}

		return static::$instances[$iblockId];
	}
}