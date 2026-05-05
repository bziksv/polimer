<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class ContactMethod implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('BY_PHONE_AND_MESSAGE'),
			self::getLocale('BY_PHONE'),
			self::getLocale('BY_MESSAGE'),
		];
	}
}