<?php
namespace Avito\Export\Dictionary;

class Fixed implements Dictionary
{
	protected $values;
	protected $useParent;

	public function __construct(array $values, array $parameters = [])
	{
		$this->values = $values;
		$this->useParent = $parameters['parent'] ?? true;
	}

	public function useParent() : bool
	{
		return $this->useParent;
	}

	public function known(array $attributes) : array
	{
		$result = [];

		foreach ($attributes as $attribute)
		{
			if (!isset($this->values[$attribute])) { continue; }

			$variants = $this->values[$attribute];

			if ($variants instanceof Listing\Listing)
			{
				$variants = $variants->values();
			}

			foreach ($variants as $variant)
			{
				$result[] = [
					'name' => $attribute,
					'value' => $variant,
				];
			}
		}

		return $result;
	}

	public function attributes(array $values = []) : array
	{
		$missing = array_diff_key($this->values, $values);

		return array_keys($missing);
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		if (!isset($this->values[$attribute])) { return null; }

		$variants = $this->values[$attribute];

		if ($variants instanceof Listing\Listing)
		{
			$variants = $variants->values();
		}

		return $variants;
	}
}