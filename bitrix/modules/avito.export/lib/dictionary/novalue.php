<?php
namespace Avito\Export\Dictionary;

class NoValue implements Dictionary
{
	protected $useParent;

	public function __construct(array $parameters = [])
	{
		$this->useParent = $parameters['parent'] ?? true;
	}

	public function useParent() : bool
	{
		return $this->useParent;
	}

	public function known(array $attributes) : array
	{
		return [];
	}

	public function attributes(array $values = []) : array
	{
		return [];
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		return null;
	}
}