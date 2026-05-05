<?php

namespace Avito\Export\Feed\Tag;

use Avito\Export\Dictionary\Listing\ListingWithDisplay;
use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Utils;
use Avito\Export\Dictionary\Listing\Listing;

class Tag
{
	use Concerns\HasLocale;

	/** @var array */
	protected $parameters;
	/** @var array|null */
	protected $supported;

	public function __construct(array $parameters = [])
	{
		$this->parameters = $parameters + $this->defaults();

		Assert::notNull($this->parameters['name'], '$parameters[name]');
	}

	protected function defaults() : array
	{
		return [];
	}

	public function name() : string
	{
		return $this->parameters['name'];
	}

	public function required()
	{
		return ($this->parameters['required'] ?? false);
	}

	public function multiple() : bool
	{
		return (bool)($this->parameters['multiple'] ?? false);
	}

	public function defined() : bool
	{
		return (bool)($this->parameters['defined'] ?? false);
	}

	public function deprecated() : bool
	{
		return (bool)($this->parameters['deprecated'] ?? false);
	}

	public function preselect() : bool
	{
		return ($this->parameters['preselect'] ?? false);
	}

	public function recommendation(Feed\Source\Context $context, Feed\Source\FetcherPool $fetcherPool) : array
	{
		return $this->recommendationListing();
	}

	protected function recommendationListing() : array
	{
		$listing = $this->parameters['listing'] ?? null;

		if ($listing === null) { return []; }

		/** @var Listing $listing */
		Assert::typeOf($listing, Listing::class, '$parameters[listing]');

		$result = [];

		foreach ($listing->values() as $value)
		{
			if ($listing instanceof ListingWithDisplay)
			{
				$result[] = [
					'VALUE' => $value,
					'DISPLAY' => $listing->display($value),
				];
			}
			else
			{
				$result[] = [
					'VALUE' => $value,
				];
			}
		}

		return $result;
	}

	public function fetcherSupported(Feed\Source\Fetcher $fetcher) : bool
	{
		return true;
	}

	public function supported() : ?array
	{
		return $this->supported;
	}

	public function exportMultiple(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (empty($value)) { return; }
		if (!is_array($value)) { $value = [$value]; }

		$parent = $tag;

		if (isset($this->parameters['wrapper']))
		{
			$wrapperName = $this->parameters['wrapper'] === true
				? $this->name()
				: $this->parameters['wrapper'];

			$parent = new Feed\Engine\Data\TagCompiled($wrapperName);
		}

		if (isset($this->parameters['max_count']))
		{
			$initialCount = $parent->childrenCount();
			$maxCount = $this->parameters['max_count'];

			foreach ($value as $one)
			{
				$this->exportSingle($parent, $one, $siblings, $context);

				if ($parent->childrenCount() - $initialCount >= $maxCount) { break; }
			}
		}
		else
		{
			foreach ($value as $one)
			{
				$this->exportSingle($parent, $one, $siblings, $context);
			}
		}

		if ($parent !== $tag && $parent->childrenCount() > 0)
		{
			$tag->addChild($parent);
		}
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function exportSingle(Feed\Engine\Data\TagCompiled $tag, $value, array $siblings, Feed\Source\Context $context) : void
	{
		if (is_array($value)) { $value = reset($value); }
		if (empty($value)) { return; }

		$value = $this->format($value);

		if ($value === '') { return; }

		$name = $this->name();

		if ($this->multiple())
		{
			if (isset($this->parameters['item']))
			{
				$name = $this->parameters['item'] === true ? 'Option' : $this->parameters['item'];
			}
			else if (isset($this->parameters['wrapper']) && $this->parameters['wrapper'] === true)
			{
				$name = 'Option';
			}
		}

		$tag->addChild(new Feed\Engine\Data\TagCompiled($name, $value));
	}

	protected function format($value) : string
	{
		return strip_tags($value);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function checkRequired($value, array $siblings, Format $format) : ?Main\Error
	{
		$required = $this->parameters['required'] ?? false;

		if ($required === false || !empty($value))
		{
			return null;
		}

		if ($required === true)
		{
			return new Main\Error(self::getLocale('CHECK_ERROR_REQUIRED'));
		}

		foreach ($required as $name)
		{
			if (empty($siblings[$name]))
			{
				return new Main\Error(self::getLocale('CHECK_ERROR_REQUIRED_SIBLING', [
					'#SELF#' => $this->name(),
					'#SIBLING#' => implode(', ', $required),
				]));
			}
		}

		return null;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function checkValue($value, array $siblings, Format $format) : ?Main\Error
	{
		return null;
	}

	public function title() : string
	{
		return $this->localeMessage('title');
	}

	public function hint() : string
	{
		return $this->localeMessage('hint');
	}

	protected function localeMessage(string $type) : string
	{
		if (isset($this->parameters[$type])) { return $this->parameters[$type]; }

		$typeUpper = mb_strtoupper($type);
		$classHint = static::getLocale($typeUpper, null, '');

		if ($classHint !== '') { return $classHint; }

		$tagHint = self::getLocale(Utils\Name::screamingSnakeCase($this->name()) . '_' . $typeUpper, null, '');

		return $tagHint;
	}
}
