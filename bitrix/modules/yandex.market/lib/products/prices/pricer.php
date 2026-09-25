<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Main\Loader;
use Yandex\Market\Checkout\ProductIdResolver;

class Pricer
{
	public static function getPriceData($productId)
	{
		$productId = (int)$productId;

		if ($productId <= 0 || !Loader::includeModule('catalog')) { return null; }

		$priceTypeId = Config::getPriceTypeId();
		if ($priceTypeId !== null && !self::hasCatalogPrice($productId, $priceTypeId))
		{
			return null;
		}

		$optimal = self::resolveOptimalPrice($productId);
		if ($optimal === null) { return null; }

		return [
			'PRODUCT_ID' => $productId,
			'OFFER_ID' => self::resolveOfferId($productId),
			'PRICE' => $optimal['price'],
			'BASE_PRICE' => $optimal['base_price'],
			'CURRENCY' => $optimal['currency'],
		];
	}

	protected static function hasCatalogPrice($productId, $priceTypeId)
	{
		$query = \CPrice::GetList(
			[],
			[
				'PRODUCT_ID' => $productId,
				'CATALOG_GROUP_ID' => $priceTypeId,
			],
			false,
			[ 'nTopCount' => 1 ],
			[ 'ID' ]
		);

		return (bool)$query->Fetch();
	}

	protected static function resolveOptimalPrice($productId)
	{
		$optimal = \CCatalogProduct::GetOptimalPrice(
			$productId,
			1,
			[],
			'N',
			[],
			SITE_ID ?: 's1',
			[]
		);

		if (!is_array($optimal) || empty($optimal['RESULT_PRICE'])) { return null; }

		$resultPrice = $optimal['RESULT_PRICE'];
		$discountPrice = isset($resultPrice['DISCOUNT_PRICE']) ? (float)$resultPrice['DISCOUNT_PRICE'] : null;
		$basePrice = isset($resultPrice['BASE_PRICE']) ? (float)$resultPrice['BASE_PRICE'] : null;

		if ($discountPrice === null || $discountPrice <= 0) { return null; }

		$price = round($discountPrice, 2);
		$base = null;

		if ($basePrice !== null && $basePrice > $discountPrice)
		{
			$base = round($basePrice, 2);
		}

		return [
			'price' => $price,
			'base_price' => $base,
			'currency' => (string)($resultPrice['CURRENCY'] ?? 'RUB'),
		];
	}

	protected static function resolveOfferId($productId)
	{
		$offerId = ProductIdResolver::getExternalId($productId, false);
		$offerId = trim((string)$offerId);

		if ($offerId === '' || mb_strlen($offerId) > 80) { return null; }

		return $offerId;
	}

	public static function buildOffer(array $priceData, $feedId)
	{
		$feedId = (int)$feedId;
		$offerId = isset($priceData['OFFER_ID']) ? (string)$priceData['OFFER_ID'] : '';

		if ($feedId <= 0 || $offerId === '') { return null; }

		$priceValue = round((float)$priceData['PRICE']);
		$offer = [
			'feed' => [ 'id' => $feedId ],
			'id' => $offerId,
			'price' => [
				'currencyId' => 'RUR',
				'value' => $priceValue,
			],
		];

		if (!empty($priceData['BASE_PRICE']) && $priceData['BASE_PRICE'] > $priceData['PRICE'])
		{
			$basePrice = round((float)$priceData['BASE_PRICE']);
			$discount = $basePrice - $priceValue;
			$discountPercent = 100 * floor($discount) / ceil($basePrice);

			if ($discount >= 1 && $discountPercent >= 5 && $discountPercent <= 95)
			{
				$offer['price']['discountBase'] = $basePrice;
			}
		}

		return $offer;
	}
}
