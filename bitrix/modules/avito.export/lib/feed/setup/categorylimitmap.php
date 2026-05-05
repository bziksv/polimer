<?php
namespace Avito\Export\Feed\Setup;

use Avito\Export\Admin\Property\CategoryProvider;
use Avito\Export\Concerns;
use Avito\Export\Feed\Tag\Format;
use Avito\Export\Structure\Category;

class CategoryLimitMap
{
	use Concerns\HasOnce;

	protected $map;

	public function __construct(array $map)
	{
		$this->map = $this->parse($map);
	}

	public function isEmpty() : bool
	{
		return empty($this->map);
	}

	/**
	 * @param Format $format
	 * @param string $category
	 * @param string|null $goodsType
	 *
	 * @return array<array{COUNT: int, CATEGORY_VALUE: string, CATEGORY_CHAIN: string[]}>
	 */
	public function filter(Format $format, string $category, string $goodsType = null) : array
	{
		$result = [];

		foreach ($this->map as $index => $item)
		{
			if ($item['CATEGORY_VALUE'] !== $category) { continue; }

			if (
				$this->goodsTypeMatchedChain($item['CATEGORY_CHAIN'], $goodsType)
				|| $this->goodsTypeMatchedCategoryTree($item['CATEGORY_CHAIN'], $format, $category, $goodsType)
			)
			{
				$result[$index] = $item;
			}
		}

		return $result;
	}

	protected function goodsTypeMatchedChain(array $chain, string $goodsType = null) : bool
	{
		if (empty($chain))
		{
			$result = true;
		}
		else if ($goodsType === null)
		{
			$result = empty($chain);
		}
		else
		{
			$result = in_array($goodsType, $chain, true);
		}

		return $result;
	}

	protected function goodsTypeMatchedCategoryTree(array $chain, Format $format, string $category, string $goodsType = null) : bool
	{
		if ($goodsType === null) { return false; }

		$structureCategory = $this->searchCategory($format, $category);

		if ($structureCategory === null) { return false; }

		$chainCategory = $this->searchChainCategory($structureCategory, $chain);

		if ($chainCategory === null) { return false; }

		return $this->searchCategoryGoodsType($chainCategory, $goodsType) !== null;
	}

	protected function searchCategory(Format $format, string $category) : ?Category
	{
		$result = null;

		foreach ($format->categoryChain()->index()->children() as $root)
		{
			foreach ($root->children() as $firstLevel)
			{
				if ($firstLevel->name() === $category)
				{
					$result = $firstLevel;
					break;
				}
			}
		}

		return $result;
	}

	protected function searchChainCategory(Category $category, array $chain) : ?Category
	{
		$level = $category;

		while ($searchName = array_shift($chain))
		{
			$found = null;

			foreach ($level->children() as $child)
			{
				if ($child->name() === $searchName)
				{
					$found = $child;
					break;
				}
			}

			if ($found === null)
			{
				$level = null;
				break;
			}

			$level = $found;
		}

		return $level;
	}

	protected function searchCategoryGoodsType(Category $category, string $name) : ?Category
	{
		$result = null;

		foreach ($category->children() as $child)
		{
			if ($child->name() === $name)
			{
				$result = $child;
				break;
			}

			$childMatched = $this->searchCategoryGoodsType($child, $name);

			if ($childMatched !== null)
			{
				$result = $childMatched;
				break;
			}
		}

		return $result;
	}

	public function all() : array
	{
		return $this->map;
	}

	protected function parse(array $map) : array
	{
		$result = [];

		foreach ($map as $index => $row)
		{
			$rowLimit = trim($row['LIMIT']);
			$rowCategory = trim($row['CATEGORY']);

			if ($rowLimit === '' || $rowCategory === '') { continue; }

			$chain = explode(CategoryProvider::VALUE_GLUE, $rowCategory);
			$category = CategoryProvider::exportValue([ 'VALUE' => $chain ], 'category');
			$categoryIndex = array_search($category, $chain, true);

			if ($categoryIndex === false) { continue; }

			$result[$index] = [
				'CATEGORY_VALUE' => $category,
				'CATEGORY_CHAIN' => array_slice($chain, $categoryIndex + 1),
				'COUNT' => (int)$rowLimit,
			];
		}

		return $result;
	}
}