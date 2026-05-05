<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Custom;

class PlumbingWaterAndSauna extends Custom
{
	use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/plumbingwaterandsauna.xml', [
				'known' => [
					CategoryLevel::GOODS_SUB_TYPE,
					CategoryLevel::PRODUCT_TYPE,
					CategoryLevel::PRODUCT_SUB_TYPE,
					CategoryLevel::BATHROOM_ACCESSORIES_TYPE,
					CategoryLevel::SHOWER_ENCLOSURE_TYPE,
					CategoryLevel::BATHING_TANK_TYPE,
					CategoryLevel::KIT,
					CategoryLevel::PLUMBING_SUB_TYPE,
					CategoryLevel::VALVE_TYPE,
					CategoryLevel::DEVICE_TYPE,
				],
			]),

			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/plumbingwaterandsauna/shower_cabin_size.xml'), [
				'wait' => [ 'ProductType' => self::getLocale('SHOWER_CABINS') ],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/plumbingwaterandsauna/shower_enclosure_brand.xml'), [
				'wait' => [ 'ProductType' => self::getLocale('SHOWER_ENCLOSURES') ],
				'rename' => [ 'brend' => 'ShowerEnclosureBrand'],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_installyacij.xml'), [
				'wait' => [ 'ProductType' => self::getLocale('SHOWER_INSTALLATION') ],
				'rename' => [ 'brend' => 'Brand'],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_unitaza.xml'), [
				'wait' => [ 'ProductType' => self::getLocale('TOILETS_AND_CISTERNS') ],
				'rename' => [ 'brend' => 'Brand'],
			]),
		]);
	}
}