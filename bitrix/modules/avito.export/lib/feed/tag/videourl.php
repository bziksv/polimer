<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Bitrix\Main;

class VideoURL extends Tag
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'VideoURL',
		];
	}

	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		if (is_array($value)) { $value = reset($value); }

		$value = (string)$value;
		$regexps = [
			'#^https://www\.youtube\.com/watch\?v=(.*)$#',
			'#^https://rutube\.ru/video/(.*)/$#',
		];
		$found = false;

		foreach ($regexps as $regexp)
		{
			if (!preg_match($regexp, $value)) { continue; }

			$found = true;
			break;
		}

		return $found ? null : new Main\Error(self::getLocale('CHECK_ERROR_PATTERN'));
	}
}
