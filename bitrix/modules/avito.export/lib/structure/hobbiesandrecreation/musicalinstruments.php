<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;

class MusicalInstruments implements Category, CategoryLevel
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
		return new Dictionary\Compound([
			new Dictionary\XmlTree('hobbiesandrecreation/musicalinstruments.xml', [
				'known' => [
					CategoryLevel::GOODS_TYPE,
					CategoryLevel::MUSIC_GUITARAS,
					CategoryLevel::GUITAR_TYPE,
					CategoryLevel::WINTER_SPORT_TYPE,
					CategoryLevel::MUSIC_FOR_STUDIOS,
					CategoryLevel::MUSIC_KEYBOARDS,
					CategoryLevel::INSTRUMENT_TYPE,
					CategoryLevel::EQUIPMENT_TYPE,
				]
			]),
			new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]),
		]);
	}

	public function children() : array
	{
		return [];
	}
}