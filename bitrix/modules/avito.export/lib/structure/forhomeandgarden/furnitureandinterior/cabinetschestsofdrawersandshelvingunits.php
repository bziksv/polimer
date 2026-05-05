<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;

class CabinetsChestsOfDrawersAndShelvingUnits implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

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
			'Material' => [ 'multiple' => true, 'wrapper' => true ],
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],
			'Purpose' => [ 'multiple' => true, 'wrapper' => true ],
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
				'Cabinets And Buffets' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/cabinets.xml', [
							'known' => [
								CategoryLevel::CABINET_TYPE,
							],
						]),
					]),
					'oldNames' => self::getLocale('CABINETS'),
				],
				'Chests And Nightstands' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/chests.xml', [
							'known' => [
								CategoryLevel::DRESSER_TYPE,
							],
						]),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					]),
					'oldNames' => self::getLocale('CHESTS'),
				],
				'Shelving And Bookcase' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/shelving_and_bookcase.xml', [
							'known' => [
								CategoryLevel::SHELVING_TYPE,
							],
						]),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					])
				],
				'Shelves' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/shelves.xml', [
							'known' => [
								CategoryLevel::SHELF_TYPE,
							],
						]),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					])
				],
				'Hallways And Shoe Racks' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/hallways_and_shoe_racks.xml', [
							'known' => [
								CategoryLevel::OUTWEAR_DRESSER_TYPE,
							],
						]),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					])
				],
				'Wardrobe Systems And Hangers' => [
					'dictionary' => new Dictionary\Compound([
						new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/cabinetschestsofdrawersandshelvingunits/wardrobe_systems_and_hangers.xml', [
							'known' => [
								CategoryLevel::CLOAKROOM_TYPE,
							],
						]),
						new Dictionary\Fixed(['Color' => new Properties\Color()]),
					])
				],
				'Sets And Kits',
				'Components' => [
					'dictionary' => new Dictionary\Fixed([
						'ComponentsType' => new Properties\ComponentsType(),
					]),
				],
				'Other',
			]);
		});
	}
}