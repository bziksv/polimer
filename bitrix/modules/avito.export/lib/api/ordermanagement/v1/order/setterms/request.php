<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\SetTerms;

use Avito\Export\Api;
use Avito\Export\Data;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/order/setTerms';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function orderId(string $orderId) : void
	{
		$this->query['orderId'] = $orderId;
	}

	public function deliveryCostRub(float $deliveryCostRub) : void
	{
		$this->query['deliveryCostRub'] = $deliveryCostRub;
	}

	public function deliveryDate(Main\Type\Date $deliveryDate = null) : void
	{
		if ($deliveryDate === null) { return; }

		$this->query['deliveryDate'] = Data\Date::forService($deliveryDate);
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
