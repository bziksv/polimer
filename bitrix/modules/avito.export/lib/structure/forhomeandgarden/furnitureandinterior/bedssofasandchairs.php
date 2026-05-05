<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\TagFactory;

class BedsSofasAndChairs implements Category, CategoryLevel, CategoryWithTags
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
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],
			'Purpose' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/bedssofasandchairs.xml', [
				'known' => [
					CategoryLevel::GOODS_SUB_TYPE,
					CategoryLevel::FURNITURE_TYPE,
				],
			]),
			new Dictionary\Decorator(
				new Dictionary\Fixed(['Color' => new Properties\Color()]),
				[
					'wait' => [
						'GoodsSubType' => [
							self::getLocale('SOFAS'),
							self::getLocale('ARMCHAIRS'),
							self::getLocale('BEDS'),
							self::getLocale('POUFFES_AND_BENCHES'),
						],
					],
				]
			),
		]);
	}

	public function children() : array
	{
		return [];
	}
}