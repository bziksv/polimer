<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Item;

use Avito\Export\Api;

class Discounts extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Discount::class;
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