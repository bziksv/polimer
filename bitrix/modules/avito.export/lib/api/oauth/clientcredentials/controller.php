<?php
namespace Avito\Export\Api\OAuth\ClientCredentials;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Api;

class Controller
{
	protected $clientId;
	protected $clientSecret;

	public function __construct(array $incomingData)
	{
		Assert::notEmptyString($incomingData['clientId'], '$incomingData[clientId]');
		Assert::notEmptyString($incomingData['clientSecret'], '$incomingData[clientSecret]');

		$this->clientId = (string)$incomingData['clientId'];
		$this->clientSecret = (string)$incomingData['clientSecret'];
	}

	public function run() : Api\OAuth\Token
	{
		$response = $this->exchangeToken();

		$token = $this->makeToken($response);
		$token = $this->userInfo($token);

		return $this->saveToken($token);
	}

	protected function exchangeToken() : Response
	{
		$exchangeRequest = new Request();

		$exchangeRequest->clientId($this->clientId);
		$exchangeRequest->clientSecret($this->clientSecret);

		return $exchangeRequest->execute();
	}

	protected function makeToken(Response $exchangeResponse) : Api\OAuth\Token
	{
		$token = new Api\OAuth\Token();
		$token->setClientId($this->clientId);
		$token->setType($exchangeResponse->tokenType());
		$token->setAccessToken($exchangeResponse->accessToken());
		$token->setRefreshToken(Api\OAuth\TokenTable::CLIENT_OWNER);
		$token->setExpires(
			(new Main\Type\DateTime())
				->add(sprintf('PT%sS', $exchangeResponse->expiresIn()))
		);

		return $token;
	}

	protected function userInfo(Api\OAuth\Token $token) : Api\OAuth\Token
	{
		$userRequest = new Api\Core\V1\Accounts\SelfPoint\Request();
		$userRequest->token($token);

		$userResponse = $userRequest->execute();

		$token->setServiceId($userResponse->id());
		$token->setName($userResponse->name() ?? $userResponse->email() ?? $userResponse->phone());

		return $token;
	}

	protected function saveToken(Api\OAuth\Token $token) : Api\OAuth\Token
	{
		$query = Api\OAuth\TokenTable::getList([
			'filter' => [
				'=CLIENT_ID' => $token->getClientId(),
				'=SERVICE_ID' => $token->getServiceId(),
			],
		]);

		if ($existToken = $query->fetchObject())
		{
			$existToken->setAccessToken($token->getAccessToken());
			$existToken->setRefreshToken($token->getRefreshToken());
			$existToken->setExpires($token->getExpires());
			$existToken->setType($token->getType());
			$existToken->setName($token->getName());

			$saveResult = $existToken->save();
			$resultToken = $existToken;
		}
		else
		{
			$saveResult = $token->save();
			$resultToken = $token;
		}

		if (!$saveResult->isSuccess())
		{
			throw new Main\SystemException(implode(PHP_EOL, $saveResult->getErrorMessages()));
		}

		return $resultToken;
	}
}