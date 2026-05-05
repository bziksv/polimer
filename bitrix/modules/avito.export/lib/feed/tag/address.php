<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Bitrix\Catalog;
use Bitrix\Main;

class Address extends Tag
{
	use Concerns\HasLocale;

	protected CONST MAX_LENGTH = 256;

	protected function defaults() : array
	{
		return [
			'name' => 'Address',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return array_merge(
			$this->regionRecommendation($context, $fetcherPool),
			$this->storesRecommendation($context)
		);
	}

	public function regionRecommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		$region = $fetcherPool->some(Feed\Source\Registry::REGION);
		$needle = explode(',', self::getLocale('RECOMMENDATION_NEEDLE'));
		$result = [];

		foreach ($region->fields($context) as $field)
		{
			$matched = false;
			$haystack = array_filter([
				$field->name(),
				$field->parameter('CODE'),
			]);

			foreach ($haystack as $haystackWord)
			{
				foreach ($needle as $needleWord)
				{
					if (mb_stripos($haystackWord, $needleWord) !== false)
					{
						$matched = true;
						break;
					}
				}

				if ($matched) { break; }
			}

			if ($matched)
			{
				$result[] = [
					'TYPE' => Feed\Source\Registry::REGION,
					'FIELD' => $field->id(),
				];
			}
		}

		return $result;
	}

	public function storesRecommendation(Feed\Source\Context $context) : array
	{
		if (!$context->hasCatalog() || !Main\Loader::includeModule('catalog')) { return []; }

		$result = [];

		$query = Catalog\StoreTable::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
				'!ADDRESS' => false,
			],
			'select' => [ 'ADDRESS' ],
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'VALUE' => $row['ADDRESS'],
			];
		}

		return $result;
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
