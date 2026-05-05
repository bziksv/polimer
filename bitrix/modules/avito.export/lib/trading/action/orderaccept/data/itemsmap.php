<?php
namespace Avito\Export\Trading\Action\OrderAccept\Data;

use Avito\Export\Api\OrderManagement\Model\Order\Item;

class ItemsMap implements \IteratorAggregate
{
	private $map;

	public function __construct()
	{
		$this->map = new \SplObjectStorage();
	}

	public function getIterator() : \SplObjectStorage
	{
		return $this->map;
	}

	public function set(Item $item, $value) : void
	{
		$this->map->offsetSet($item, $value);
	}

	public function get(Item $item)
	{
		return $this->map->contains($item) ? $this->map->offsetGet($item) : null;
	}

	public function values() : array
	{
		$result = [];

		foreach ($this->map as $item)
		{
			$result[] = $this->map->offsetGet($item);
		}

		return $result;
	}

	public function diff(array $items) : array
	{
		$result = [];

		foreach ($items as $item)
		{
			if (!$this->map->contains($item))
			{
				$result[] = $item;
			}
		}

		return $result;
	}

	public function append(ItemsMap $itemsMap) : void
	{
		foreach ($itemsMap as $item)
		{
			$this->set($item, $itemsMap->get($item));
		}
	}
}