<?php
namespace Avito\Export\Api\OrderManagement\V1\Markings;

use Bitrix\Main\Text\Encoding;
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
		$text = '{
		"results": [
		{
			"error": "Order with such id doesn\'t exist",
			"itemId": "59",
			"orderId": "49",
			"success": false
		}
	]
}';

		return Encoding::convertEncoding($text, LANG_CHARSET, 'UTF-8');
	}

	public function getContentType() : string
	{
		return 'application/json';
	}
}