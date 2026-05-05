<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class DeliverySubsidy implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('FREE'),
			self::getLocale('SALE'),
			self::getLocale('NO'),
		];
	}
}