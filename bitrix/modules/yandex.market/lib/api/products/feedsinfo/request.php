<?php
namespace Yandex\Market\Api\Products\FeedsInfo;

use Bitrix\Main;
use Yandex\Market\Api\Products\Reference;

class Request extends Reference\Request
{
	public function getPath()
	{
		return '/products/api/ext/partner/feeds-info';
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}
}
