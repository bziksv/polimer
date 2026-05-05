<?php

namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing;

/** @noinspection PhpUnused */
class ContactMethodProperty extends ListingSkeleton
{
	use Concerns\HasLocale;

	protected const USER_TYPE = 'avito_contact_method';

	protected static function getListing() : Listing\Listing
	{
		return new Listing\ContactMethod();
	}

	protected static function getMessage(string $code) : string
	{
		return self::getLocale($code);
	}
}