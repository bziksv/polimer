<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Utils;

class Param extends Tag implements TagExtractable
{
	use Concerns\HasLocale;

	public const NAME = 'param';

	protected function defaults() : array
	{
		return [
			'name' => static::NAME,
		];
	}

	public function extract($value, array $tagLink, Format $format) : array
	{
		$name = trim($tagLink['TAG'] ?? '');

		if ($name === '') { return [ null, null ]; }

		$tag = $format->tag($name);
		$tagValues = [
			$name => $value,
		];

		return $tag !== null ?  [ null, $tagValues ] : [ $tagValues, null ];
	}

	public function exportMultiple(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		$this->exportValue($tag, $value, $siblings);
	}

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		$this->exportValue($tag, $value, $siblings);
	}

	protected function exportValue(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings) : void
	{
		if (!is_array($value)) { return; }

		foreach ($value as $name => $one)
		{
			if (is_array($one)) { $one = reset($one); }
			if (Utils\Value::isEmpty($one) || !empty($siblings[$name]) || !empty($siblings[Characteristic::NAME][$name])) { continue; }

			$one = trim($this->format($one));

			if ($one === '') { continue; }

			$tag->addChild(new Feed\Engine\Data\TagCompiled($name, $one));
		}
	}
}
