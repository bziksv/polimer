<?php

foreach ($arResult["STORES"] as $pid => &$arProperty) {
    if ($storeIds = $arProperty['USER_FIELDS']['UF_STORE']['VALUE']) {
        $rsProps = CCatalogStore::GetList(
            array('SORT' => 'ASC', 'ID' => 'ASC'),
            ["ACTIVE" => "Y", "PRODUCT_ID" => $arParams["ELEMENT_ID"], "+SITE_ID" => SITE_ID, "ISSUING_CENTER" => 'Y', "ID" => $storeIds],
            false,
            false,
            ['ID', 'TITLE', 'PRODUCT_AMOUNT']
        );
        while ($prop = $rsProps->GetNext())
        {
            $arProperty['AMOUNT'] += $prop['PRODUCT_AMOUNT'];
        }
    }

    // Имя склада и адрес раздельно (для подсветки адреса)
    $title = trim((string)($arProperty['TITLE'] ?? ''));
    $address = trim((string)($arProperty['ADDRESS'] ?? ''));
    $name = $title;

    if ($address !== '' && $title !== '')
    {
        $suffix = ' ('.$address.')';
        if (mb_substr($title, -mb_strlen($suffix)) === $suffix)
        {
            $name = trim(mb_substr($title, 0, -mb_strlen($suffix)));
        }
        elseif (preg_match('/^(.+?)\s*\('.preg_quote($address, '/').'\)\s*$/u', $title, $m))
        {
            $name = trim($m[1]);
        }
    }
    elseif ($address === '' && preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $title, $m))
    {
        $name = trim($m[1]);
        $address = trim($m[2]);
    }

    $arProperty['STORE_NAME'] = $name !== '' ? $name : $title;
    $arProperty['STORE_ADDRESS'] = $address;
}
unset($arProperty);
