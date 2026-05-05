<?php
namespace Avito\Export\Dictionary;

use Avito\Export\Structure;
use Bitrix\Main;

class CategoryLevel implements Dictionary
{
	/** @var Structure\Category */
	protected $category;

	public function __construct(Structure\Category $category)
	{
		$this->category = $category;
	}

	public function category() : Structure\Category
	{
		return $this->category;
	}

	public function useParent() : bool
	{
		return false;
	}

	public function known(array $attributes) : array
	{
		throw new Main\NotImplementedException('known for ' . static::class);
	}

	public function attributes(array $values = []) : array
	{
		$result = [];

		foreach ($this->targetCategory($values)->children() as $child)
		{
			if ($child instanceof Structure\CategoryLevel && $child->categoryLevel() !== null)
			{
				$result[$child->categoryLevel()] = true;
			}
		}

		return array_keys($result);
	}

	public function variants(string $attribute, array $values = []) : ?array
	{
		$result = [];

		foreach ($this->targetCategory($values)->children() as $child)
		{
			if ($child instanceof Structure\CategoryLevel && $child->categoryLevel() === $attribute)
			{
				$result[] = $child->name();
			}
		}

		return !empty($result) ? $result : null;
	}

	protected function targetCategory(array $values) : Structure\Category
	{
		$finder = new Structure\NameFinder();
		$chain = $finder->tags($this->category, $values);

		return !empty($chain) ? end($chain) : $this->category;
	}
}