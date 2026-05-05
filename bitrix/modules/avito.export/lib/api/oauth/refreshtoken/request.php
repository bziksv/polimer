<?php
namespace Avito\Export\Api\OAuth\RefreshToken;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

class Request extends Api\RequestWithClient
{
	protected $query = [
		'grant_type' => 'refresh_token',
		'refresh_token' => null,
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
		Assert::notNull($this->query['refresh_token'], 'code');

		return $this->query + $this->queryClient();
	}

	protected function encodeBody($query, Main\Web\HttpClient $transport)
	{
		return $query;
	}

	public function refreshToken(string $token) : void
	{
		$this->query['refresh_token'] = $token;
	}

	protected function validateResponse($data, Main\Web\HttpClient $transport) : void
	{
		(new Api\Validator\Queue())
			->add(new Api\Validator\FormatArray($data, $transport))
			->add(new Api\Validator\ResponseError($data, $transport))
			->run();
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
