<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;
use Avito\Export\Utils\Name;

class ListingFee implements Listing, ListingWithDisplay
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			'Package',
			'PackageSingle',
			'Single',
		];
	}

	public function display(string $value) : string
	{
		$code = Name::screamingSnakeCase($value);

		return self::getLocale($code, null, $value);
	}
}