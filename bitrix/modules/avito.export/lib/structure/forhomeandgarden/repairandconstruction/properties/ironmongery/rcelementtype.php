<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties\Ironmongery;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class RCElementType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('BOTTOM'),
			self::getLocale('CABLE_WELL'),
			self::getLocale('RING'),
			self::getLocale('THROAT_RING'),
			self::getLocale('LID'),
			self::getLocale('TELEPHONE_WELL'),
		];
	}
}
