<?php
namespace Avito\Export\Trading\Action\OrderAccept;

use Avito\Export\Api\OrderManagement\Model\Order;
use Avito\Export\Trading\Action\Reference as TradingReference;

class Command implements TradingReference\Command
{
	protected $order;

	public function __construct(Order $order)
	{
		$this->order = $order;
	}

	public function order() : Order
	{
		return $this->order;
	}
}