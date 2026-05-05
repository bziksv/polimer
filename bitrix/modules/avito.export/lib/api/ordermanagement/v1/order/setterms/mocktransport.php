<?php
namespace Avito\Export\Api\OrderManagement\V1\Order\SetTerms;

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
			"error": {
				"code": "delivery_date_to_low",
				"message": "Дата доставки не может быть в прошлом"
			},
			"success": false
		}';
	}

	public function getContentType() : string
	{
		return 'application/json';
	}
}