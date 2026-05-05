<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure;

class OilsAndChemicals implements Structure\Category, Structure\CategoryLevel, Structure\CategoryWithTags
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return Structure\CategoryLevel::GOODS_TYPE;
	}

	public function tags() : array
	{
		return (new Structure\TagFactory())->make([
			'ACEA' => [ 'multiple' => true, 'wrapper' => true ],
			'API' => [ 'multiple' => true, 'wrapper' => true ],
			'OEMOil' => [ 'multiple' => true, 'wrapper' => true ],
			'ATF' => [ 'multiple' => true, 'wrapper' => true ],
			'DOT' => [ 'multiple' => true, 'wrapper' => true ],
			'ASTM' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\Fixed([
				'ProductType' => [
					self::getLocale('PRODUCT_TYPE_MOTOR_OILS'),
					self::getLocale('PRODUCT_TYPE_GEAR_OILS'),
					self::getLocale('PRODUCT_TYPE_COOLANTS'),
					self::getLocale('PRODUCT_TYPE_BRAKE_FLUIDS'),
					self::getLocale('PRODUCT_TYPE_HYDRAULIC_FLUIDS'),
					self::getLocale('PRODUCT_TYPE_WINDOW_WASHER_FLUIDS'),
					self::getLocale('PRODUCT_TYPE_FLUSHING_FLUIDS_ADDITIVES_AND_LUBRICANTS'),
					self::getLocale('PRODUCT_TYPE_OTHER_OILS'),
					self::getLocale('PRODUCT_TYPE_CAR_CARE_PRODUCTS_AND_ACCESSORIES'),
					self::getLocale('PRODUCT_TYPE_FUELS'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_brand.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_sae.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_volume.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_acea.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_api.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_oem.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/motor_other.xml'),
				new Dictionary\Fixed([ 'VendorCode' => [] ]),
			]), [
				'wait' => [ 'ProductType' => self::getLocale('PRODUCT_TYPE_MOTOR_OILS') ],
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_brand.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_sae.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_volume.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_api.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_oem.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/gear_atf.xml'),
				new Dictionary\Fixed([ 'VendorCode' => [] ]),
			]), [
				'wait' => [ 'ProductType' => self::getLocale('PRODUCT_TYPE_GEAR_OILS') ],
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/hydraulic_brand.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/hydraulic_volume.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/hydraulic_oem.xml'),
				new Dictionary\Fixed([ 'VendorCode' => [] ]),
			]), [
				'wait' => [ 'ProductType' => self::getLocale('PRODUCT_TYPE_HYDRAULIC_FLUIDS') ],
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/brake_brand.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/brake_volume.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/brake_oem.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/brake_dot.xml'),
				new Dictionary\Fixed([ 'VendorCode' => [] ]),
			]), [
				'wait' => [ 'ProductType' => self::getLocale('PRODUCT_TYPE_BRAKE_FLUIDS') ],
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/coolants_brand.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/coolants_volume.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/coolants_oem.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/coolants_color.xml'),
				new Dictionary\XmlTree('transportation/partsandaccessories/oilsandchemicals/coolants_astm.xml'),
				new Dictionary\Fixed([ 'VendorCode' => [] ]),
			]), [
				'wait' => [ 'ProductType' => self::getLocale('PRODUCT_TYPE_COOLANTS') ],
			]),
		]);
	}

	public function children() : array
	{
		return [];
	}
}