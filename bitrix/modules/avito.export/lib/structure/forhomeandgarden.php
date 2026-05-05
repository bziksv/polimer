<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;

class ForHomeAndGarden implements Category
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
			'Availability' => new Dictionary\Listing\Availability(),
			'DeliverySubsidy' => new Dictionary\Listing\DeliverySubsidy(),
			'WeightForDelivery' => [],
			'LengthForDelivery' => [],
			'HeightForDelivery' => [],
			'WidthForDelivery' => [],
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = new Factory(self::getLocalePrefix());
			$factory->categoryLevel(CategoryLevel::CATEGORY);

			return array_merge(
				[
					new ForHomeAndGarden\FurnitureAndInterior(),
				],
				$factory->make([
					'Plants' => [
						'dictionary' => new Dictionary\XmlTree('forhomeandgarden/plants.xml', [
							'known' => [
								CategoryLevel::GOODS_TYPE,
								CategoryLevel::PRODUCT_PLANT_TYPE,
								CategoryLevel::HOUSE_PLANT_TYPE,
								CategoryLevel::GARDEN_PLANT_TYPE,
								CategoryLevel::PRODUCT_TYPE,
							]
						])
					],
					'Foods' => [
						'dictionary' => new Dictionary\XmlTree('forhomeandgarden/foods.xml', [
							'known' => [
								CategoryLevel::GOODS_TYPE,
								CategoryLevel::GOODS_SUB_TYPE,
							]
						])
					]
				]),
				[
					new ForHomeAndGarden\RepairAndConstruction(),
					new ForHomeAndGarden\DishesAndProductsKitchen(),
					new ForHomeAndGarden\HomeAppliances(),
				]
			);
		});
	}
}