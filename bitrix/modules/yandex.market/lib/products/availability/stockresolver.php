<?php
namespace Yandex\Market\Products\Availability;

use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Loader;
use Yandex\Market\Checkout\ProductIdResolver;

class StockResolver
{
	public static function isAvailable($productId)
	{
		return self::getAvailableQuantity($productId) > 0;
	}

	public static function getAvailableQuantity($productId)
	{
		$productId = (int)$productId;

		if ($productId <= 0 || !Loader::includeModule('catalog')) { return 0.0; }

		if (Config::useGeneralStock())
		{
			return self::getProductQuantity($productId);
		}

		return self::getWarehouseQuantity($productId);
	}

	public static function resolveOfferId($productId)
	{
		$offerId = ProductIdResolver::getExternalId($productId, false);
		$offerId = trim((string)$offerId);

		if ($offerId === '' || mb_strlen($offerId) > 50) { return null; }

		return $offerId;
	}

	public static function buildHiddenOffer($offerId, $feedId)
	{
		$feedId = (int)$feedId;
		$offerId = trim((string)$offerId);

		if ($feedId <= 0 || $offerId === '') { return null; }

		return [
			'feedId' => $feedId,
			'offerId' => $offerId,
		];
	}

	protected static function getProductQuantity($productId)
	{
		$row = ProductTable::getList([
			'filter' => [ '=ID' => $productId ],
			'select' => [ 'QUANTITY' ],
			'limit' => 1,
		])->fetch();

		return $row ? (float)$row['QUANTITY'] : 0.0;
	}

	protected static function getWarehouseQuantity($productId)
	{
		$configuredStoreIds = Config::getStoreIds();
		$filter = [ '=PRODUCT_ID' => $productId ];

		if (!empty($configuredStoreIds))
		{
			$filter['@STORE_ID'] = $configuredStoreIds;
		}

		$storeProducts = StoreProductTable::getList([
			'filter' => $filter,
			'select' => [ 'STORE_ID', 'AMOUNT' ],
		]);

		$total = 0.0;
		$allowedStoreIds = !empty($configuredStoreIds)
			? array_fill_keys($configuredStoreIds, true)
			: null;

		while ($row = $storeProducts->fetch())
		{
			$storeId = (int)$row['STORE_ID'];

			if ($allowedStoreIds !== null && !isset($allowedStoreIds[$storeId]))
			{
				continue;
			}

			$store = StoreTable::getById($storeId)->fetch();

			if (!$store || $store['ACTIVE'] !== 'Y') { continue; }

			$total += (float)$row['AMOUNT'];
		}

		return $total;
	}
}
