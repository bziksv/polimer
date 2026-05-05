<?php
namespace Avito\Export\Structure\Animals\Listing;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing as DictionaryListing;

class AdType implements DictionaryListing\Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('I_GIVE_IT_AWAY_FOR_FREE'),
			self::getLocale('I_SELL_AS_A_BREEDER'),
			self::getLocale('I_SELL_AS_A_PRIVATE_PERSON'),
		];
	}
}