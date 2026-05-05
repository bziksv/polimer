<?php
namespace Avito\Export\Feed\Source\Field;

use Avito\Export\Concerns;

class BooleanField extends EnumField
{
	use Concerns\HasLocale;

	public function variants() : array
	{
		return [
			[
				'ID' => 'Y',
				'VALUE' => self::getLocale('Y'),
			],
			[
				'ID' => 'N',
				'VALUE' => self::getLocale('N'),
			],
		];
	}

	public function conditions() : array
	{
		return [
			Condition::EQUAL,
			Condition::NOT_EQUAL,
		];
	}
}