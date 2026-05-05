<?php
namespace Avito\Export\Structure\Transportation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class SpecialVehicles implements Category, CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\Fixed([
				'Availability' => new Dictionary\Listing\Availability(),
			]),
			new Dictionary\XmlTree('transportation/specialvehicles/common.xml'),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = new Factory(self::getLocalePrefix());
			$factory->categoryLevel(CategoryLevel::GOODS_TYPE);

			return $factory->make([
				'Other' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/othertransport/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/other_transport.xml', [
							'known' => [
								CategoryLevel::TYPE_OF_VEHICLE,
								CategoryLevel::SUB_TYPE_OF_VEHICLE,
							],
						]),
					]),
				],
				'Construction Equipment' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/construction_machinery.xml', [
						'known' => [
							CategoryLevel::TYPE_OF_VEHICLE,
						],
					]),
				],
				'Buses' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/bus/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/bus.xml'),
					]),
				],
				'Trucks' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/truck/main.xml'),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('transportation/specialvehicles/truck/crane_arm.xml'),
							[
								'rename' => [
									'Make' => 'MakeKmu',
									'Model' => 'ModelKmu',
									'TypeOfVehicle' => 'TypeOfVehicleKmu',
								]
							]
						),
					]),
				],
				'Trailers' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/trailer/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/trailer.xml', [
							'known' => [
								CategoryLevel::TYPE_OF_VEHICLE,
								CategoryLevel::TYPE_OF_TRAILER,
							],
						]),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('transportation/specialvehicles/truck/crane_arm.xml'),
							[
								'rename' => [
									'Make' => 'MakeKmu',
									'Model' => 'ModelKmu',
									'TypeOfVehicle' => 'TypeOfVehicleKmu',
								]
							]
						),
					]),
				],
				'Loaders' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/loader.xml'),
				],
				'Bulldozers' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/bulldozer/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/bulldozer.xml'),
					]),
				],
				'Autocranes' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/autocrane/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/autocrane.xml', [
							'known' => [
								CategoryLevel::TYPE_OF_VEHICLE,
							]
						]),
					]),
				],
				'Excavators' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/excavators.xml'),
				],
				'Motorhomes' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/motorhome/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/motorhome.xml', [
							'known' => [
								CategoryLevel::TYPE_OF_VEHICLE,
							]
						]),
					]),
				],
				'Agricultural Machinery' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/agricultural_machinery.xml', [
						'known' => [
							CategoryLevel::TYPE_OF_VEHICLE,
						],
					]),
				],
				'Logging Machinery' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/logging_machinery.xml'),
				],
				'Machinery Attachment' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/machinery_attachment.xml', [
						'known' => [
							CategoryLevel::TYPE_OF_VEHICLE,
						],
					]),
				],
				'Municipal Machinery' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/municipalmachinery/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/municipal_machinery.xml', [
							'known' => [
								CategoryLevel::TYPE_OF_VEHICLE,
							],
						]),
					]),
				],
				'Trailer Trucks' => [
					'dictionary' => new Dictionary\XmlTree('transportation/specialvehicles/trailer_truck.xml', [
						'known' => [
							CategoryLevel::TYPE_OF_VEHICLE,
						],
					]),
				],
				'Light Commercial Vehicles' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('transportation/specialvehicles/lightcommercialvehicles/main.xml'),
						new Dictionary\XmlTree('transportation/specialvehicles/lcv.xml'),
						new Dictionary\Decorator(
							new Dictionary\XmlTree('transportation/specialvehicles/truck/crane_arm.xml'),
							[
								'rename' => [
									'Make' => 'MakeKmu',
									'Model' => 'ModelKmu',
									'TypeOfVehicle' => 'TypeOfVehicleKmu',
								]
							]
						),
					]),
				],
			]);
		});
	}
}