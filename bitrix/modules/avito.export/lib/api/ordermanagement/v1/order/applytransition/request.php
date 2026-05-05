<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\ApplyTransition;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/order/applyTransition';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function orderId(string $orderId) : void
	{
		$this->query['orderId'] = $orderId;
	}

	public function transition(string $transition) : void
	{
		$this->query['transition'] = $transition;
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
