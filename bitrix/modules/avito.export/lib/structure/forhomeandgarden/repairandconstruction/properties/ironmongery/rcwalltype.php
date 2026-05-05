<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties\Ironmongery;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class RCWallType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('SLOPE'),
			self::getLocale('PORTAL'),
		];
	}
}
