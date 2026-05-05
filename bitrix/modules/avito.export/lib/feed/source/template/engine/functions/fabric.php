<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;

class Fabric
{
	protected static $cached = [];

	public static function make(string $name)
	{
		if (isset(static::$cached[$name])) { return static::$cached[$name]; }

		$iblockFunction = Iblock\Template\Functions\Fabric::createInstance($name);

		if (static::isDummy($iblockFunction) && Registry::isExists($name))
		{
			$result = Registry::make($name);
			static::$cached[$name] = $result;
		}
		else
		{
			$result = $iblockFunction;
		}

		return $result;
	}

	protected static function isDummy($function) : bool
	{
		return !is_subclass_of($function, Iblock\Template\Functions\FunctionBase::class);
	}
}