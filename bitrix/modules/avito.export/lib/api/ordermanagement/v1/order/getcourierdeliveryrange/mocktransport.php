<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\GetCourierDeliveryRange;

use Bitrix\Main\Web\HttpClient;

class MockTransport extends HttpClient
{
	public function getError() : array
	{
		return [];
	}

	public function query($method, $url, $entityBody = null) : bool
	{
		return true;
	}

	public function getResult() : string
	{
		return '{
"code": 500,
"message": "Error message"
}';
	}

	public function getContentType() : string
	{
		return 'application/json';
	}
}