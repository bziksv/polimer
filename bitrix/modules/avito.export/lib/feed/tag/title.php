<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class Title extends Tag
{
	use Concerns\HasLocale;

	protected CONST MAX_LENGTH = 50;

	protected function defaults() : array
	{
		return [
			'name' => 'Title',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'NAME',
			],
			[
				'TYPE' => Feed\Source\Registry::SEO_FIELD,
				'FIELD' => 'ELEMENT_PAGE_TITLE',
			],
			[
				'TYPE' => Feed\Source\Registry::SEO_FIELD,
				'FIELD' => 'ELEMENT_META_TITLE',
			],
			[
				'TYPE' => Feed\Source\Registry::SEO_FIELD,
				'FIELD' => 'ELEMENT_META_DESCRIPTION',
			],
			[
				'TYPE' => Feed\Source\Registry::SEO_FIELD,
				'FIELD' => 'SECTION_META_TITLE',
			],
			[
				'TYPE' => Feed\Source\Registry::SEO_FIELD,
				'FIELD' => 'SECTION_META_DESCRIPTION',
			],
		];
	}

	protected function format($value) : string
	{
		$value = parent::format($value);
		$value = $this->cutValue($value);

		return $value;
	}

	protected function cutValue($value) : string
	{
		if (mb_strlen($value) > static::MAX_LENGTH)
		{
			$cut = self::getLocale('CUT', null, '...');

			$value =
				mb_substr($value, 0, static::MAX_LENGTH - mb_strlen($cut))
				. $cut;
		}

		return (string)$value;
	}
}
