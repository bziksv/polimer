<?php
namespace Avito\Export\Api\StockManagement\V1\Stocks\Response;

use Avito\Export\Api;

class Stocks extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Stock::class;
	}
}