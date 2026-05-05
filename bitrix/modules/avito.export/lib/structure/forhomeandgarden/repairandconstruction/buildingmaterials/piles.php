<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\BuildingMaterials;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;
use Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties;

class Piles implements Structure\Category, Structure\CategoryLevel
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
	    return new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/piles.xml', [
			'known' => [
				Structure\CategoryLevel::PILE_TYPE,
			],
	    ]);
    }
}
