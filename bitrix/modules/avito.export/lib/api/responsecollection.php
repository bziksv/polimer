<?php
namespace Avito\Export\Api;

use Avito\Export\Assert;
use Avito\Export\Concerns;

/**
 * @template T
*/
abstract class ResponseCollection
	implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use Concerns\HasCollection;

	public function __construct(array $items)
	{
		$this->collection = $this->compile($items);
	}

	/**
	 * @param array $items
	 *
	 * @return T[]
	 */
	protected function compile(array $items) : array
	{
		$className = $this->itemClass();
		$result = [];

		Assert::isSubclassOf($className, Response::class);

		foreach ($items as $item)
		{
			$result[] = new $className($item);
		}

		return $result;
	}

	/** @return T */
	abstract protected function itemClass() : string;
}