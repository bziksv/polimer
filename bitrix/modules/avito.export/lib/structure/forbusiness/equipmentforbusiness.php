<?php
namespace Avito\Export\Structure\ForBusiness;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;
use Avito\Export\Feed;

class EquipmentForBusiness implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'MaterialType' => [ 'multiple' => true, 'wrapper' => true ],
			'HangerType' => [ 'multiple' => true, 'wrapper' => true ],
			'HangerMaterial' => [ 'multiple' => true, 'wrapper' => true ],
			'SorterCurrency' => [ 'multiple' => true, 'wrapper' => true ],
			'DetectorCurrency' => [ 'multiple' => true, 'wrapper' => true ],
			'invoice' => [ 'multiple' => true, 'wrapper' => true ],
			'VideoFileURL' => new Feed\Tag\VideoFileUrl(),
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('forbusiness/equipmentforbusiness/common.xml'),
			new Dictionary\XmlTree('forbusiness/equipmentforbusiness/country.xml'),
			new Dictionary\Fixed([
				'Condition' => new Dictionary\Listing\Condition(),
				'Availability' => new Dictionary\Listing\Availability(),
				'DeliverySubsidy' => new Dictionary\Listing\DeliverySubsidy(),
				'WeightForDelivery' => [],
				'LengthForDelivery' => [],
				'HeightForDelivery' => [],
				'WidthForDelivery' => [],
				'VideoFileURL' => [],
			]),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			return (new Factory(self::getLocalePrefix()))->make([
				self::getLocale('INDUSTRIAL') => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial.xml', [
							'known' => [
								CategoryLevel::GOODS_PROM_TYPE,
								CategoryLevel::PRIVOD_TYPE,
								CategoryLevel::ELECTRIC_TYPE,
								CategoryLevel::STANOK_TYPE,
								CategoryLevel::NASOS_TYPE,
								CategoryLevel::IZMERENIE_TYPE,
								CategoryLevel::KONVEIER_TYPE,
								CategoryLevel::SVARKA_TYPE,
								CategoryLevel::KLIMAT_TYPE,
								CategoryLevel::PACK_TYPE,
								CategoryLevel::SPEC_TYPE,
								CategoryLevel::TRANSFORMER_TYPE,
								CategoryLevel::RELAY_TYPE,
								CategoryLevel::GENERATOR_TYPE,
								CategoryLevel::COMPRESSOR_TYPE,
								CategoryLevel::PUMP_TYPE,
								CategoryLevel::DRYER_TYPE,
							],
						]),

						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/brand/generator.xml'), [
							'wait' => [ 'ElectricType' => self::getLocale('INDUSTRIAL_GENERATOR') ],
							'rename' => [ 'proizvoditel_generatorov' => 'GeneratorBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/brand/converter.xml'), [
							'wait' => [ 'ElectricType' => self::getLocale('INDUSTRIAL_CONVERTER') ],
							'rename' => [ 'proizvoditel_preobrazovatelya' => 'ConverterBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/brand/compressor.xml'), [
							'wait' => [ 'NasosType' => self::getLocale('INDUSTRIAL_COMPRESSOR') ],
							'rename' => [ 'proizvoditel_compressora' => 'CompressorBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/brand/dryer.xml'), [
							'wait' => [ 'NasosType' => self::getLocale('INDUSTRIAL_DRYER') ],
							'rename' => [ 'proizvoditel_osushitelya' => 'DryerBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/brand/sleeve.xml'), [
							'wait' => [ 'NasosType' => self::getLocale('INDUSTRIAL_SLEEVE') ],
							'rename' => [ 'proizvoditel_rvd' => 'SleeveBrand' ]
						]),

						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/industrial/dryer_dew_point.xml'), [
							'wait' => [ 'NasosType' => self::getLocale('INDUSTRIAL_DRYER') ],
						]),
					]),
				],
				self::getLocale('LOGISTICS_WAREHOUSE') => [
                    'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/logistics.xml', [
	                    'known' => [
		                    CategoryLevel::GOODS_SUB_TYPE,
		                    CategoryLevel::CONTAINER_TYPE,
		                    CategoryLevel::LIFTING_TYPE,
		                    CategoryLevel::CART_TYPE,
		                    CategoryLevel::STACKER_TYPE,
		                    CategoryLevel::ELEVATOR_TYPE,
		                    CategoryLevel::WINCH_TYPE,
		                    CategoryLevel::TACKELAZH_TYPE,
		                    CategoryLevel::CRANE_TYPE,
		                    CategoryLevel::GRAB_TYPE,
		                    CategoryLevel::STORAGE_TYPE,
	                    ],
                    ]),
                ],
				self::getLocale('FOOD') => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food.xml', [
							'known' => [
								CategoryLevel::GOODS_SUB_TYPE,
								CategoryLevel::EQUIPMENT_TYPE,
								CategoryLevel::COFFEE_MACHINE_TYPE,
								CategoryLevel::BREWERY_TYPE,
								CategoryLevel::JUICER_TYPE,
								CategoryLevel::NEUTRAL_SHELF_TYPE,
								CategoryLevel::DOUGH_MIXER_TYPE,
								CategoryLevel::DOUGH_ROLL_PURPOSE,
								CategoryLevel::MEAT_MASSAGER_TYPE,
							],
						]),

						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/graniters_and_juicers.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_GRANITERS') ],
							'rename' => [ 'proizvoditel_sokovizhymalki' => 'GraniterBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/coffee_machines.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COFFEE_MACHINES') ],
							'rename' => [ 'proizvoditel_b2b_kofemashiny' => 'CoffeeMachineBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/brewery_equipment.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_BREWERY_EQUIPMENT') ],
							'rename' => [ 'proizvoditel_pivovarennoe_oborudovanie' => 'BreweryBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/graniters_and_juicers.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_JUICERS') ],
							'rename' => [ 'proizvoditel_sokovizhymalki' => 'JuicerBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/umbrellas.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_UMBRELLAS') ],
							'rename' => [ 'proizvoditel_zonty_vytyazhnye' => 'ExtUmbrellaBrand' ]
						]),

						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/monoblock.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_MONOBLOCK') ],
							'rename' => [ 'proizvoditel_xolodilnye_monobloki' => 'MonoblockBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/ice_gen.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_ICE_GEN') ],
							'rename' => [ 'proizvoditel_ldogeneratory' => 'IceGenBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/chest.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_CHEST') ],
							'rename' => [ 'proizvoditel_morozilnye_lari' => 'ChestBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cool_slide.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOL_SLIDE') ],
							'rename' => [ 'proizvoditel_xolodilnye_gorki' => 'CoolSlideBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cool_table.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOL_TALBE') ],
							'rename' => [ 'proizvoditel_xolodilnye_stoly' => 'CoolTableBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/freezer.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_FREEZER') ],
							'rename' => [ 'proizvoditel_morozilnye_skafy' => 'FreezerBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cool_case.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOL_CASE') ],
							'rename' => [ 'proizvoditel_xolodilnye_vitriny' => 'CoolCaseBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cooler.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOLER') ],
							'rename' => [ 'proizvoditel_oxladiteli' => 'CoolerBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cool_room.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOL_ROOM') ],
							'rename' => [ 'proizvoditel_xolodilny_kamera' => 'CoolRoomBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forbusiness/equipmentforbusiness/food/brand/cool_cab.xml'), [
							'wait' => [ 'EquipmentType' => self::getLocale('FOOD_COOL_CAB') ],
							'rename' => [ 'proizvoditel_xolodilny_shkaf' => 'CoolCabBrand' ]
						]),
					]),
					'oldNames' => self::getLocale('FOR_RESTAURANT'),
				],
				self::getLocale('FOR_BEAUTY_SALON') => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/for_beauty_salon.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::EQUIPMENT_TYPE,
							CategoryLevel::PEDICURE_CHAIR_TYPE,
							CategoryLevel::STERILIZER_TYPE,
							CategoryLevel::BARBER_CHAIR_TYPE,
							CategoryLevel::LASER_EPIL_TYPE,
							CategoryLevel::PHOTO_EPIL_TYPE,
							CategoryLevel::ELECTRO_EPIL_TYPE,
							CategoryLevel::SOLARIUM_TYPE,
							CategoryLevel::TATOO_REMOVAL_TYPE,
						],
					]),
				],
				self::getLocale('FOR_CAR_BUSINESS') => [
                    'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/for_car_business.xml', [
	                    'known' => [
		                    CategoryLevel::GOODS_SUB_TYPE,
		                    CategoryLevel::WASH_TYPE,
		                    CategoryLevel::TIRES_TYPE,
		                    CategoryLevel::AUTO_PAINT_TYPE,
		                    CategoryLevel::STATION_TYPE,
		                    CategoryLevel::AUTO_DIAG_TYPE,
		                    CategoryLevel::AUTO_LIFT_TYPE,
		                    CategoryLevel::AUTO_ELEVATOR_TYPE,
	                    ],
                    ]),
                ],

				self::getLocale('MINING'),
				self::getLocale('LABORATORY') => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/laboratory.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
						],
					]),
				],
				self::getLocale('MEDICAL') => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/medical.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
						],
					]),
				],
				self::getLocale('TELECOMMUNICATION'),
				'Trade' => [
					'dictionary' => new Dictionary\XmlTree('forbusiness/equipmentforbusiness/trade.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::FURNITURE_TYPE,
							CategoryLevel::CASHIER_TYPE,
							CategoryLevel::ADVERTISING_TYPE,
							CategoryLevel::PAVILIONS_TYPE,
							CategoryLevel::ENTERTAINING_TYPE,
							CategoryLevel::INVENTORY_TYPE,
							CategoryLevel::MONITOR_TYPE,
							CategoryLevel::MANNEQUIN_TYPE,
							CategoryLevel::TENT_TYPE,
							CategoryLevel::SCANER_TYPE,
							CategoryLevel::CASH_BOX_TYPE,
							CategoryLevel::DETECTOR_TYPE,
							CategoryLevel::REGISTER_TYPE,
						],
					]),
				],
				self::getLocale('OTHER'),
			]);
		});
	}
}