<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\TiresRimsAndWheels;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class Wheels implements Structure\Category, Structure\CategoryLevel
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
				    'Brand' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tiresbrands.xml',
				    'Model' => new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/shiny.xml'), [
					    'rename' => [
						    'make' => 'Brand',
						    'model' => 'Model',
					    ],
				    ]),
				    'LoadIndex' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/loadindex.xml',
				    'SpeedIndex' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/speedindex.xml',
				    'other_tags' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/wheels.xml',
				    'RimDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/rimdiameter.xml',
				    'TireSectionWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/tiresectionwidth.xml',
				    'RimBoltsDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/rimboltsdiameter.xml',
				    'RimDIA' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/rimdia.xml',
				    'RimOffset' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/rimoffset.xml',
				    'RimWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels/rimwidth.xml',
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
