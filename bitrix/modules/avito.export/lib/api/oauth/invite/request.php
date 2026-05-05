<?php
namespace Avito\Export\Api\OAuth\Invite;

use Avito\Export\Api;
use Bitrix\Main;

class Request extends Api\Request
{
	protected $query = [
		'response_type' => 'code',
		'client_id' => null,
		'scope' => 'user:read,items:info',
	];

	public function fullUrl() : string
	{
		return $this->url() . '?' . http_build_query($this->query());
	}

	public function url() : string
	{
		return 'https://avito.ru/oauth';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

	public function query() : ?array
	{
		return $this->query;
	}

	public function clientId(string $clientId) : void
	{
		$this->query['client_id'] = $clientId;
	}

	public function clientSecret(string $secret) : void
	{
		$this->query['client_secret'] = $secret;
	}

	public function scope(array $scopes) : void
	{
		$this->query['query'] = implode(',', $scopes);
	}

	public function validateResponse($data, Main\Web\HttpClient $transport = null) : void
	{
		if ($transport === null) { $transport = $this->buildTransport(); }

		parent::validateResponse($data, $transport);
	}

	/**
	 * @param array $data
	 *
	 * @return Response
	 */
	public function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
