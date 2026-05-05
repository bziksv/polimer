<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class Delivery implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('OFF'),
			self::getLocale('STORE'),
			self::getLocale('COURIER'),
			self::getLocale('POSTAMAT'),
			self::getLocale('OWN_COURIER'),
			self::getLocale('OWN_PARTNER_SDEK'),
			self::getLocale('OWN_PARTNER_DELLIN'),
			self::getLocale('OWN_PARTNER_DPD'),
			self::getLocale('OWN_PARTNER_PEC'),
			self::getLocale('OWN_PARTNER_RUSSIAN_POST'),
			self::getLocale('OWN_PARTNER_BOXBERRY'),
		];
	}
}