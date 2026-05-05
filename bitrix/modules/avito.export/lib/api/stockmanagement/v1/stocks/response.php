<?php
namespace Avito\Export\Api\StockManagement\V1\Stocks;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function stocks() : Response\Stocks
	{
		return $this->requireCollection('stocks', Response\Stocks::class);
	}
}