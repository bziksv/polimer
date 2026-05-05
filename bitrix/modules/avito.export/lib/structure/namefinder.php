<?php
namespace Avito\Export\Structure;

use Bitrix\Main;
use Avito\Export\Concerns;
use Avito\Export\Utils\Caller;
use Avito\Export\Admin\Property\CategoryProvider;

class NameFinder
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public function category(Category $index, array $values) : ?CategoryChain
	{
		$values = array_intersect_key($values, array_flip(CategoryLevelRegistry::tags()));

		if (empty($values)) { return null; }

		$hash = Caller::argumentsHash($index, $values);

		return $this->once('category-' . $hash, function() use ($index, $values) {
			$chain = $this->categoryFind($index, $values);

			if (empty($chain)) { return null; }

			array_unshift($chain, $index);

			return new CategoryChain($chain);
		});
	}

	protected function categoryFind(Category $parent, array $values) : ?array
	{
		if (empty($values)) { return []; }

		$children = $parent->children();

		// search by category level

		foreach ($children as $category)
		{
			if (!($category instanceof CategoryLevel)) { continue; }

			$levelName = $category->categoryLevel();

			if (isset($levelName, $values[$levelName]) && $this->matchCategoryName($category, $values[$levelName]))
			{
				unset($values[$levelName]);

				$result = $this->categoryFind($category, $values);
				array_unshift($result, $category);

				return $result;
			}
		}

		// search by virtual categories

		foreach ($children as $category)
		{
			if ($category instanceof CategoryLevel && $category->categoryLevel() !== null) { continue; }

			$foundChain = $this->categoryFind($category, $values);

			if (empty($foundChain)) { continue; }

			$result = $foundChain;
			array_unshift($result, $category);

			return $result;
		}

		return [];
	}

	/** @return Category[] */
	public function tags(Category $root, array $values) : array
	{
		if (empty($values)) { return []; }

		$result = [];
		$parent = $root;

		while ($children = $parent->children())
		{
			$found = null;

			foreach ($children as $category)
			{
				if (!($category instanceof CategoryLevel))  { continue; }

				$categoryLevel = $category->categoryLevel();

				if (
					isset($categoryLevel, $values[$categoryLevel])
					&& $values[$categoryLevel] === $category->name()
				)
				{
					$found = $category;
					unset($values[$categoryLevel]);
					break;
				}
			}

			if ($found === null) { break; }

			$result[] = $found;
			$parent = $found;
		}

		return $result;
	}

	/** @return Category[] */
	public function path(Category $root, string $path) : array
	{
		$nameChain = explode(CategoryProvider::VALUE_GLUE, $path);
		$level = $root;
		$result = [];

		foreach ($nameChain as $name)
		{
			$matched = null;
			$name = str_replace('\\' . CategoryProvider::VALUE_ESCAPE, CategoryProvider::VALUE_ESCAPE, $name);

			foreach ($level->children() as $child)
			{
				if ($this->matchCategoryName($child, $name))
				{
					$matched = $child;
					break;
				}
			}

			if ($matched === null)
			{
				throw new Main\ArgumentException(self::getLocale('NOT_FOUND', [
					'#NAME#' => $name,
				]));
			}

			$level = $matched;
			$result[] = $matched;
		}

		return $result;
	}

	protected function matchCategoryName(Category $category, string $name) : bool
	{
		$result = false;

		if ($this->compareName($category->name(), $name))
		{
			$result = true;
		}
		else if ($category instanceof CategoryCompatible)
		{
			foreach ($category->oldNames() as $oldName)
			{
				if ($this->compareName($oldName, $name))
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	protected function compareName(string $first, string $second) : bool
	{
		return mb_strtolower(trim($first)) === mb_strtolower(trim($second));
	}
}