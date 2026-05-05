<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Delivery;

use Avito\Export\Api;

class TerminalInfo extends Api\Response
{
	public function address() : ?string
	{
		return $this->getValue('address');
	}

	public function code() : ?string
	{
		return $this->getValue('code');
	}
}