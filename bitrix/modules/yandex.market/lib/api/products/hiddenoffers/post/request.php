<?php
namespace Yandex\Market\Api\Products\HiddenOffers\Post;

use Bitrix\Main;
use Yandex\Market\Api\Products\Reference;

class Request extends Reference\Request
{
	protected $hiddenOffers = [];

	public function getPath()
	{
		return '/products/api/ext/partner/hidden-offers';
	}

	public function getMethod()
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

	public function getQueryFormat()
	{
		return static::DATA_TYPE_JSON;
	}

	public function getQuery()
	{
		return [
			'hiddenOffers' => $this->getHiddenOffers(),
		];
	}

	public function buildResponse($data)
	{
		return new \Yandex\Market\Api\Products\HiddenOffers\Response($data);
	}

	public function getHiddenOffers()
	{
		return $this->hiddenOffers;
	}

	public function setHiddenOffers(array $hiddenOffers)
	{
		$this->hiddenOffers = $hiddenOffers;

		return $this;
	}
}
