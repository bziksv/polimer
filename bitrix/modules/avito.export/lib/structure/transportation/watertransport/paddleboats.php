<?php
namespace Avito\Export\Structure\Transportation\WaterTransport;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Paddleboats implements Structure\Category, Structure\CategoryLevel
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::VEHICLE_TYPE;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		return [];
	}
}