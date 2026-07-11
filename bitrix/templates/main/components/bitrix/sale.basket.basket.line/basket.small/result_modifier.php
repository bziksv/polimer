<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Only orderable items (READY) — exclude soft-deleted DELAY positions from header badge.
$totalQuantity = 0;

if (!empty($arResult['CATEGORIES']['READY']) && is_array($arResult['CATEGORIES']['READY'])) {
    foreach ($arResult['CATEGORIES']['READY'] as $item) {
        $totalQuantity += (float)($item['QUANTITY'] ?? 0);
    }
} elseif (isset($arResult['NUM_PRODUCTS'])) {
    $totalQuantity = (int)$arResult['NUM_PRODUCTS'];
}

$arResult['TOTAL_QUANTITY'] = $totalQuantity;
