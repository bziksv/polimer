<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Bitrix\Main;

class ManagerName extends Tag
{
	use Concerns\HasLocale;

	protected CONST MAX_LENGTH = 40;

	protected function defaults() : array
	{
		return [
			'name' => 'ManagerName',
		];
	}

	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		if (is_array($value)) { $value = reset($value); }

		if (!is_string($value)) { return new Main\Error(self::getLocale('CHECK_ERROR_STRING')); }

		if (mb_strlen($value) <= static::MAX_LENGTH) { return null; }

		return new Main\Error(self::getLocale('CHECK_ERROR_MAX_LENGTH', [
			'#LENGTH#' => static::MAX_LENGTH,
		]));
	}
}
