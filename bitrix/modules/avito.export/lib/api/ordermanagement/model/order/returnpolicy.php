<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

class ReturnPolicy extends Api\Response
{
	public function returnStatus() : string
	{
		return (string)$this->requireValue('returnStatus');
	}
}