<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\Parts;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class ForCars implements Structure\Category, Structure\CategoryLevel
{
    use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::PRODUCT_TYPE;
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
		return new Dictionary\Compound(
		    (new Structure\DictionaryFactory(self::getLocalePrefix()))->make([
			    'all' => [
				    'other_tags' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_cars/for_cars.xml', [
						'known' => [
							Structure\CategoryLevel::SPARE_PART_TYPE,
							Structure\CategoryLevel::BODY_SPARE_PART_TYPE,
							Structure\CategoryLevel::ENGINE_SPARE_PART_TYPE,
							Structure\CategoryLevel::TRANSMISSION_SPARE_PART_TYPE,
						],
				    ]),
				    'Brand' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/partsbrands.xml'),
				    'Originality' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/originality.xml'),
				    'autoCatalog' => 'transportation/partsandaccessories/parts/autocatalog.xml',
			    ],
			    'SparePartType-->SPARE_PART_TYPE_BATTERIES' => [
				    'transportation/partsandaccessories/parts/producttype/for_cars/batteries/capacity.xml',
				    'transportation/partsandaccessories/parts/producttype/for_cars/batteries/dcl.xml',
				    'transportation/partsandaccessories/parts/producttype/for_cars/batteries/length.xml',
				    'transportation/partsandaccessories/parts/producttype/for_cars/batteries/width.xml',
				    'transportation/partsandaccessories/parts/producttype/for_cars/batteries/height.xml',
			    ],
		    ])
	    );
    }
}
