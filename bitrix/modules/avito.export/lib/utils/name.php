<?php
namespace Avito\Export\Utils;

class Name
{
	public static function screamingSnakeCase(string $name) : string
	{
		/** @var string $name fix phpStorm */
		$name = str_replace('\\', '_', $name);
		$name = preg_replace('/([A-Z]+)/', '_$1', $name);
		$name = preg_replace('/__+/', '_', $name);
		$name = ltrim($name, '_');
		$name = mb_strtoupper($name);

		return $name;
	}
}