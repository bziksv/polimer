<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\TagFactory;

class KitchenSets implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'DoorsMaterial' => [ 'multiple' => true, 'wrapper' => true ],
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],

		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/kitchensets.xml', [
			'known' => [
				CategoryLevel::GOODS_SUB_TYPE,
				CategoryLevel::COMPONENTS_TYPE,
			],
		]);
	}

	public function children() : array
	{
		return [];
	}
}