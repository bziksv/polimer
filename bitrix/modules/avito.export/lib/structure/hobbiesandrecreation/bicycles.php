<?php
namespace Avito\Export\Structure\HobbiesAndRecreation;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Category;
use Avito\Export\Structure\CategoryLevel;

class Bicycles implements Category, CategoryLevel
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
			new Dictionary\Fixed([ 'Condition' => new Dictionary\Listing\Condition() ]),
			new Dictionary\XmlTree('hobbiesandrecreation/bicycles.xml', [
				'known' => [
					CategoryLevel::VEHICLE_TYPE,
					CategoryLevel::PRODUCT_TYPE,
				]
			]),
		]);
	}

	public function children() : array
	{
		return [];
	}
}