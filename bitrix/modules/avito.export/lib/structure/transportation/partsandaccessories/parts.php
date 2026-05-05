<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class Parts implements Category, CategoryLevel
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\NoValue();
    }

	public function children() : array
	{
		self::includeLocale();

		$factory = new Factory(self::getLocalePrefix());
		$factory->categoryLevel(CategoryLevel::PRODUCT_TYPE);

		return $factory->make([
			new Parts\ForMotorcycles(),
			new Parts\ForCars(),
			new Parts\ForSpecialVehicles(),
			'For Water Vehicles' => [
				'dictionary' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/partsbrands.xml'),
			],
		]);
	}
}
