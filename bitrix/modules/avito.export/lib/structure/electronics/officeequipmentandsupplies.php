<?php
namespace Avito\Export\Structure\Electronics;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Factory;
use Avito\Export\Structure\Category;

class OfficeEquipmentAndSupplies implements Category, CategoryLevel
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
		return new Dictionary\Fixed([
			'Condition' => new Dictionary\Listing\Condition(),
		]);
	}

	public function children() : array
	{
		return $this->once('children', static function() {
			self::includeLocale();

			$customFactory = new Factory(self::getLocalePrefix());

			return $customFactory->make([
				'MFPs, copiers, and scanners',
				'Printers',
				'Telephony',
				'UPS, surge protectors',
				'Paper shredders',
				'Power supplies and batteries',
				'Memory Banks',
				'Paper',
				'Cables and Adapters',
				'Cartridges',
				'Stationery',
			]);
		});
	}
}