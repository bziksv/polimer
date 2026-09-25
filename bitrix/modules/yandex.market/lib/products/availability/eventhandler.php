<?php
namespace Yandex\Market\Products\Availability;

use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Yandex\Market\Products\Availability\Config as AvailabilityConfig;
use Yandex\Market\Products\Prices\Config;

class EventHandler
{
	const MODULE_ID = 'yandex.market';

	public static function onProductChange($productId, $fields = null)
	{
		if (!Config::isEnabled()) { return; }

		list($productId, $fields) = self::normalizeProductEventArguments($productId, $fields);
		$productId = self::resolveProductId($productId, $fields);

		if ($productId === null || !self::isTrackedProduct($productId)) { return; }

		Repository::enqueue($productId);
	}

	public static function onStoreProductChange($amountId, $fields = null)
	{
		if (!Config::isEnabled() || AvailabilityConfig::useGeneralStock()) { return; }

		list($amountId, $fields) = self::normalizeStoreEventArguments($amountId, $fields);
		$productId = self::resolveStoreProductId($amountId, $fields);

		if ($productId === null || !self::isTrackedProduct($productId)) { return; }

		Repository::enqueue($productId);
	}

	protected static function normalizeProductEventArguments($productId, $fields = null)
	{
		if ($productId instanceof Main\Event)
		{
			$event = $productId;

			return [
				$event->getParameter('id'),
				$event->getParameter('fields'),
			];
		}

		return [ $productId, $fields ];
	}

	protected static function normalizeStoreEventArguments($amountId, $fields = null)
	{
		if ($amountId instanceof Main\Event)
		{
			$event = $amountId;

			return [
				$event->getParameter('id'),
				$event->getParameter('fields'),
			];
		}

		return [ $amountId, $fields ];
	}

	protected static function resolveProductId($productId, $fields = null)
	{
		if (is_array($fields) && isset($fields['ID']))
		{
			return (int)$fields['ID'];
		}

		if (is_array($fields) && isset($fields['PRODUCT_ID']))
		{
			return (int)$fields['PRODUCT_ID'];
		}

		$productId = (int)$productId;

		return $productId > 0 ? $productId : null;
	}

	protected static function resolveStoreProductId($amountId, $fields = null)
	{
		if (is_array($fields) && isset($fields['PRODUCT_ID']))
		{
			return (int)$fields['PRODUCT_ID'];
		}

		$amountId = (int)$amountId;

		if ($amountId <= 0 || !Loader::includeModule('catalog')) { return null; }

		$query = \CCatalogStoreProduct::GetList(
			[],
			[ '=ID' => $amountId ],
			false,
			false,
			[ 'PRODUCT_ID' ]
		);

		if ($row = $query->Fetch())
		{
			return (int)$row['PRODUCT_ID'];
		}

		return null;
	}

	protected static function isTrackedProduct($productId)
	{
		$productId = (int)$productId;

		if ($productId <= 0 || !Loader::includeModule('iblock')) { return false; }

		$iblockIds = self::getTrackedIblockIds();

		if (empty($iblockIds)) { return true; }

		$row = Iblock\ElementTable::getList([
			'filter' => [ '=ID' => $productId ],
			'select' => [ 'IBLOCK_ID' ],
			'limit' => 1,
		])->fetch();

		return $row && in_array((int)$row['IBLOCK_ID'], $iblockIds, true);
	}

	protected static function getTrackedIblockIds()
	{
		$ids = [];
		$productIblockId = (int)Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_IBLOCK_ID', 0);
		$useSku = Option::get(self::MODULE_ID, 'USE_SKU', 'N') === 'Y';
		$skuIblockId = (int)Option::get(self::MODULE_ID, 'SKU_IBLOCK_ID', 0);

		if ($productIblockId > 0)
		{
			$ids[] = $productIblockId;
		}

		if ($useSku && $skuIblockId > 0)
		{
			$ids[] = $skuIblockId;
		}

		return $ids;
	}

	public static function registerEvents()
	{
		if (!Loader::includeModule('catalog')) { return; }

		$moduleId = self::MODULE_ID;
		$class = '\\' . __CLASS__;

		if (class_exists(Catalog\Model\Product::class))
		{
			RegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Product::OnAfterAdd', $moduleId, $class, 'onProductChange');
			RegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Product::OnAfterUpdate', $moduleId, $class, 'onProductChange');
		}
		else
		{
			RegisterModuleDependences('catalog', 'OnProductAdd', $moduleId, $class, 'onProductChange');
			RegisterModuleDependences('catalog', 'OnProductUpdate', $moduleId, $class, 'onProductChange');
		}

		RegisterModuleDependences('catalog', 'OnStoreProductAdd', $moduleId, $class, 'onStoreProductChange');
		RegisterModuleDependences('catalog', 'OnStoreProductUpdate', $moduleId, $class, 'onStoreProductChange');
		RegisterModuleDependences('catalog', 'OnBeforeStoreProductDelete', $moduleId, $class, 'onStoreProductChange');
	}

	public static function unregisterEvents()
	{
		$moduleId = self::MODULE_ID;
		$class = '\\' . __CLASS__;

		UnRegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Product::OnAfterAdd', $moduleId, $class, 'onProductChange');
		UnRegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Product::OnAfterUpdate', $moduleId, $class, 'onProductChange');
		UnRegisterModuleDependences('catalog', 'OnProductAdd', $moduleId, $class, 'onProductChange');
		UnRegisterModuleDependences('catalog', 'OnProductUpdate', $moduleId, $class, 'onProductChange');
		UnRegisterModuleDependences('catalog', 'OnStoreProductAdd', $moduleId, $class, 'onStoreProductChange');
		UnRegisterModuleDependences('catalog', 'OnStoreProductUpdate', $moduleId, $class, 'onStoreProductChange');
		UnRegisterModuleDependences('catalog', 'OnBeforeStoreProductDelete', $moduleId, $class, 'onStoreProductChange');
	}
}
