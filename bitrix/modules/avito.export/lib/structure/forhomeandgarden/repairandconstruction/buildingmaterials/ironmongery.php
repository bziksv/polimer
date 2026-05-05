<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\BuildingMaterials;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;
use Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties;

class Ironmongery implements Structure\Category, Structure\CategoryLevel
{
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::GOODS_SUB_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function children() : array
	{
		return [];
	}

	public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Compound([
			new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/ironmongery.xml', [
			    'known' => [
				    Structure\CategoryLevel::PURPOSE,
				    Structure\CategoryLevel::PRODUCT_TYPE,
				    Structure\CategoryLevel::FLOOR_SLAB_TYPE,
				    Structure\CategoryLevel::PIPE_TYPE,
				    Structure\CategoryLevel::PRODUCT_KIND,
			    ],
		    ]),
			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/ironmongery/markirovka_dorozhnoj_plity.xml'), [
				'wait' => [ 'ProductType' => self::getLocale('RC_ROAD_SLAB') ],
				'rename' => [ 'markirovka_dorozhnoj_plity' => 'RCRoadSlabMarking' ],
			]),
	    ]);
    }
}
