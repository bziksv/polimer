<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$totalQuantity = 0;
foreach ($arResult['CATEGORIES'] as $items) {
    foreach ($items as $item) {
        $totalQuantity += $item['QUANTITY'];
    }
}
$arResult['TOTAL_QUANTITY'] = $totalQuantity;
