<?php
namespace Avito\Export\Structure\Electronics\Phone\Mobile;

use Avito\Export\Concerns;
use Avito\Export\Dictionary\Listing\Listing;

/** @deprecated */
class Condition implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			$this->value('NEW'),
			$this->value('EXCELLENT'),
			$this->value('GOOD'),
			$this->value('SATISFACTORY'),
			$this->value('NEEDS_REPAIR'),
		];
	}

	public function value(string $key) : string
	{
		return self::getLocale($key);
	}
}