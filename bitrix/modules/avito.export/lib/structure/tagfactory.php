<?php
namespace Avito\Export\Structure;

use Avito\Export\Feed\Tag\Tag;

class TagFactory
{
	protected $defaultParameters;
	protected $tagClass;

	public function __construct(array $defaultParameters = [], string $tagClass = Tag::class)
	{
		$this->defaultParameters = $defaultParameters;
		$this->tagClass = $tagClass;
	}

	public function make(array $tags) : array
	{
		$tagClass = $this->tagClass;
		$result = [];

		foreach ($tags as $key => $value)
		{
			if ($value instanceof Tag)
			{
				$result[] = $value;
				continue;
			}

			[$name, $parameters] = $this->sanitizeItem($key, $value);

			$result[$name] = new $tagClass($parameters);
		}

		return $result;
	}

	protected function sanitizeItem($key, $value) : array
	{
		if (is_numeric($key))
		{
			$name = $value;
			$parameters = $this->defaultParameters;
			$parameters['name'] = $name;
		}
		else
		{
			$name = $key;
			$parameters = is_array($value) ? $value : [];
			$parameters += $this->defaultParameters;
			$parameters['name'] = $name;
		}

		return [$name, $parameters];
	}
}