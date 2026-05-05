<?php
namespace Avito\Export\Structure\ForHomeAndGarden;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;

class RepairAndConstruction implements Category, CategoryLevel
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Fixed([
			'AdType' => new Dictionary\Listing\AdType(),
			'Condition' => new Dictionary\Listing\Condition(),
		]);
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			/** @noinspection SpellCheckingInspection */
			return (new Factory(self::getLocalePrefix()))->make([
				new RepairAndConstruction\PrefabricatedStructuresAndLogCabins([
					'name' => self::getLocale('PREFABRICATED_STRUCTURES_AND_LOG_CABINS'),
				]),
				new RepairAndConstruction\BuildingMaterials(),
				new RepairAndConstruction\PlumbingWaterAndSauna([
					'name' => self::getLocale('PLUMBING_WATER_AND_SAUNA'),
					'oldNames' => self::getLocale('PLUMBING_WATER_AND_SAUNA_OLD_NAMES')
				]),
				new RepairAndConstruction\Tools([
					'name' => self::getLocale('TOOLS'),
					'tags' => (new TagFactory())->make([
						'SawMaterialOption' => [ 'multiple' => true, 'wrapper' => true ],
					]),
				]),
				'Windows And Balconies' => [
					'dictionary' =>	new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/windows_and_balconies.xml', [
							'known' => [
								CategoryLevel::GOODS_SUB_TYPE,
								CategoryLevel::BUSINESS_SUB_TYPE,
								CategoryLevel::DOOR_OPEN_TYPE,
							],
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/windows_brands.xml'), [
							'wait' => [ 'GoodsSubType' => self::getLocale('WINDOWS_AND_BALCONIES_WINDOWS') ],
							'rename' => [ 'brend_profilya' => 'ProfileBrand' ]
						])
					]),
				],
				'Ceilings',
				'Doors' => [
					'dictionary' =>	new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/doors.xml', [
						'known' => [
							CategoryLevel::DOOR_TYPE,
							CategoryLevel::DOOR_OPEN_TYPE,
							CategoryLevel::ENTRANCE_DOOR_TYPE,
						],
					]),
				],
				'Fireplaces And Heaters' => [
					'dictionary' =>	new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/fireplaces_and_heaters.xml', [
							'known' => [
								CategoryLevel::GOODS_SUB_TYPE,
								CategoryLevel::OVENS_TYPE,
								CategoryLevel::HEATING_TYPE,
								CategoryLevel::COMPONENTS_TYPE,
								CategoryLevel::PRODUCT_TYPE,
								CategoryLevel::BOILER_TYPE,
							],
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/boilers_brands.xml'), [
							'wait' => [ 'HeatingType' => self::getLocale('FIREPLACES_AND_HEATERS_BOILERS') ],
							'rename' => [ 'brend' => 'Brand' ]
						])
					]),
				],
				'For Garden And Cottage' => [
					'dictionary' =>	new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/for_garden_and_cottage.xml', [
							'known' => [
								CategoryLevel::PRODUCT_GARGEN_TYPE,
								CategoryLevel::GARDEN_EQUIPMENT_SUB_TYPE,
								CategoryLevel::COMPONENTS_SUB_TYPE,
								CategoryLevel::IRRIGATION_SUB_TYPE,
								CategoryLevel::GARDEN_TOOLS_SUB_TYPE,
								CategoryLevel::PRODUCT_TYPE,
							],
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/forgarden/gazonokosilki.xml'), [
							'wait' => [ 'GardenEquipmentSubType' => self::getLocale('FOR_GARDEN_LAWN_MOWERS') ],
							'rename' => [
								'brend' => 'Brand',
								'model' => 'Model',
								'tip_gazonokosilki' => 'LawnMowerType',
								'samohodnaya' => 'SelfPropelled',
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/forgarden/snegouborshiki.xml'), [
							'wait' => [ 'GardenEquipmentSubType' => self::getLocale('FOR_GARDEN_SNOW_BLOWERS') ],
							'rename' => [
								'brend' => 'Brand',
								'model' => 'Model',
								'tip_snegouborshika' => 'SnowBlowerType',
								'tip_peredvizheniya' => 'MovementType',
							]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/forgarden/trimmery.xml'), [
							'wait' => [ 'GardenEquipmentSubType' => self::getLocale('FOR_GARDEN_SNOW_TRIMMERS') ],
							'rename' => [
								'brend' => 'Brand',
								'model' => 'Model',
								'tip_trimmera' => 'TrimmerType',
							]
						]),
						new Dictionary\Decorator(
							new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/benzopily2.xml'),
							[
								'rename' => [
									'brend' => 'Brand',
									'model' => 'Model',
									'dlina_shiny' => 'TireLength',
								],
								'wait' => [ 'GardenEquipmentSubType' => self::getLocale('FOR_GARDEN_CHAINSAW') ],
							]
						),
					]),
					'oldNames' => self::getLocale('GARDEN_APPLIANCES'),
				],
				'Gates and fences' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences.xml', [
							'known' => [
								CategoryLevel::FENCE_TYPE,
								CategoryLevel::GATE_SUB_TYPE,
								CategoryLevel::FENCE_SUB_TYPE,
								CategoryLevel::ROLLERSHUTTERS_SUB_TYPE,
								CategoryLevel::GUARDRAIL_SUB_TYPE,
							],
						]),

						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences/brand_section.xml'), [
							'wait' => [ 'GateSubType' => self::getLocale('GATES_AND_FENCES_SECTION_GATES') ],
							'rename' => [ 'brend_sekcionnyh_vorot' => 'SectionalGateType' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences/brand_sliding.xml'), [
							'wait' => [ 'GateSubType' => self::getLocale('GATES_AND_FENCES_SLIDING_GATES') ],
							'rename' => [ 'brend_otkatnyh_vorot' => 'SlidingGatesBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences/brand_automation.xml'), [
							'wait' => [ 'GateSubType' => self::getLocale('GATES_AND_FENCES_AUTOMATION_GATES') ],
							'rename' => [ 'brend_avtomatiki_dlya_vorot' => 'GateAutomationBrand' ]
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences/gate_automation_type.xml'), [
							'wait' => [ 'GateSubType' => self::getLocale('GATES_AND_FENCES_AUTOMATION_GATES') ],
						]),
						new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/gatesandfences/brand_swing.xml'), [
							'wait' => [ 'GateSubType' => self::getLocale('GATES_AND_FENCES_SWING_GATES') ],
							'rename' => [ 'brend_raspashnyh_vorot' => 'SwingGatesBrand' ]
						]),
					]),
				],
			]);
		});
	}
}
