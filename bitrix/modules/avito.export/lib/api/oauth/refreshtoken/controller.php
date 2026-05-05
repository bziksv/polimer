<?php
namespace Avito\Export\Api\OAuth\RefreshToken;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Api;
use Avito\Export\Push;
use Avito\Export\Exchange;

class Controller
{
	protected $token;
	/** @var Exchange\Setup\Model */
	protected $usedSetup;

	public function __construct(Api\OAuth\Token $token)
	{
		$this->token = $token;
	}

	public function run() : Api\OAuth\Token
	{
		$clientSecret = $this->searchClient();

		$response = $this->token->isOwner()
			? $this->ownToken($clientSecret)
			: $this->refreshToken($clientSecret);

		$token = $this->updateToken($response);

		return $this->saveToken($token);
	}

	public function usedSetup() : ?Exchange\Setup\Model
	{
		return $this->usedSetup;
	}

	/**
	 * @noinspection PhpCastIsUnnecessaryInspection
	 * @noinspection UnnecessaryCastingInspection
	 */
	protected function searchClient() : string
	{
		$result = null;

		$query = Exchange\Setup\RepositoryTable::getList();

		/** @var Exchange\Setup\Model $exchange */
		while ($exchange = $query->fetchObject())
		{
			$settings = $exchange->settingsBridge()->commonSettings();

			if (
				(string)$settings->oauthClientSecret() === ''
				|| $settings->oauthClientId() !== $this->token->getClientId()
			)
			{
				continue;
			}

			$result = $settings->oauthClientSecret();

			$this->usedSetup = $exchange;
		}

		Assert::notNull($result, 'oauthClientSecret');

		return $result;
	}

	protected function ownToken(string $clientSecret) : Api\OAuth\ClientCredentials\Response
	{
		$exchangeRequest = new Api\OAuth\ClientCredentials\Request();

		$exchangeRequest->clientId($this->token->getClientId());
		$exchangeRequest->clientSecret($clientSecret);

		return $exchangeRequest->execute();
	}

	protected function refreshToken(string $clientSecret) : Response
	{
		$exchangeRequest = new Request();

		$exchangeRequest->clientId($this->token->getClientId());
		$exchangeRequest->clientSecret($clientSecret);
		$exchangeRequest->refreshToken($this->token->getRefreshToken());

		return $exchangeRequest->execute();
	}

	protected function updateToken(Api\OAuth\ClientCredentials\Response $response) : Api\OAuth\Token
	{
		$token = $this->token;
		$token->setType($response->tokenType());
		$token->setAccessToken($response->accessToken());
		$token->setExpires(
			(new Main\Type\DateTime())
				->add(sprintf('PT%sS', $response->expiresIn()))
		);

		if ($response instanceof Response)
		{
			$token->setRefreshToken($response->refreshToken());
		}

		return $token;
	}

	protected function saveToken(Api\OAuth\Token $token) : Api\OAuth\Token
	{
		$saveResult = $token->save();

		if (!$saveResult->isSuccess())
		{
			throw new Main\SystemException(implode(PHP_EOL, $saveResult->getErrorMessages()));
		}

		return $token;
	}
}