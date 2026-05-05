<?php

namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing;

/** @noinspection PhpUnused */
class ListingFeeProperty extends ListingSkeleton
{
	use Concerns\HasLocale;

	protected const USER_TYPE = 'avito_listing_fee';

	protected static function getListing() : Listing\Listing
	{
		return new Listing\ListingFee();
	}

	protected static function getMessage(string $code) : string
	{
		return self::getLocale($code);
	}
}