<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;

class AvailableAction extends Api\Response
{
	public function name() : string
	{
		return (string)$this->requireValue('name');
	}

	public function required() : bool
	{
		return (bool)$this->getValue('required');
	}
}