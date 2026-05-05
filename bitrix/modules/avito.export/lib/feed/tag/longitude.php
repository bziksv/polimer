<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class Longitude extends Tag
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'Longitude',
		];
	}

	protected function format($value) : string
	{
		if (is_string($value) && preg_match('/^(\d+\.\d+),\s?(\d+\.\d+)$/', $value, $matches))
		{
			$value = $matches[2];
		}

		return (string)$value;
	}
}
