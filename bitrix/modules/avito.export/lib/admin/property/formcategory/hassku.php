<?php
namespace Avito\Export\Admin\Property\FormCategory;

use Bitrix\Main;
use Avito\Export\Utils;

trait HasSku
{
	protected function propertyIblockId(array $property) : int
	{
		$iblockId = (int)($property['IBLOCK_ID'] ?? 0);

		if ($iblockId <= 0 || !Main\Loader::includeModule('catalog')) { return 0; }

		$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);
		$productIblockId = (int)($catalog['PRODUCT_IBLOCK_ID'] ?? 0);

		return $productIblockId !== $iblockId ? $productIblockId : 0;
	}

	protected function skuPropertyId(array $property) : int
	{
		$iblockId = (int)($property['IBLOCK_ID'] ?? 0);

		if ($iblockId <= 0 || !Main\Loader::includeModule('catalog')) { return 0; }

		$catalog = \CCatalogSku::GetInfoByIBlock($iblockId);

		return (int)($catalog['SKU_PROPERTY_ID'] ?? 0);
	}

	protected function mapOffersSku(array $offerIds) : array
	{
		if (
			empty($offerIds)
			|| !Main\Loader::includeModule('iblock')
			|| !Main\Loader::includeModule('catalog')
		)
		{
			return [];
		}

		$products = \CCatalogSKU::getProductList($offerIds);

		return Utils\ArrayHelper::column($products, 'ID');
	}

	public function makeOfferValues(array $productValues, array $offerToSkuMap) : array
	{
		$result = [];

		foreach ($offerToSkuMap as $offerId => $skuId)
		{
			if (isset($productValues[$skuId]))
			{
				$result[$offerId] = $productValues[$skuId];
			}
		}

		return $result;
	}
}