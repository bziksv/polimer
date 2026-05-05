<?php
namespace Avito\Export\Trading\Service;

class Registry
{
	/** @var Container */
	protected static $container;

	public static function service() : Container
	{
		if (static::$container === null)
		{
			static::$container = new Container();
		}

		return static::$container;
	}
}
