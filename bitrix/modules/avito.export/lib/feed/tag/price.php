<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Bitrix\Main;

class Price extends Tag
{
	use Concerns\HasLocale;

	protected CONST MIN_PRICE = 0;

	protected $supported = ['N', 'S'];

	protected function defaults() : array
	{
		return [
			'name' => 'Price',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::PRICE_FIELD,
				'FIELD' => 'MINIMAL_DISCOUNT',
			],
			[
				'TYPE' => Feed\Source\Registry::PRICE_FIELD,
				'FIELD' => 'MINIMAL_VALUE',
			],
			[
				'TYPE' => Feed\Source\Registry::PRICE_FIELD,
				'FIELD' => 'OPTIMAL_DISCOUNT',
			],
			[
				'TYPE' => Feed\Source\Registry::PRICE_FIELD,
				'FIELD' => 'OPTIMAL_VALUE',
			],
		];
	}

	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		if (is_array($value)) { $value = reset($value); }

		if (!is_numeric($value))
		{
			return new Main\Error(self::getLocale('CHECK_ERROR_NUMERIC'));
		}

		$value = (int)$value;

		if ($value <= static::MIN_PRICE)
		{
			return new Main\Error(self::getLocale('LOWER_THEN', [
				'#MIN#' => static::MIN_PRICE,
			]));
		}

		return null;
	}

	protected function format($value) : string
	{
		return (string)round($value);
	}
}
