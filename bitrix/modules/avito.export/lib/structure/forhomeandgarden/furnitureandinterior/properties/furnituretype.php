<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class FurnitureType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('HEADBOARD'),
			self::getLocale('LAMEL'),
			self::getLocale('MECHANISM'),
			self::getLocale('SEAT_FILLER'),
			self::getLocale('BED_BASE'),
			self::getLocale('SPRING_BLOCK'),
			self::getLocale('RACK_BOTTOM'),
			self::getLocale('BOX'),
			self::getLocale('OTHER'),
		];
	}
}