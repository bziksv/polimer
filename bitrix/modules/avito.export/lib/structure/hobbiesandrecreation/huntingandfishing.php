<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\CategoryWithTags;
use Avito\Export\Structure\TagFactory;

class HuntingAndFishing implements Category, CategoryLevel, CategoryWithTags
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function name() : string
	{
		return self::getLocale('NAME');
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'PurposeEcho' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	public function categoryLevel() : ?string
	{
		return CategoryLevel::CATEGORY;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('hobbiesandrecreation/huntingandfishing.xml', [
				'known' => [
					CategoryLevel::EQUIPMENT_TYPE,
					CategoryLevel::EQUIPMENT_SUB_TYPE,
					CategoryLevel::EQUIPMENT_GROUP,
					CategoryLevel::OPTICS_TYPE,
					CategoryLevel::SIGHT_TYPE,
					CategoryLevel::KNIFE_TYPE,
					CategoryLevel::PRODUCT_TYPE,
				]
			]),
			new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]),

			new Dictionary\Decorator(
				new Dictionary\XmlTree('hobbiesandrecreation/huntingandfishing/udochki_spinningi_i_katushki.xml'),
				[
					'rename' => [ 'brend' => 'Brand' ],
					'wait' => [ 'EquipmentSubType' => self::getLocale('FISHING_RODS_SPINNING_RODS_AND_REELS') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlTree('hobbiesandrecreation/huntingandfishing/eholoty_i_snaryazhenie.xml'),
				[
					'rename' => [ 'brend' => 'Brand' ],
					'wait' => [ 'EquipmentSubType' => self::getLocale('ECHO_SOUNDERS_AND_EQUIPMENT') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlTree('hobbiesandrecreation/huntingandfishing/knife_catalogue.xml'),
				[
					'rename' => [ 'brend' => 'Brand' ],
					'wait' => [ 'EquipmentType' => self::getLocale('KNIVES_MULTITOOLS_AXES') ],
				]
			)
		]);
	}

	public function children() : array
	{
		return [];
	}
}