<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class BuildingMaterials implements Category, CategoryLevel
{
	use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		self::includeLocale();

		$factory = new Factory(self::getLocalePrefix());
		$factory->categoryLevel(CategoryLevel::GOODS_SUB_TYPE);

		return $factory->make([
			'Isolation',
			'Fasteners',
			'Roof and gutter',
			'Varnishes and paints',
			'Rolled metal' => [
				'dictionary' => new Dictionary\Compound([
					new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal.xml', [
						'known' => [
							CategoryLevel::MATERIAL_TYPE,
							CategoryLevel::MATERIAL_SUB_TYPE,
							CategoryLevel::PIPE_ROLLING_TYPE,
						],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/MetalSheetThickness.xml'), [
						'wait' => [ 'MaterialSubType' => self::getLocale('SHEET') ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/ReinforcementDiameter.xml'), [
						'wait' => [ 'MaterialSubType' => self::getLocale('REINFORCEMENT') ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/PipeWallThickness.xml'), [
						'wait' => [ 'PipeRollingType' => [ self::getLocale('ROUND_PIPE'), self::getLocale('RECTANGULAR_PIPE') ] ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/PipeWallDiameter.xml'), [
						'wait' => [ 'PipeRollingType' => self::getLocale('ROUND_PIPE') ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/PipeWallSize.xml'), [
						'wait' => [ 'PipeRollingType' => self::getLocale('RECTANGULAR_PIPE') ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/IBeamHeight.xml'), [
						'wait' => [ 'MaterialSubType' => self::getLocale('IBEAM') ],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/rolledmetal/tip_profilya_dvutavrovoj_balki.xml'), [
						'wait' => [ 'MaterialSubType' => self::getLocale('IBEAM') ],
						'rename' => [ 'tip_profilya_dvutavrovoj_balki' => 'IBeamProfileType' ],
					]),
				]),

			],
			'Finishing' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/finishing.xml', [
					'known' => [
						CategoryLevel::FINISHING_MATERIALS_TYPE,
						CategoryLevel::FLOORING_MATERIALS_SUB_TYPE,
						CategoryLevel::CERAMIC_PORCELAIN_TILES_SUB_TYPE,
						CategoryLevel::WALLPAPER_SUB_TYPE,
						CategoryLevel::EXTERIOR_FINISHING_DECORATIVE_STONE_SUB_TYPE,
						CategoryLevel::WALL_PANELS_SLATS_DECORATIVE_ELEMENTS_SUB_TYPE,
						CategoryLevel::TILE_TYPE,
						CategoryLevel::WALLPAPER_TYPE,
					],
				])
			],
			new BuildingMaterials\Lumber(),
			'Construction mixtures' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/constructionmixtures.xml', [
					'known' => [
						CategoryLevel::MIXES_TYPE,
					],
				])
			],
			'Construction of walls' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/constructionofwalls.xml', [
					'known' => [
						CategoryLevel::PRODUCT_TYPE,
						CategoryLevel::CONSTRUCTION_BLOCKS_TYPE,
						CategoryLevel::PANELS_TYPE,
						CategoryLevel::FORMWORK_TYPE,
						CategoryLevel::COMPONENTS_TYPE,
					],
				]),
			],
			'Electrics' => [
				'dictionary' => new Dictionary\Compound([
					new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/electrics.xml', [
						'known' => [
							CategoryLevel::ELECTRICS_TYPE,
							CategoryLevel::ELECTRICS_SUB_TYPE,
						],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/electrics/rozetki_vyklyuchateli_final.xml'), [
						'wait' => [ 'ElectricsSubType' => self::getLocale('SWITCHES_AND_SOCKETS') ],
						'rename' => [
							'brend' => 'Brand',
							'kollekciya' => 'Collection',
						],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/electrics/pribory_uchyota.xml'), [
						'wait' => [
							'DeviceType' => [
								self::getLocale('ELECTRONIC'),
								self::getLocale('ELECTROMECHANICAL')
							]
						],
						'rename' => [
							'brend_priborov_uchyota' => 'Brand',
							'seriya_priborov_uchota' => 'Series',
						],
					]),
					new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/electrics/sredstva_i_sistemy_ohrannopozharnoj_signalizacii.xml'), [
						'wait' => [ 'ElectricsSubType' => self::getLocale('FIRE_ALARM_SYSTEMS_AND_EQUIPMENT') ],
						'rename' => [
							'tip_ustroystva' => 'DeviceType',
							'brend' => 'Brand',
							'model' => 'Model',
						],
					]),
				]),
			],
			'Other',
			'Sheet materials' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/sheetmaterials.xml', [
					'known' => [
						CategoryLevel::PRODUCT_TYPE,
						CategoryLevel::PRODUCT_SUB_TYPE,
						CategoryLevel::POLYWOOD_TYPE,
						CategoryLevel::MATERIAL_TYPE,
					],
				])
			],
			'Ladders and accessories' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/laddersandaccessories.xml', [
					'known' => [
						CategoryLevel::PRODUCT_TYPE,
						CategoryLevel::COMPONENTS_TYPE,
						CategoryLevel::STAIRS_TYPE,
					],
				])
			],
			'Bulk materials' => [
				'dictionary' => new Dictionary\XmlTree('forhomeandgarden/buildingmaterials/bulkmaterials.xml', [
					'known' => [
						CategoryLevel::BULK_MATERIAL_TYPE,
						CategoryLevel::BULK_MATERIAL_SUB_TYPE,
						CategoryLevel::SOIL_TYPE,
						CategoryLevel::RUBBLE_TYPE,
						CategoryLevel::LAYING_TYPE,
					],
				])
			],
			new BuildingMaterials\Ironmongery(),
			new BuildingMaterials\Piles(),
		]);
	}
}