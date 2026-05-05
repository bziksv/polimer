<?php
namespace Avito\Export\Structure\ForHomeAndGarden\FurnitureAndInterior\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class ComponentsType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('DOORS_FOR_FURNITURE'),
			self::getLocale('COMPARTMENT_DOORS'),
			self::getLocale('WIREFRAMES'),
			self::getLocale('SHELVES'),
			self::getLocale('FURNITURE'),
			self::getLocale('BOXES'),
			self::getLocale('OTHER'),
		];
	}
}