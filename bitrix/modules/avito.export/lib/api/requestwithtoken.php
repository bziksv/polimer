<?php
namespace Avito\Export\Api;

use Avito\Export\Assert;
use Bitrix\Main;

abstract class RequestWithToken extends Request
{
	/** @var OAuth\Token */
	protected $token;

	public function token(OAuth\Token $token) : void
	{
		$this->token = $token;
	}

	protected function buildTransport() : Main\Web\HttpClient
	{
		$result = parent::buildTransport();
		$result->setHeader('Authorization', $this->authorizationHeader());

		return $result;
	}

	protected function authorizationHeader() : string
	{
		Assert::notNull($this->token, 'token');

		$this->checkToken();

		return $this->token->getType() . ' ' . $this->token->getAccessToken();
	}

	protected function checkToken() : void
	{
		if (!$this->token->isExpired()) { return; }

		$controller = new OAuth\RefreshToken\Controller($this->token);

		$this->token = $controller->run();
	}
}