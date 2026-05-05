<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Item;

use Avito\Export\Api;

class Discount extends Api\Response
{
	public function id() : string
	{
		return (string)$this->getValue('id');
	}

	public function type() : string
	{
		return (string)$this->getValue('type');
	}

	public function value() : float
	{
		return (float)$this->getValue('value');
	}
}