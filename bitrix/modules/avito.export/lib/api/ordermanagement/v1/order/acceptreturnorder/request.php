<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\AcceptReturnOrder;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/order/acceptReturnOrder';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function orderId(string $orderId) : void
	{
		$this->query['orderId'] = $orderId;
	}

	public function recipient(string $name, string $phone) : void
	{
		$this->query['recipient'] = [
			'name' => $name,
			'phone' => $phone
		];
	}

	public function terminalNumber(string $terminalNumber) : void
	{
		$this->query['terminalNumber'] = $terminalNumber;
	}

	protected function validationQueue($data, Main\Web\HttpClient $transport) : Api\Validator\Queue
	{
		return parent::validationQueue($data, $transport)
             ->add(new Api\Validator\SuccessTrue($data, $transport));
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
