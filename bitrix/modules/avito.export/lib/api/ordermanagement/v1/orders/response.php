<?php
namespace Avito\Export\Api\OrderManagement\V1\Orders;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function hasMore() : bool
	{
		return (bool)$this->getValue('hasMore');
	}

	public function orders() : Api\OrderManagement\Model\Orders
	{
		return $this->requireCollection('orders', Api\OrderManagement\Model\Orders::class);
	}
}