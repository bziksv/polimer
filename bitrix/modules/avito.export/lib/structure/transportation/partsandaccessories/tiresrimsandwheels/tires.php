<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\TiresRimsAndWheels;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Tires implements Structure\Category, Structure\CategoryLevel
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
				    'other_tags' => new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tires.xml', [
					    'known' => [
						    Structure\CategoryLevel::TIRE_TYPE,
					    ],
				    ]),
				    'Brand' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tiresbrands.xml',
				    'Model' => new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/shiny.xml'), [
					    'rename' => [
						    'make' => 'Brand',
						    'model' => 'Model',
					    ],
				    ]),
				    'TireSectionWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tiresectionwidth.xml',
				    'RimDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/rimdiameter.xml',
				    'TireAspectRatio' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tireaspectratio.xml',
				    'LoadIndex' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/loadindex.xml',
				    'Homologation' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/homologation.xml',
				    'SpeedIndex' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/speedindex.xml',
			    ],
			    'Condition-->CONDITION_USED' => [
				    'ResidualTread' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/residualtread.xml',
			    ],
			    'DifferentWidthTires-->DIFFERENT_WIDTH_TIRES_YES' => [
				    'BackRimDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/backrimdiameter.xml',
				    'BackTireAspectRatio' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/backtireaspectratio.xml',
				    'BackTireSectionWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/backtiresectionwidth.xml',
			    ],
			])
	    );
    }
}
