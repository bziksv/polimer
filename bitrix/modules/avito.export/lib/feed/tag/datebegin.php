<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Bitrix\Main;

class DateBegin extends Tag
{
	use Concerns\HasLocale;

	public const DATE_FORMAT = 'Y-m-d';
	public const DATE_TIME_FORMAT = \DateTimeInterface::ATOM;

	protected function defaults() : array
	{
		return [
			'name' => 'DateBegin',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'ACTIVE_FROM',
			],
		];
	}

	protected function format($value) : string
	{
		if (is_string($value))
		{
			$result = $this->formatString($value);
		}
		else if ($value instanceof Main\Type\DateTime || $value instanceof \DateTime)
		{
			$result = $value->format(static::DATE_TIME_FORMAT);
		}
		else if ($value instanceof Main\Type\Date)
		{
			$result = $value->format(static::DATE_FORMAT);
		}
		else
		{
			$result = (string)$value;
		}

		return $result;
	}

	protected function formatString(string $value) : string
	{
		$value = trim($value);

		if ($value === '') { return ''; }

		$parts = ParseDateTime($value);

		if (!isset($parts['YYYY'], $parts['MM'], $parts['DD'])) { return ''; }

		$date = new Main\Type\DateTime();
		$date->setDate((int)$parts['YYYY'], (int)$parts['MM'], (int)$parts['DD']);

		if (isset($parts['HH'])) // has time
		{
			$date->setTime((int)$parts['HH'], (int)($parts['MI'] ?? 0), (int)($parts['SS'] ?? 0));

			return $date->format(static::DATE_TIME_FORMAT);
		}

		return $date->format(static::DATE_FORMAT);
	}
}
