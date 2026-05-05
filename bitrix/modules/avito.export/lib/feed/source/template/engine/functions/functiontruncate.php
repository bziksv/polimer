<?php

namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Avito\Export\Concerns;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionTruncate extends Iblock\Template\Functions\FunctionBase
{
	use Concerns\HasLocale;
	use Concerns\HasOnceStatic;

	public function calculate(array $parameters)
	{
		if (count($parameters) < 2) { return $parameters[0] ?? null; }

		$value = array_shift($parameters);
		$maxLength = max(0, (int)(array_shift($parameters)));
		$reservedSuffix = count($parameters) > 0 ? implode('', $parameters) : '';

		$overflowSuffix = static::overflowSuffix();
		$overflowSuffixLength = mb_strlen($overflowSuffix);

		$valueMaxLength = max($overflowSuffixLength, $maxLength - mb_strlen($reservedSuffix));

		if (mb_strlen($value) <= $valueMaxLength)
		{
			return $value . $reservedSuffix;
		}

		$value = mb_substr($value, 0, $valueMaxLength - $overflowSuffixLength);

		return $value . $overflowSuffix . $reservedSuffix;
	}

	protected static function overflowSuffix() : string
	{
		return self::onceStatic('overflowSuffix', static function() {
			return self::getLocale('OVERFLOW_SUFFIX', null, '...');
		});
	}
}