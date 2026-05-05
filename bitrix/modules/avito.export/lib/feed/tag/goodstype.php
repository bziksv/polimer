<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class GoodsType extends Tag
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'GoodsType'
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::AVITO_PROPERTY,
				'FIELD' => Feed\Source\AvitoProperty\Fetcher::FIELD_GOODS_TYPE,
			],
		];
	}
}
