<?php
namespace Avito\Export\Api\OrderManagement\Model\Order;

use Avito\Export\Api;
use Avito\Export\Data\Number;

class Prices extends Api\Response
{
	public function commission() : float
	{
		return (float)$this->getValue('commission');
	}

	public function delivery() : ?float
	{
		return Number::castFloat($this->getValue('delivery'));
	}

	public function discount() : float
	{
		return (float)$this->getValue('discount');
	}

	public function price() : ?float
	{
		return Number::castFloat($this->getValue('price'));
	}

	public function total() : ?float
	{
		return Number::castFloat($this->getValue('total'));
	}
}