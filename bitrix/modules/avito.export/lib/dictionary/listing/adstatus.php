<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;
use Avito\Export\Utils\Name;

class AdStatus implements Listing, ListingWithDisplay
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			'Free',
			'Highlight',
			'XL',
			'x2_1',
			'x2_7',
			'x5_1',
			'x5_7',
			'x10_1',
			'x10_7',
			'x15_1',
			'x15_7',
			'x20_1',
			'x20_7',
		];
	}

	public function display(string $value) : string
	{
		$code = Name::screamingSnakeCase($value);

		return self::getLocale($code, null, $value);
	}
}