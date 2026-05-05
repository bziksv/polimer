<?php
namespace Avito\Export\Api\StockManagement\V1\Stocks\Response;

use Avito\Export\Api;

class Stock extends Api\Response
{
	public function itemId() : string
	{
		return (string)$this->requireValue('item_id');
	}

	public function success() : bool
	{
		return (bool)$this->getValue('success');
	}

	/** @return string[]|null */
	public function errors() : ?array
	{
		return $this->getValue('errors');
	}
}