<?php
namespace Avito\Export\Structure\Transportation\PartsAndAccessories\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class DeviceType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('CAR_ALARMS'),
			self::getLocale('IMMOBILIZERS'),
			self::getLocale('MECHANICAL_BLOCKERS'),
			self::getLocale('SATELLITE_SYSTEMS'),
		];
	}
}