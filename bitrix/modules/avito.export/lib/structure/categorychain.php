<?php
namespace Avito\Export\Structure;

use Avito\Export\Concerns;
use Avito\Export\Feed;
use Avito\Export\Structure;

class CategoryChain implements \IteratorAggregate
{
	use Concerns\HasOnce;

	protected $chain;

	/** @param Structure\Category[] $chain */
	public function __construct(array $chain)
	{
		$this->chain = $chain;
	}

	public function index() : Category
	{
		return $this->chain[0];
	}

	public function getIterator() : \ArrayIterator
	{
		return new \ArrayIterator($this->chain);
	}

	/** @return Feed\Tag\Tag[] */
	public function tags() : array
	{
		return $this->once('tags', function() {
			$result = [];

			foreach ($this->chain as $category)
			{
				if (!($category instanceof Structure\CategoryWithTags)) { continue; }

				foreach ($category->tags() as $tag)
				{
					$name = $tag->name();

					if (isset($result[$name])) { continue; }

					$result[$name] = $tag;
				}
			}

			return $result;
		});
	}
}