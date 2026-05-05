<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Delivery;

use Avito\Export\Api;

class CourierInfo extends Api\Response
{
	public function address() : ?string
	{
		return $this->getValue('address');
	}

	public function comment() : ?string
	{
		return $this->getValue('comment');
	}
}