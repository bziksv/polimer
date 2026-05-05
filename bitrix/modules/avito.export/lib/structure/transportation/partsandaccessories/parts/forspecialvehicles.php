<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\Parts;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class ForSpecialVehicles implements Structure\Category, Structure\CategoryLevel, Structure\CategoryCompatible
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

	public function oldNames() : array
	{
		return [
			self::getLocale('NAME_OLD'),
		];
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
				    'other_tags' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_special_vehicles/for_special_vehicles.xml', [
					    'known' => [
						    Structure\CategoryLevel::SPARE_PART_TYPE,
						    Structure\CategoryLevel::TECHNIC_SPARE_PART_TYPE,
						    Structure\CategoryLevel::TECHNIC_ADD_ON_TYPE,
						    Structure\CategoryLevel::HYDRAULIC_DESIGN_TYPE,
						    Structure\CategoryLevel::HYDRAULIC_DISTRIBUTOR_TYPE,
					    ],
				    ]),
				    'Originality' => new Dictionary\XmlTree('transportation/partsandaccessories/parts/originality.xml'),
			    ],
			    'Technic-->TECHNIC_BUSES' => [ 'transportation/specialvehicles/bus.xml' ],
			    'Technic-->TECHNIC_MOTOR_HOMES' => [ 'transportation/specialvehicles/motorhome.xml' ],
			    'Technic-->TECHNIC_TRUCK_CRANES' => [ 'transportation/specialvehicles/autocrane.xml' ],
			    'Technic-->TECHNIC_BULLDOZERS' => [ 'transportation/specialvehicles/bulldozer.xml' ],
			    'Technic-->TECHNIC_TRUCKS' => [ 'transportation/specialvehicles/truck_catalog.xml' ],
			    'Technic-->TECHNIC_LOADERS' => [ 'transportation/specialvehicles/loader.xml' ],
			    'Technic-->TECHNIC_TRAILERS' => [ 'transportation/specialvehicles/trailer.xml' ],
			    'Technic-->TECHNIC_AGRICULTURAL_MACHINERY' => [ 'transportation/specialvehicles/agricultural_machinery.xml' ],
			    'Technic-->TECHNIC_CONSTRUCTION_EQUIPMENT' => [ 'transportation/specialvehicles/construction_machinery.xml' ],
			    'Technic-->TECHNIC_TRACTORS' => [ 'transportation/specialvehicles/trailer_truck.xml' ],
			    'Technic-->TECHNIC_EXCAVATORS' => [ 'transportation/specialvehicles/excavators.xml' ],
			    'Technic-->TECHNIC_MUNICIPAL_EQUIPMENT' => [ 'transportation/specialvehicles/municipal_machinery.xml' ],
			    'Technic-->TECHNIC_LIGHT_COMMERCIAL_VEHICLES' => [
					new Dictionary\Decorator(new Dictionary\XmlTree('transportation/specialvehicles/lcv.xml'), [
						'rename' => [
							'Brand' => 'Make',
						],
						'include' => [ 'Make', 'Model' ],
			        ]),
			    ],
			    'Technic-->TECHNIC_ATTACHMENTS' => [ 'transportation/specialvehicles/machinery_attachment.xml' ],
			    'Technic-->TECHNIC_OTHER' => [ 'transportation/specialvehicles/other_transport.xml' ],
		    ])
	    );
    }
}
