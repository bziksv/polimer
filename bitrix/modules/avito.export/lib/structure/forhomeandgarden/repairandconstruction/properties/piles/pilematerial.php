<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties\Piles;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class PileMaterial implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('WOOD'),
			self::getLocale('REINFORCED_CONCRETE'),
			self::getLocale('METAL'),
		];
	}
}
