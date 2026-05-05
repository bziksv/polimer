<?php
namespace Avito\Export\Trading\State;

class Repository
{
	protected static $loaded = [];

	public static function forOrder(int $externalId) : Order
	{
		if (!isset(static::$loaded[$externalId]))
		{
			static::$loaded[$externalId] = new Order($externalId);
		}

		return static::$loaded[$externalId];
	}
}