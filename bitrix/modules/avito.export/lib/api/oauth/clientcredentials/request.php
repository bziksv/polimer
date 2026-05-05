<?php
namespace Avito\Export\Api\OAuth\ClientCredentials;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\RequestWithClient
{
	protected $query = [
		'grant_type' => 'client_credentials',
	];

	public function url() : string
	{
		return 'https://api.avito.ru/token';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function query() : ?array
	{
		return $this->query + $this->queryClient();
	}

	protected function encodeBody($query, Main\Web\HttpClient $transport)
	{
		return $query;
	}

	/**
	 * @param array $data
	 *
	 * @return Response
	 */
	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
