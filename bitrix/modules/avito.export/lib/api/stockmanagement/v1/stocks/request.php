<?php
namespace Avito\Export\Api\StockManagement\V1\Stocks;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/stock-management/1/stocks';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_PUT;
	}

	public function stocks(array $stocks) : void
	{
		$this->query['stocks'] = $stocks;
	}

	public function query() : ?array
	{
		Assert::notNull($this->query['stocks'], 'stocks');

		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
