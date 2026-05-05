<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\TiresRimsAndWheels;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class TrucksAndSpecialVehicles implements Structure\Category, Structure\CategoryLevel
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
				    'other_tags' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/tires_for_trucks_and_special_equipment.xml',
				    'Brand' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/brand.xml',
				    'Model' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/model.xml',
				    'TireSectionWidth' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/tiresectionwidth.xml',
				    'RimDiameter' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/rimdiameter.xml',
				    'TireAspectRatio' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/tireaspectratio.xml',
			    ],
			    'VehicleType-->FOR_SPECIAL_VEHICLES' => [
				    'PlyRayting' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/plyrayting.xml',
			    ],
			    'VehicleType-->TRUCK' => [
				    'LoadIndex' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/loadindex.xml',
			    ],
			    'Condition-->CONDITION_USED' => [
				    'ResidualTreadSV' => 'transportation/partsandaccessories/tiresrimsandwheels/producttype/for_trucks_and_special_vehicles/residualtreadsv.xml',
			    ],
		    ])
	    );
    }
}
