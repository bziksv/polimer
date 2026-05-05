<?php
namespace Avito\Export\Api\OrderManagement\V1\Markings;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/order-management/1/markings';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function markings(array $markings) : void
	{
		$this->query['markings'] = $markings;
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
