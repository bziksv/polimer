<?php
namespace Avito\Export\Structure\ForHomeAndGarden;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class DishesAndProductsKitchen implements Category, CategoryLevel
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

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			return (new Factory(self::getLocalePrefix()))->make([
				'Cookware' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/dishesandproductskitchen/cookware.xml', [
						'known' => [
							CategoryLevel::DISHER_TYPE,
							CategoryLevel::KITCHENWARE_TYPE,
							CategoryLevel::COOKWARE_TYPE,
							CategoryLevel::SERVING_DISHER_TYPE,
							CategoryLevel::DRINKWARE_TYPE,
							CategoryLevel::PRODUCT_TYPE,
							CategoryLevel::PRODUCT_SUB_TYPE,
							CategoryLevel::PAN_TYPE,
						]
					]),
				],
				'Kitchenware'
			]);
		});
	}
}