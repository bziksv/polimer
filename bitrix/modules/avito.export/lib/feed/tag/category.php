<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;

class Category extends Tag
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'Category',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::AVITO_PROPERTY,
				'FIELD' => Feed\Source\AvitoProperty\Fetcher::FIELD_CATEGORY,
			],
		];
	}

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (!is_array($value)) { $value = [ $this->name() => $value ]; }

		foreach ($value as $name => $one)
		{
			if (!is_scalar($one) || (string)$one === '') { continue; }
			if (!empty($siblings[$name]) && $name !== $this->name()) { continue; }

			$one = trim($this->format($one));

			if ($one === '') { continue; }

			$tag->addChild(new Feed\Engine\Data\TagCompiled($name, $one));
		}
	}
}
