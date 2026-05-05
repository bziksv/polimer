<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\TiresRimsAndWheels;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Rims implements Structure\Category, Structure\CategoryLevel
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
		$dictionaryFactory = new Structure\DictionaryFactory(self::getLocalePrefix());

	    return new Dictionary\Compound(
		    $dictionaryFactory->make([
			    'all' => [
				    'other_tags' => new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rims.xml', [
					    'known' => [
						    Structure\CategoryLevel::RIM_TYPE,
					    ],
				    ]),
				    'RimDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimdiameter.xml',
				    'RimWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimwidth.xml',
				    'RimBoltsDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimboltsdiameter.xml',
				    'RimOffset' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimoffset.xml',
				    'RimDIA' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimdia.xml',
				    'RimBrand' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimbrand.xml',
				    'RimModel' => new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimmodel.xml'), [
						'rename' => [
							'marka' => 'RimBrand',
							'model' => 'RimModel',
						],
				    ]),
			    ]
		    ])
	    );
    }
}
