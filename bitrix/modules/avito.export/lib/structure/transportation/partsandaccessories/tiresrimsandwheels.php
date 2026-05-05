<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class TiresRimsAndWheels implements Category, CategoryLevel
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		return [
			new TiresRimsAndWheels\Tires(),
			new TiresRimsAndWheels\MotoTires(),
			new TiresRimsAndWheels\Rims(),
			new TiresRimsAndWheels\Wheels(),
			new TiresRimsAndWheels\TrucksAndSpecialVehicles(),
			new TiresRimsAndWheels\Caps(),
		];
	}
}
