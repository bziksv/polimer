<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult['ITEMS'])) {
    return;
}

foreach ($arResult['ITEMS'] as &$item) {
    $item['VIEWED_PRODUCT_CODE'] = '';

    if (!empty($item['PROPERTIES']['CML2_TRAITS']['VALUE'][2])) {
        $item['VIEWED_PRODUCT_CODE'] = $item['PROPERTIES']['CML2_TRAITS']['VALUE'][2];
    } else {
        $rsProp = CIBlockElement::GetProperty(
            (int)$item['IBLOCK_ID'],
            (int)$item['ID'],
            ['sort' => 'asc'],
            ['CODE' => 'CML2_TRAITS']
        );

        $values = [];
        while ($arProp = $rsProp->Fetch()) {
            $values[] = $arProp['VALUE'];
        }

        if (!empty($values[2])) {
            $item['VIEWED_PRODUCT_CODE'] = $values[2];
        }
    }

    $item['VIEWED_IN_STOCK'] = checkProduct($item['ID']);
}
unset($item);
