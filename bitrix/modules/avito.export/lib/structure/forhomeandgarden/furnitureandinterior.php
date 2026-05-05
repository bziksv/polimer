<?php
namespace Avito\Export\Structure\ForHomeAndGarden;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\TagFactory;

class FurnitureAndInterior implements Category, CategoryLevel, CategoryWithTags
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

	public function tags() : array
	{
		return (new TagFactory())->make([
			'FurnitureAdditions' => [ 'multiple' => true, 'wrapper' => true ],
			'Material' => [ 'multiple' => true, 'wrapper' => true ],
			'SeatMaterial' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Fixed([
			'Condition' => new Dictionary\Listing\Condition(),
			'Availability' => new Dictionary\Listing\Availability(),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			return (new Factory(self::getLocalePrefix()))->make([
				new FurnitureAndInterior\BedsSofasAndChairs(),
				new FurnitureAndInterior\CabinetsChestsOfDrawersAndShelvingUnits(),
				new FurnitureAndInterior\TablesAndChairs(),
				'Textiles And Carpets' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/textiles_and_carpets.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::CURTAINS_TYPE,
							CategoryLevel::BEDDING_TYPE,
							CategoryLevel::SUB_TYPE,
						],
					]),
					'tags' => [
						'FastingType' => [ 'multiple' => true, 'wrapper' => true ],
					],
				],
				new FurnitureAndInterior\KitchenSets(),
				'Interior Decorations Art' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/interior.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::INTERIOR_SUB_TYPE,
						],
					]),
					'tags' => [
						'Material' => [ 'multiple' => true, 'wrapper' => true ],
					],
				],
				'Lighting' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/lighting.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::LIGITING_TYPE,
						],
					]),
				],
				'Computer Desks And Chairs' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/computer_desks_and_chairs.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::TABLE_TYPE,
							CategoryLevel::DESK_CHAIR_TYPE,
						],
					]),
				],
				'Stands And Tables' => [
					'dictionary' => new Dictionary\XmlTree('forhomeandgarden/furnitureandinterior/stands_and_tables.xml', [
						'known' => [
							CategoryLevel::GOODS_SUB_TYPE,
							CategoryLevel::CABINET_TYPE,
							CategoryLevel::RACK_TYPE,
						],
					]),
				],
				'Other',
			]);
		});
	}
}