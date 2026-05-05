<?php
namespace Avito\Export\Api\OAuth\ExchangeCode;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

class Request extends Api\RequestWithClient
{
	protected $query = [
		'grant_type' => 'authorization_code',
		'code' => null,
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
		Assert::notNull($this->query['code'], 'code');

		return $this->query + $this->queryClient();
	}

	protected function encodeBody($query, Main\Web\HttpClient $transport)
	{
		return $query;
	}

	public function code(string $code) : void
	{
		$this->query['code'] = $code;
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
