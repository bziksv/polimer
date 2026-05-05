<?php
namespace Avito\Export\Api\OrderManagement\Model\Order\Item;

use Avito\Export\Api;
use Avito\Export\Data\Number;

class Prices extends Api\Response
{
	public function commission() : float
	{
		return (float)$this->getValue('commission');
	}

	public function discountSum() : float
	{
		return (float)$this->getValue('discountSum');
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