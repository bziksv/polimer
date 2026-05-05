<?php
namespace Avito\Export\Concerns;

use Avito\Export\Utils\Caller;

trait HasOnceStatic
{
	private static $onceStaticMemoized = [];

	protected static function onceStatic(string $name, callable $callable, ...$arguments)
	{
		$sign = $name;
		$sign .= !empty($arguments) ? '-' . Caller::argumentsHash(...$arguments) : '';

		if (!isset(static::$onceStaticMemoized[$sign]) && !array_key_exists($sign, static::$onceStaticMemoized))
		{
			static::$onceStaticMemoized[$sign] = call_user_func($callable, ...$arguments);
		}

		return static::$onceStaticMemoized[$sign];
	}
}