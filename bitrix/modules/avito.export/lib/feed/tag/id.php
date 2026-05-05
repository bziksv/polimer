<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class Id extends Tag
{
	use Concerns\HasLocale;

	protected $supported = ['N', 'S', 'L'];

	protected function defaults() : array
	{
		return [
			'name' => 'Id',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::OFFER_FIELD,
				'FIELD' => 'ID',
			],
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'ID',
			]
		];
	}

	public function fetcherSupported(Feed\Source\Fetcher $fetcher) : bool
	{
		return $fetcher instanceof Feed\Source\FetcherInvertible;
	}
}
