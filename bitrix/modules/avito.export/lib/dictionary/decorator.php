<?php
namespace Avito\Export\Dictionary;

class Decorator implements Dictionary
{
	protected $dictionary;
	protected $rename;
	protected $wait;
	protected $include;
	protected $useParent;

	public function __construct(Dictionary $dictionary, array $parameters = [])
	{
		$this->dictionary = $dictionary;
		$this->rename = $parameters['rename'] ?? [];
		$this->wait = $parameters['wait'] ?? [];
		$this->include = $parameters['include'] ?? null;
		$this->useParent = $parameters['parent'] ?? true;
	}

	public function useParent() : bool
	{
		return $this->useParent;
	}

	public function known(array $attributes) : array
	{
		return $this->dictionary->known($attributes);
	}

	public function attributes(array $values = []) : array
	{
		if (!$this->ready($values)) { return []; }

		$values = $this->renameValues($values);
		$attributes = $this->dictionary->attributes($values);

		// rename

		foreach ($attributes as &$attribute)
		{
			if (isset($this->rename[$attribute]))
			{
				$attribute = $this->rename[$attribute];
			}
		}
		unset($attribute);

		// include

		if ($this->include !== null)
		{
			$attributes = array_values(array_intersect($attributes, $this->include));
		}

		return $attributes;
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		if (!$this->ready($values)) { return null; }
		if (isset($this->rename[$attribute])) { return null; }

		$values = $this->renameValues($values);
		$rename = array_search($attribute, $this->rename, true);

		if ($rename !== false)
		{
			$attribute = (string)$rename;
		}

		return $this->dictionary->variants($attribute, $values);
	}

	protected function ready(array $values) : bool
	{
		foreach ($this->wait as $name => $expected)
		{
			$not = false;
			if (mb_strpos($name, '!') === 0)
			{
				$name = mb_substr($name, 1);
				$not = true;
			}

			$value = $values[$name] ?? null;

			if ($value === null) { return false; }

			if (is_array($expected))
			{
				$valueMatched = in_array($value, $expected, true);
			}
			else
			{
				$valueMatched = ($value === $expected);
			}

			if ($not)
			{
				$valueMatched = !$valueMatched;
			}

			if (!$valueMatched) { return false; }
		}

		return true;
	}

	protected function renameValues(array $values) : array
	{
		foreach ($this->rename as $atFile => $atFormat)
		{
			unset($values[$atFile]);
			if (!isset($values[$atFormat]) && !array_key_exists($atFormat, $values)) { continue; }

			$values[$atFile] = $values[$atFormat];
			unset($values[$atFormat]);
		}

		return $values;
	}
}