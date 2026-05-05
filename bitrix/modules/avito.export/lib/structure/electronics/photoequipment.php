<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class PhotoEquipment implements Category, CategoryLevel
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
			new Dictionary\Fixed([
				'Condition' => new Dictionary\Listing\Condition(),
			]),
			new Dictionary\Decorator(new Dictionary\Compound([
				new Dictionary\XmlTree('electronics/photoequipment/lenses_vendor.xml'),
				new Dictionary\XmlTree('electronics/photoequipment/mount.xml')
			]), [
				'wait' => [ 'GoodsType' => self::getLocale('LENSES') ],
			])
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$customFactory = new Factory(self::getLocalePrefix());

			return $customFactory->make([
				'Compact cameras',
				'SLR Cameras',
				'FILM Cameras',
				'Binoculars and Telescopes',
				'Lenses',
				'Equipment and Accessories',
			]);
		});
	}
}