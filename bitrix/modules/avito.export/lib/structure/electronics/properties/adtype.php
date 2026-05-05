<?php
namespace Avito\Export\Structure\Electronics\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class AdType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('SELLING_MY_OWN'),
			self::getLocale('GOODS_PURCHASED_FOR_SALE'),
		];
	}
}