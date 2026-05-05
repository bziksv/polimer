<?php
namespace Avito\Export\Structure\ForBusiness\Readybusiness\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class DealGoal implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('SELL_A_BUSINESS'),
			self::getLocale('RENT_A_BUSINESS'),
			self::getLocale('ATTRACT_INVESTMENT'),
			self::getLocale('FIND_A_PARTNER'),
			self::getLocale('GET_A_LOAN'),
		];
	}
}