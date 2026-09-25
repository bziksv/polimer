<?php
namespace Yandex\Market\Api\Products\OfferPrices\Updates;

use Bitrix\Main;
use Yandex\Market\Api\Products\Reference;

class Request extends Reference\Request
{
	protected $offers = [];

	public function getPath()
	{
		return '/products/api/ext/partner/offer-prices/updates';
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
			'offers' => $this->getOffers(),
		];
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	public function getOffers()
	{
		return $this->offers;
	}

	public function setOffers(array $offers)
	{
		$this->offers = $offers;

		return $this;
	}
}
