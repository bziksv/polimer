<?php

namespace Avito\Export\Feed\Tag;

use Bitrix\Main;
use Avito\Export\Feed;
use Avito\Export\Concerns;

class VideoFileUrl extends Tag
{
	use Concerns\HasLocale;

	protected function defaults() : array
	{
		return [
			'name' => 'VideoFileURL',
		];
	}

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (is_array($value)) { $value = reset($value); }
		if (empty($value)) { return; }

		if (!preg_match('#^(https?:)?//#i', $value))
		{
			$domain = $context->variable('DOMAIN');
			$value = rtrim($domain, '/') . '/' . ltrim($value, '/');
		}

		$url = new Feed\Engine\Data\TagCompiled($this->name(), $this->format($value, $context));
		$url->markCData();

		$tag->addChild($url);
	}

	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		if (is_array($value)) { $value = reset($value); }

		$value = (string)$value;
		$regexps = [
			'#^https://disk.yandex.ru/(.*)$#',
			'#^(.*).mp4$#',
			'#^(.*).mov$#',
			'#^(.*).webm$#',
			'#^(.*).ogg$#',
		];
		$found = false;

		foreach ($regexps as $regexp)
		{
			if (!preg_match($regexp, $value)) { continue; }

			$found = true;
			break;
		}

		return $found ? null : new Main\Error(self::getLocale('CHECK_ERROR_PATTERN'));
	}
}
