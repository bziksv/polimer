<?php
/**
 * Фильтр выгрузки Яндекс по складам: сумма остатков по выбранным складам >= мин. кол-ва.
 * Используется в catalog_export/yandex_setup.php и yandex_run.php.
 */

use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\StoreProductTable;

/**
 * @return array<int, array{ID:int,TITLE:string}>
 */
function polimerYandexGetActiveStores(): array
{
	$result = [];
	$rs = StoreTable::getList([
		'filter' => ['=ACTIVE' => 'Y'],
		'select' => ['ID', 'TITLE'],
		'order' => ['TITLE' => 'ASC', 'ID' => 'ASC'],
	]);
	while ($row = $rs->fetch())
	{
		$id = (int)$row['ID'];
		$result[$id] = [
			'ID' => $id,
			'TITLE' => (string)$row['TITLE'],
		];
	}

	return $result;
}

/**
 * Нормализует список ID складов из настроек профиля.
 *
 * @param mixed $storeIds
 * @return int[]
 */
function polimerYandexNormalizeStoreIds($storeIds): array
{
	if (!is_array($storeIds))
	{
		return [];
	}

	$out = [];
	foreach ($storeIds as $id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			$out[$id] = $id;
		}
	}

	return array_values($out);
}

/**
 * Сумма остатков product_id по выбранным складам.
 *
 * @param int[] $productIds
 * @param int[] $storeIds
 * @return array<int, float> productId => sum amount
 */
function polimerYandexGetStoreAmountSums(array $productIds, array $storeIds): array
{
	$productIds = array_values(array_unique(array_map('intval', $productIds)));
	$storeIds = polimerYandexNormalizeStoreIds($storeIds);
	$sums = [];
	foreach ($productIds as $pid)
	{
		if ($pid > 0)
		{
			$sums[$pid] = 0.0;
		}
	}

	if (empty($sums) || empty($storeIds))
	{
		return $sums;
	}

	foreach (array_chunk(array_keys($sums), 500) as $chunk)
	{
		$rs = StoreProductTable::getList([
			'filter' => [
				'@PRODUCT_ID' => $chunk,
				'@STORE_ID' => $storeIds,
			],
			'select' => ['PRODUCT_ID', 'AMOUNT'],
		]);
		while ($row = $rs->fetch())
		{
			$pid = (int)$row['PRODUCT_ID'];
			if (isset($sums[$pid]))
			{
				$sums[$pid] += (float)$row['AMOUNT'];
			}
		}
	}

	return $sums;
}

/**
 * Проходит ли товар/ТП фильтр: сумма по складам >= min.
 */
function polimerYandexPassStoreFilter(int $productId, array $amountSums, float $minQuantity): bool
{
	$amount = $amountSums[$productId] ?? 0.0;

	return $amount >= $minQuantity;
}

/**
 * Остаток для фида: целая сумма по выбранным складам (или null, если склады не заданы).
 *
 * @param array<int, float> $amountSums
 */
function polimerYandexGetOfferQuantity(int $productId, array $amountSums, bool $hasStoreAmounts): ?int
{
	if (!$hasStoreAmounts || $productId <= 0)
	{
		return null;
	}

	$qty = (int)floor((float)($amountSums[$productId] ?? 0.0));

	return $qty < 0 ? 0 : $qty;
}

/**
 * Теги остатков для Яндекс Товары: <available> + <quantity>.
 * quantity — только если в профиле выбраны склады (сумма AMOUNT по ним).
 *
 * @param array<int, float> $amountSums
 */
function polimerYandexFormatStockXmlTags(int $productId, string $availableFlag, array $amountSums, bool $hasStoreAmounts): string
{
	$available = ($availableFlag === 'Y') ? 'true' : 'false';
	$qty = polimerYandexGetOfferQuantity($productId, $amountSums, $hasStoreAmounts);

	// Если остаток по складам 0 — явно нет в наличии (даже если CATALOG.AVAILABLE = Y).
	if ($qty !== null && $qty <= 0)
	{
		$available = 'false';
	}

	$xml = '<available>'.$available."</available>\n";
	if ($qty !== null)
	{
		$xml .= '<quantity>'.$qty."</quantity>\n";
	}

	return $xml;
}
