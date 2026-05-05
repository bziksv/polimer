<?php
namespace Avito\Export\Api\Core\V1\Items\Item\UpdatePrice;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;

class ErrorMockTransport extends HttpClient
{
	public function getError() : array
	{
		return [];
	}

	public function query($method, $url, $entityBody = null) : bool
	{
		return true;
	}

    public function getStatus() : int
    {
        return 404;
    }

	public function getResult() : string
	{
		$text = '{
            "error": {
                "message": "Item not found",
                "code": 404
            }
        }';

		return Encoding::convertEncoding($text, LANG_CHARSET, 'UTF-8');
	}

	public function getContentType() : string
	{
		return 'application/json';
	}
}