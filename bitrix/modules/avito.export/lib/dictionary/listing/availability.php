<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class Availability implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('IN_STOCK'),
			self::getLocale('ON_ORDER'),
		];
	}
}