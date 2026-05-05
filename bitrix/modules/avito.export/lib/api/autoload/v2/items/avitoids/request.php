<?php
namespace Avito\Export\Api\Autoload\V2\Items\AvitoIds;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

class Request extends Api\RequestWithToken
{
	protected $query = [];

	public function url() : string
	{
		return 'https://api.avito.ru/autoload/v2/items/avito_ids';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function primary(array $primary) : void
	{
		$this->query['query'] = implode(',', $primary);
	}

	public function query() : ?array
	{
		Assert::notNull($this->query['query'], 'query');

		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
