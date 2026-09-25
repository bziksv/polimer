<?php
namespace Yandex\Market\Api\Products\Reference;

use Bitrix\Main;
use Yandex\Market\Api;
use Yandex\Market\Psr\Log\LoggerInterface;

abstract class Request extends Api\Reference\Request
{
	/** @var OAuth */
	protected $auth;

	public function __construct($accessToken = null, LoggerInterface $logger = null)
	{
		parent::__construct($logger);
		$this->auth = new OAuth((string)$accessToken);
	}

	public function getHost()
	{
		return Api\Products\Glossary::API_HOST;
	}

	public function setAccessToken($accessToken)
	{
		$this->auth = new OAuth((string)$accessToken);
	}

	protected function buildClient()
	{
		$result = parent::buildClient();
		$result->setHeader(...$this->auth->getHeader());

		return $result;
	}
}
