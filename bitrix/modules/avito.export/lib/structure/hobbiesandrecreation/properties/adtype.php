<?php
namespace Avito\Export\Structure\HobbiesAndRecreation\Properties;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class AdType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('SELLING_MY'),
			self::getLocale('GOODS_PURCHASED_FOR_SALE'),
			self::getLocale('GOODS_FROM_THE_MANUFACTURER'),
		];
	}
}