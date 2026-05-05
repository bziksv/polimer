<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Delivery;

use Avito\Export\Api;

class BuyerInfo extends Api\Response
{
	public function fullName() : ?string
	{
		return $this->getValue('fullName');
	}

	public function phoneNumber() : ?string
	{
		return $this->getValue('phoneNumber');
	}
}