<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;

class DictionaryCategoryProxy implements Category, CategoryLevel, CategoryCompatible, CategoryWithTags
{
	use Concerns\HasOnce;

	private $origin;

	public function __construct(Category $origin)
	{
		$this->origin = $origin;
	}

	public function name() : string
	{
		return $this->origin->name();
	}

	public function dictionary() : Dictionary\Dictionary
	{
		return $this->origin->dictionary();
	}

	public function children() : array
	{
		return $this->once('children', function() {
			$children = $this->origin->children();

			if (empty($children))
			{
				return (new DictionaryCategoryFactory())->make($this->dictionary());
			}

			$result = [];

			foreach ($children as $child)
			{
				$result[] = new DictionaryCategoryProxy($child);
			}

			return $result;
		});
	}

	public function categoryLevel() : ?string
	{
		if ($this->origin instanceof CategoryLevel)
		{
			return $this->origin->categoryLevel();
		}

		return null;
	}

	public function oldNames() : array
	{
		if ($this->origin instanceof CategoryCompatible)
		{
			return $this->origin->oldNames();
		}

		return [];
	}

	public function tags() : array
	{
		if ($this->origin instanceof CategoryWithTags)
		{
			return $this->origin->tags();
		}

		return [];
	}
}