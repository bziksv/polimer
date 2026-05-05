<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Feed;
use Avito\Export\Concerns;

class Description extends Tag
{
	use Concerns\HasLocale;

	protected CONST MAX_LENGTH = 7500;

	protected function defaults() : array
	{
		return [
			'name' => 'Description',
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'DETAIL_TEXT',
			],
			[
				'TYPE' => Feed\Source\Registry::IBLOCK_FIELD,
				'FIELD' => 'PREVIEW_TEXT',
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
				'FIELD' => 'SECTION_PAGE_TITLE',
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

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (is_array($value)) { $value = reset($value); }
		if (empty($value)) { return; }

		$description = new Feed\Engine\Data\TagCompiled($this->name(), $this->format($value));
		$description->markCData();

		$tag->addChild($description);
	}

	protected function format($value) : string
	{
		$value = $this->formatReplaceTags($value);
		$value = strip_tags($value, '<p><br><strong><em><ul><ol><li>');

		return $this->cutValue($value);
	}

	protected function formatReplaceTags($value)
	{
		if (!is_string($value)) { return $value; }

		static $replaces = [
			'h1' => [ 'p', 'strong' ],
			'h2' => [ 'p', 'strong' ],
			'h3' => [ 'p', 'strong' ],
			'h4' => [ 'p', 'strong' ],
			'h5' => [ 'p', 'strong' ],
			'h6' => [ 'p', 'strong' ],
			'b' => 'strong',
			'i' => 'em',
		];

		/* repeat for included tags */
		foreach (range(1, 2) as $ignored)
		{
			$formatted = preg_replace_callback('#<(?<tag>' . implode('|', array_keys($replaces)) . ')(?<attributes>\s.*?)?>(?<content>.*?)</\1>#i', static function(array $matches) use ($replaces) {
				$tag = mb_strtolower($matches['tag']);

				if (!isset($replaces[$tag])) { return $matches[0]; }

				$replace = $replaces[$tag];

				if (is_array($replace))
				{
					$result = sprintf(
						'<%1$s%3$s><%2$s>%4$s</%2$s></%1$s>',
						$replace[0],
						$replace[1],
						$matches['attributes'] ?? '',
						$matches['content'] ?? ''
					);
				}
				else
				{
					$result = sprintf(
						'<%1$s%2$s>%3$s</%1$s>',
						$replace,
						$matches['attributes'] ?? '',
						$matches['content'] ?? ''
					);
				}

				return $result;
			}, $value);

			if ($value === $formatted) { break; }

			$value = $formatted;
		}

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
