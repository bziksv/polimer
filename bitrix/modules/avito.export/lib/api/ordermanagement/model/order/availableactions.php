<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

/**
 * @property AvailableAction[] $collection
*/
class AvailableActions extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return AvailableAction::class;
	}

	public function has(string $name) : bool
	{
		$result = false;

		foreach ($this->collection as $action)
		{
			if ($action->name() === $name)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}