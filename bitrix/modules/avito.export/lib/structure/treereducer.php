<?php
namespace Avito\Export\Structure;

class TreeReducer
{
	protected $callback;
	protected $includeRoot;

	public function __construct(callable $callback, bool $includeRoot = false)
	{
		$this->callback = $callback;
		$this->includeRoot = $includeRoot;
	}

	public function reduce(Category $root, $initial = null)
	{
		if ($this->includeRoot)
		{
			$carry = $this->walkCategory($root, [], $initial);
		}
		else
		{
			$carry = $this->walkChildren($root->children(), [], $initial);
		}

		return $carry;
	}

	protected function walkCategory(Category $category, array $chain, $carry)
	{
		$carry = $this->apply($category, $chain, $carry);
		$carry = $this->walkChildren($category->children(), array_merge($chain, [$category]), $carry);

		return $carry;
	}

	protected function walkChildren(array $children, array $chain, $carry)
	{
		foreach ($children as $child)
		{
			$carry = $this->walkCategory($child, $chain, $carry);
		}

		return $carry;
	}

	protected function apply(Category $category, array $chain, $carry)
	{
		return call_user_func($this->callback, $carry, $category, $chain);
	}
}