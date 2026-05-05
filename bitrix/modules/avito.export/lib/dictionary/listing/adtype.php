<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class AdType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('GOODS_PURCHASED_FOR_SALE'),
			self::getLocale('GOODS_FROM_THE_MANUFACTURER'),
		];
	}
}