<?php
namespace Avito\Export\Structure\Electronics\Tablets;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

class ProductsType implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('TABLET'),
			self::getLocale('GRAPHICS_TABLET'),
		];
	}
}