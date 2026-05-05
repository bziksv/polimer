<?php

namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing;

/** @noinspection PhpUnused */
class ConditionProperty extends ListingSkeleton
{
	use Concerns\HasLocale;

	protected const USER_TYPE = 'avito_condition';

	protected static function getListing() : Listing\Listing
	{
		return new Listing\Condition();
	}

	protected static function getMessage(string $code) : string
	{
		return self::getLocale($code);
	}
}