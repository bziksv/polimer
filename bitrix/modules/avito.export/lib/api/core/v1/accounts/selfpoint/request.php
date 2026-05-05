<?php
namespace Avito\Export\Api\Core\V1\Accounts\SelfPoint;

use Avito\Export\Api;
use Bitrix\Main;

/**
 * @method Response execute()
 */
class Request extends Api\RequestWithToken
{
	public function url() : string
	{
		return 'https://api.avito.ru/core/v1/accounts/self';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function query() : ?array
	{
		return [];
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
