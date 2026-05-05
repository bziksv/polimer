<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

/**
 * @property Item[] $collection
*/
class Items extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Item::class;
	}

	public function ids() : array
	{
		$result = [];

		foreach ($this->collection as $item)
		{
			$id = $item->id();

			if ($id === null) { continue; }

			$result[] = $id;
		}

		return $result;
	}
}