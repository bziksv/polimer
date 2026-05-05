<?php
namespace Avito\Export\Dictionary\Listing;

use Avito\Export\Concerns;

class InternetCalls implements Listing
{
	use Concerns\HasLocale;

	public function values() : array
	{
		return [
			self::getLocale('YES'),
			self::getLocale('NO'),
		];
	}
}