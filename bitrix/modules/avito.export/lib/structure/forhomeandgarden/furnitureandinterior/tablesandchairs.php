<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;

class TablesAndChairs implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],
			'Purpose' => [ 'multiple' => true, 'wrapper' => true ],
			'SeatMaterial' => [ 'multiple' => true, 'wrapper' => true ],
			'Construction' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\NoValue();
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$factory = new Factory(self::getLocalePrefix());
			$factory->categoryLevel(CategoryLevel::GOODS_SUB_TYPE);

			return $factory->make([
				'Components',
				'Lunch Group' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/tablesandchairs/lunchgroup.xml'),
					'tags' => [
						'DiningFurnitureSet' => [ 'multiple' => true, 'wrapper' => true ],
					],
				],
				'Benches' => [
					'dictionary' => new Dictionary\Fixed(['Model' => []])
				],
				'Tables' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/tablesandchairs/tables.xml', [
							'known' => [
								CategoryLevel::TABLE_TYPE,
							],
						]),
					])
				],
				'Chairs' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/tablesandchairs/chairs.xml'),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					]),
					'tags' => [
						'BaseMaterial' => [ 'multiple' => true, 'wrapper' => true ],
					],
				],
				'Stools' => [
					'dictionary' => new Dictionary\Fixed(['Model' => []])
				],
				'Other',
			]);
		});
	}
}
