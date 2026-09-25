<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Main\Config\Option;
use Yandex\Market\Api\Products\Glossary;

class Config
{
	const MODULE_ID = 'yandex.market';

	public static function isEnabled()
	{
		return Option::get(self::MODULE_ID, 'PRODUCTS_PRICES_ENABLED', 'N') === 'Y';
	}

	public static function getOAuthToken()
	{
		return trim((string)Option::get(self::MODULE_ID, 'PRODUCTS_PRICES_OAUTH_TOKEN', ''));
	}

	public static function getFeedId()
	{
		return (int)Option::get(self::MODULE_ID, 'PRODUCTS_PRICES_FEED_ID', 0);
	}

	public static function getIntervalMinutes()
	{
		$minutes = (int)Option::get(self::MODULE_ID, 'PRODUCTS_PRICES_INTERVAL', 5);

		return max(1, min(1440, $minutes));
	}

	public static function getIntervalSeconds()
	{
		return self::getIntervalMinutes() * 60;
	}

	public static function getPriceTypeId()
	{
		$priceTypeId = (int)Option::get(self::MODULE_ID, 'PRODUCTS_PRICES_PRICE_TYPE', 0);

		return $priceTypeId > 0 ? $priceTypeId : null;
	}

	public static function isConfigured()
	{
		return (
			self::isEnabled()
			&& self::getOAuthToken() !== ''
			&& self::getFeedId() > 0
		);
	}

	public static function getOAuthAuthorizeUrl()
	{
		return Glossary::OAUTH_AUTHORIZE_URL
			. '?response_type=token'
			. '&client_id=' . rawurlencode(Glossary::OAUTH_CLIENT_ID);
	}
}
