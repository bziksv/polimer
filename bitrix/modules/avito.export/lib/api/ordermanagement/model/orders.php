<?php
namespace Avito\Export\Api\OrderManagement\Model;

use Avito\Export\Api;

/**
 * @property Order[] $collection
*/
class Orders extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Order::class;
	}

	public function ids() : array
	{
		$result = [];

		foreach ($this->collection as $item)
		{
			$result[] = $item->id();
		}

		return $result;
	}
}