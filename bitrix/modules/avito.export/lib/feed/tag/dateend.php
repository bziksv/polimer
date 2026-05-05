<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class DateEnd extends DateBegin
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'DateEnd',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'ACTIVE_TO',
			],
		];
	}
}
