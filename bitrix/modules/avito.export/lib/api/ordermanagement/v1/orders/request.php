<?php
namespace Avito\Export\Api\OrderManagement\V1\Orders;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/orders';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function ids(array $ids) : void
	{
		$this->query['ids'] = $ids;
	}

	public function statuses(array $statuses) : void
	{
		$this->query['statuses'] = $statuses;
	}

    public function limit(int $limit) : void
    {
        $this->query['limit'] = $limit;
    }

	public function page(int $number) : void
	{
		$this->query['page'] = $number;
	}

	public function query() : ?array
	{
		return $this->query;
	}

	/*protected function buildTransport() : Main\Web\HttpClient
	{
		return new MockTransport();
	}*/

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
