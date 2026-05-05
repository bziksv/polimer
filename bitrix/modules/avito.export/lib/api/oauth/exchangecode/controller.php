<?php
namespace Avito\Export\Api\OAuth\ExchangeCode;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Api;

class Controller extends Api\OAuth\ClientCredentials\Controller
{
	protected $code;
	protected $clientId;
	protected $clientSecret;

	public function __construct(array $incomingData)
	{
		parent::__construct($incomingData);

		Assert::notNull($incomingData['code'], '$incomingData[code]');

		$this->code = (string)$incomingData['code'];
	}

	protected function exchangeToken() : Api\OAuth\ClientCredentials\Response
	{
		$exchangeRequest = new Request();

		$exchangeRequest->code($this->code);
		$exchangeRequest->clientId($this->clientId);
		$exchangeRequest->clientSecret($this->clientSecret);

		return $exchangeRequest->execute();
	}

	protected function makeToken(Api\OAuth\ClientCredentials\Response $exchangeResponse) : Api\OAuth\Token
	{
		/** @var Response $exchangeResponse */
		Assert::typeOf($exchangeResponse, Response::class, 'exchangeResponse');

		$token = new Api\OAuth\Token();
		$token->setClientId($this->clientId);
		$token->setType($exchangeResponse->tokenType());
		$token->setAccessToken($exchangeResponse->accessToken());
		$token->setRefreshToken($exchangeResponse->refreshToken());
		$token->setExpires(
			(new Main\Type\DateTime())
				->add(sprintf('PT%sS', $exchangeResponse->expiresIn()))
		);

		return $token;
	}
}