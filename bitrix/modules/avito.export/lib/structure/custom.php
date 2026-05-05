<?php
namespace Avito\Export\Structure;

use Avito\Export\Assert;
use Avito\Export\Dictionary;

class Custom implements Category, CategoryCompatible, CategoryLevel, CategoryWithTags
{
	protected $name;
	protected $oldNames;
	protected $dictionary;
	protected $categoryLevel;
	protected $children;
	protected $tags;

	public function __construct(array $parameters)
	{
		Assert::notNull($parameters['name'], '$parameters[name]');

		$this->name = $parameters['name'];
		$this->oldNames = $this->parseOldNames($parameters['oldNames'] ?? '');
		$this->categoryLevel = $parameters['categoryLevel'] ?? null;
		$this->dictionary = $parameters['dictionary'] ?? null;
		$this->tags = $parameters['tags'] ?? null;
		$this->children = $parameters['children'] ?? [];
	}

	public function name() : string
	{
		return $this->name;
	}

	public function tags() : array
	{
		return $this->tags ?? [];
	}

	public function categoryLevel() : ?string
	{
		return $this->categoryLevel;
	}

	public function oldNames() : array
	{
		return $this->oldNames;
	}

	protected function parseOldNames($parameter) : array
	{
		if (is_array($parameter))
		{
			$result = $parameter;
		}
		else if (is_string($parameter) && $parameter !== '')
		{
			$result = explode('||', $parameter);
		}
		else
		{
			$result = [];
		}

		return $result;
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return $this->dictionary ?? new Dictionary\NoValue();
	}

	public function children() : array
	{
		return $this->children;
	}

	protected function constructDictionaries(array $map, $productType = null) : array
	{
		return $this->dictionaryFactory()->make($map, $productType);
	}

	protected function makeWait(string $type) : array
	{
		return $this->dictionaryFactory()->makeWait($type);
	}

	protected function dictionaryFactory() : DictionaryFactory
	{
		if (method_exists(static::class, 'getLocalePrefix'))
		{
			$reflection = new \ReflectionClass(static::class);
			$reflectionIncludeLocale = $reflection->getMethod('includeLocale');
			$reflectionGetLocalePrefix = $reflection->getMethod('getLocalePrefix');

			$reflectionIncludeLocale->setAccessible(true);
			$reflectionIncludeLocale->invoke(null);

			$reflectionGetLocalePrefix->setAccessible(true);
			$factory = new DictionaryFactory($reflectionGetLocalePrefix->invoke(null));
		}
		else
		{
			$factory = new DictionaryFactory();
		}

		return $factory;
	}
}