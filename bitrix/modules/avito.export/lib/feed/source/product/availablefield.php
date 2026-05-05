<?php
namespace Avito\Export\Feed\Source\Product;

use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;

class AvailableField extends Field\BooleanField
{
	use Concerns\HasLocale;

	public function variants() : array
	{
		return array_merge(parent::variants(), [
			[
				'ID' => 'ANY',
				'VALUE' => self::getLocale('ANY'),
			],
		]);
	}

	public function filter(string $compare, $value) : array
	{
		if ($value === 'ANY') { return []; }

		return parent::filter($compare, $value);
	}
}