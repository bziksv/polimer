<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class EventHandler
{
	const MODULE_ID = 'yandex.market';

	public static function onPriceChange($priceId, $fields = null)
	{
		if (!Config::isEnabled()) { return; }

		list($priceId, $fields) = self::normalizePriceEventArguments($priceId, $fields);
		$productId = self::resolveProductId($priceId, $fields);

		if ($productId === null || !self::isTrackedProduct($productId)) { return; }

		Repository::enqueue($productId);
	}

	public static function onBeforePriceDelete($priceId, $fields = null)
	{
		if (!Config::isEnabled()) { return; }

		list($priceId, $fields) = self::normalizePriceEventArguments($priceId, $fields);
		$productId = self::resolveProductId($priceId, $fields);

		if ($productId === null || !self::isTrackedProduct($productId)) { return; }

		Repository::enqueue($productId);
	}

	protected static function normalizePriceEventArguments($priceId, $fields = null)
	{
		if ($priceId instanceof Main\Event)
		{
			$event = $priceId;

			return [
				$event->getParameter('id'),
				$event->getParameter('fields'),
			];
		}

		return [ $priceId, $fields ];
	}

	protected static function resolveProductId($priceId, $fields = null)
	{
		if (is_array($fields) && isset($fields['PRODUCT_ID']))
		{
			return (int)$fields['PRODUCT_ID'];
		}

		$priceId = (int)$priceId;

		if ($priceId <= 0 || !Loader::includeModule('catalog')) { return null; }

		$query = \CPrice::GetList(
			[],
			[ '=ID' => $priceId ],
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

		if (class_exists(Catalog\Model\Price::class))
		{
			RegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnAfterAdd', $moduleId, $class, 'onPriceChange');
			RegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnAfterUpdate', $moduleId, $class, 'onPriceChange');
			RegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnDelete', $moduleId, $class, 'onBeforePriceDelete');
		}
		else
		{
			RegisterModuleDependences('catalog', 'OnPriceAdd', $moduleId, $class, 'onPriceChange');
			RegisterModuleDependences('catalog', 'OnPriceUpdate', $moduleId, $class, 'onPriceChange');
			RegisterModuleDependences('catalog', 'OnBeforePriceDelete', $moduleId, $class, 'onBeforePriceDelete');
		}
	}

	public static function unregisterEvents()
	{
		$moduleId = self::MODULE_ID;
		$class = '\\' . __CLASS__;

		UnRegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnAfterAdd', $moduleId, $class, 'onPriceChange');
		UnRegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnAfterUpdate', $moduleId, $class, 'onPriceChange');
		UnRegisterModuleDependences('catalog', 'Bitrix\\Catalog\\Model\\Price::OnDelete', $moduleId, $class, 'onBeforePriceDelete');
		UnRegisterModuleDependences('catalog', 'OnPriceAdd', $moduleId, $class, 'onPriceChange');
		UnRegisterModuleDependences('catalog', 'OnPriceUpdate', $moduleId, $class, 'onPriceChange');
		UnRegisterModuleDependences('catalog', 'OnBeforePriceDelete', $moduleId, $class, 'onBeforePriceDelete');
	}
}
