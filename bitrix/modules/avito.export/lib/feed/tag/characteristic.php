<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Admin\Property\CharacteristicProperty;
use Avito\Export\Concerns;
use Avito\Export\Feed;
use Bitrix\Main;
use Avito\Export\Utils;

class Characteristic extends Tag implements TagExtractable
{
	use Concerns\HasLocale;

	public const NAME = 'Characteristic';

	protected $supported = [
		CharacteristicProperty::USER_TYPE,
	];

	protected function defaults() : array
	{
		$defaultRestriction = [
			[
				'type' => 'regex',
				'pattern' => '/^([\w\d|-]+)$/',
			],
			[
				'type' => 'length',
				'limit' => 50,
			],
		];

		return [
			'name' => static::NAME,
			'restrictions' => [
				'OEM' => $defaultRestriction,
				'OriginalOEM' => $defaultRestriction,
			]
		];
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return [
			[
				'TYPE' => Feed\Source\Registry::AVITO_PROPERTY,
				'FIELD' => Feed\Source\AvitoProperty\Fetcher::FIELD_CHARACTERISTIC,
			],
		];
	}

	public function extract($value, array $tagLink, Format $format) : array
	{
		if (!is_array($value)) { return [ null, null ]; }

		$tags = $format->tags();
		$selfValue = $value;
		$siblingsValues = [];

		foreach ($value as $name => $one)
		{
			if (isset($tags[$name]))
			{
				$siblingsValues[$name] = $one;
				unset($selfValue[$name]);
			}
		}

		return [$selfValue, $siblingsValues];
	}

	public function exportMultiple(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		$this->exportSingle($tag, $value, $siblings, $context);
	}

	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (!is_array($value)) { return; }

		foreach ($value as $name => $one)
		{
			if (is_array($one)) { $one = reset($one); }
			if (Utils\Value::isEmpty($one) || !empty($siblings[$name])) { continue; }

			$tag->addChild(new Feed\Engine\Data\TagCompiled($name, (string)$one));
		}
	}

	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		if (!is_array($value)) { return null; }

		$messages = [];

		foreach ($value as $name => $one)
		{
			if (!isset($this->parameters['restrictions'][$name])) { continue; }

			foreach ($this->parameters['restrictions'][$name] as $restriction)
			{
				$message = $this->checkValueCharacteristic($one, $restriction);

				if ($message === null) { continue; }

				$messages[] = self::getLocale('CHECK_ERROR_ATTRIBUTE', [
					'#NAME#' => $name,
					'#VALUE#' => $message,
				]);
			}
		}

		return !empty($messages) ? new Main\Error(implode(PHP_EOL, $messages)) : null;
	}

	private function checkValueCharacteristic(string $value, array $restriction) : ?string
	{
		$result = null;

		if ($restriction['type'] === 'regex')
		{
			$matched = preg_match($restriction['pattern'], $value);

			if (!$matched)
			{
				$result = $restriction['message'] ?? self::getLocale('PATTERN_ERROR');
			}
		}
		else if ($restriction['type'] === 'length')
		{
			$matched = mb_strlen($value) <= $restriction['limit'];

			if (!$matched)
			{
				$result = $restriction['message'] ?? self::getLocale('LENGTH_ERROR', [
					'#VALUE#' => $restriction['limit']
				]);
			}
		}

		return $result;
	}
}
