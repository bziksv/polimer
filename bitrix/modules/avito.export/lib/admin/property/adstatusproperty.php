<?php

namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing;

/** @noinspection PhpUnused */
class AdStatusProperty extends ListingSkeleton
{
	use Concerns\HasLocale;

	protected const USER_TYPE = 'avito_ad_status';

	protected static function getListing() : Listing\Listing
	{
		return new Listing\AdStatus();
	}

	protected static function getMessage(string $code) : string
	{
		return self::getLocale($code);
	}
}