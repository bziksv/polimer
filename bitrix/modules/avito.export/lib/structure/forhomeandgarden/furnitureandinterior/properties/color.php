<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class Color implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('BEIGE'),
			self::getLocale('WHITE'),
			self::getLocale('TURQUOISE'),
			self::getLocale('BORDEAUX'),
			self::getLocale('LIGHT_BLUE'),
			self::getLocale('YELLOW'),
			self::getLocale('GREEN'),
			self::getLocale('GOLD'),
			self::getLocale('BROWN'),
			self::getLocale('RED'),
			self::getLocale('ORANGE'),
			self::getLocale('PINK'),
			self::getLocale('SILVER'),
			self::getLocale('GRAY'),
			self::getLocale('BLUE'),
			self::getLocale('VIOLET'),
			self::getLocale('BLACK'),
			self::getLocale('MULTICOLOURED'),
			self::getLocale('OTHER'),
		];
	}
}