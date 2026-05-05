<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction\Properties\Piles;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class PileType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('BORED_HAMMER'),
			self::getLocale('SPIRAL'),
			self::getLocale('HAMMERED'),
			self::getLocale('SHEET_PILE'),
			self::getLocale('PILE_TIP'),
			self::getLocale('PILE_HEAD'),
			self::getLocale('OTHER'),
		];
	}
}
