<?php
namespace Avito\Export\Api\Messenger\V1\Webhook\Unsubscribe;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

/**
 * @method Response execute()
 */
class Request extends Api\RequestWithToken
{
    protected $query = [];

    public function url() : string
	{
		return 'https://api.avito.ru/messenger/v1/webhook/unsubscribe';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

    public function userUrl(string $url) : void
    {
        $this->query['url'] = $url;
    }

	public function query() : ?array
	{
		Assert::notNull($this->query['url'], 'url');

		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
