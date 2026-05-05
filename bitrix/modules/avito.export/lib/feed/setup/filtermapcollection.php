<?php
namespace Avito\Export\Feed\Setup;

use Avito\Export\Concerns;

/**
 * @property FilterMap[] $collection
 */
class FilterMapCollection
	implements \ArrayAccess, \Countable, \IteratorAggregate
{
	use Concerns\HasCollection;

	public function __construct(array $filters)
	{
		$this->collection = $this->boot($filters);
	}

	protected function boot(array $filters) : array
	{
		$result = [];

		foreach ($filters as $filter)
		{
			$result[] = new FilterMap($filter);
		}

		if (empty($result))
		{
			$result[] = new FilterMap([]);
		}

		return $result;
	}

	public function sources() : array
	{
		$result = [];

		foreach ($this->collection as $filterMap)
		{
			foreach ($filterMap->sources() as $type => $conditions)
			{
				if (!isset($result[$type]))
				{
					$result[$type] = [];
				}

				array_push($result[$type], ...$conditions);
			}
		}

		return $result;
	}
}