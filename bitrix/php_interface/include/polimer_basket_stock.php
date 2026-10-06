<?php

/**
 * Остаток каталога (b_catalog_product.QUANTITY) vs корзина.
 * QUANTITY_TRACE на сайте выключен — штатный Bitrix количество не режет.
 */

function polimerGetCatalogStock($productId)
{
	$productId = (int)$productId;
	if ($productId <= 0 || !CModule::IncludeModule('catalog'))
	{
		return 0.0;
	}

	$row = CCatalogProduct::GetByID($productId);
	if (empty($row))
	{
		return 0.0;
	}

	return max(0.0, (float)$row['QUANTITY']);
}

function polimerGetBasketProductQuantity($productId, $exceptBasketItemId = 0)
{
	$productId = (int)$productId;
	$exceptBasketItemId = (int)$exceptBasketItemId;
	if ($productId <= 0 || !CModule::IncludeModule('sale'))
	{
		return 0.0;
	}

	if (!class_exists('\Bitrix\Sale\Basket') || !class_exists('\Bitrix\Sale\Fuser'))
	{
		return 0.0;
	}

	$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), SITE_ID);
	$sum = 0.0;
	foreach ($basket as $item)
	{
		if ($item->isDelay())
		{
			continue;
		}
		if ((int)$item->getProductId() !== $productId)
		{
			continue;
		}
		if ($exceptBasketItemId > 0 && (int)$item->getId() === $exceptBasketItemId)
		{
			continue;
		}
		$sum += (float)$item->getQuantity();
	}

	return $sum;
}

function polimerStockShortageMessage($name, $stock)
{
	$name = trim((string)$name);
	$stockLabel = (int)$stock === (float)$stock ? (string)(int)$stock : (string)$stock;
	$prefix = $name !== '' ? '«'.$name.'»: ' : '';

	return $prefix.'запрашиваемое количество больше остатка. На складе: '.$stockLabel;
}

function polimerFormatStockQty($qty)
{
	$qty = (float)$qty;
	return (int)$qty === $qty ? (string)(int)$qty : rtrim(rtrim(sprintf('%.4F', $qty), '0'), '.');
}

function polimerRememberStockClamp(array $changes)
{
	if (empty($changes))
	{
		return;
	}

	$lines = [];
	foreach ($changes as $change)
	{
		$name = trim((string)($change['NAME'] ?? ''));
		$label = $name !== '' ? '«'.$name.'»' : 'Товар';
		if (($change['TYPE'] ?? '') === 'delay')
		{
			$lines[] = $label.': нет в наличии, не попадёт в заказ.';
			continue;
		}

		$to = polimerFormatStockQty($change['TO'] ?? 0);
		$lines[] = $label.': количество уменьшено до остатка ('.$to.' шт).';
	}

	$_SESSION['POLIMER_STOCK_CLAMP'] = array_values(array_unique(array_merge(
		$_SESSION['POLIMER_STOCK_CLAMP'] ?? [],
		$lines
	)));
}

function polimerTakeStockClampMessages()
{
	$messages = $_SESSION['POLIMER_STOCK_CLAMP'] ?? [];
	unset($_SESSION['POLIMER_STOCK_CLAMP']);

	return is_array($messages) ? $messages : [];
}

function polimerClampFuserBasketToStock()
{
	$changes = [];
	if (!CModule::IncludeModule('sale') || !class_exists('\Bitrix\Sale\Basket'))
	{
		return $changes;
	}

	$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), SITE_ID);
	foreach ($basket as $item)
	{
		if ($item->isDelay())
		{
			continue;
		}

		$basketId = (int)$item->getId();
		if ($basketId <= 0)
		{
			continue;
		}

		$productId = (int)$item->getProductId();
		$stock = polimerGetCatalogStock($productId);
		$qty = (float)$item->getQuantity();
		$name = (string)$item->getField('NAME');

		if ($stock < 1)
		{
			if (\CSaleBasket::Update($basketId, ['DELAY' => 'Y']))
			{
				$changes[] = ['TYPE' => 'delay', 'NAME' => $name, 'TO' => 0];
			}
			continue;
		}

		if ($qty > $stock + 0.0001)
		{
			if (\CSaleBasket::Update($basketId, ['QUANTITY' => $stock]))
			{
				$changes[] = [
					'TYPE' => 'clamp',
					'NAME' => $name,
					'FROM' => $qty,
					'TO' => $stock,
				];
			}
		}
	}

	return $changes;
}
