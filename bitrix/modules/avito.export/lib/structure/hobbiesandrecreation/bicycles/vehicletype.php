<?php
namespace Avito\Export\Structure\HobbiesAndRecreation\Bicycles;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;


class VehicleType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('MOUNTAIN'),
			self::getLocale('ROAD'),
			self::getLocale('BMX'),
			self::getLocale('KIDS'),
			self::getLocale('SPARE_PARTS_ACCESSORIES'),
		];
	}
}
