<?php
namespace Yandex\Market\Products\Availability;

use Bitrix\Main\Config\Option;
use Yandex\Market\Utils\PhpSerializer;

class Config
{
	const MODULE_ID = 'yandex.market';

	const STOCK_MODE_GENERAL = 'general';
	const STOCK_MODE_WAREHOUSE = 'warehouse';

	public static function getStockMode()
	{
		$mode = (string)Option::get(self::MODULE_ID, 'PRODUCTS_AVAILABILITY_STOCK_MODE', self::STOCK_MODE_GENERAL);

		return $mode === self::STOCK_MODE_WAREHOUSE
			? self::STOCK_MODE_WAREHOUSE
			: self::STOCK_MODE_GENERAL;
	}

	public static function useGeneralStock()
	{
		return self::getStockMode() === self::STOCK_MODE_GENERAL;
	}

	public static function useWarehouseStock()
	{
		return self::getStockMode() === self::STOCK_MODE_WAREHOUSE;
	}

	/** @return int[] */
	public static function getStoreIds()
	{
		$raw = (string)Option::get(self::MODULE_ID, 'PRODUCTS_AVAILABILITY_STORE_IDS', '');

		if ($raw === '') { return []; }

		$decoded = PhpSerializer::decode($raw);

		if (!is_array($decoded)) { return []; }

		$ids = [];

		foreach ($decoded as $storeId)
		{
			$storeId = (int)$storeId;

			if ($storeId > 0)
			{
				$ids[] = $storeId;
			}
		}

		return array_values(array_unique($ids));
	}
}
