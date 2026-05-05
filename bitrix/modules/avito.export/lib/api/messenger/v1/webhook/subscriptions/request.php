<?php
namespace Avito\Export\Api\Messenger\V1\Webhook\Subscriptions;

use Avito\Export\Api;
use Bitrix\Main;

/**
 * @method Response execute()
 */
class Request extends Api\RequestWithToken
{
    protected $query = [];

    public function url() : string
	{
		return 'https://api.avito.ru/messenger/v1/subscriptions';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function query() : ?array
	{
		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
