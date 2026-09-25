<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}

if (!\Bitrix\Main\Loader::includeModule('yandex.market') &&
    \Bitrix\Main\Config\Option::get('yandex.market', 'SHOW_BUTTON', 'N') !== 'Y'
) {
    return;
}

$path = $templateFolder;
\CJSCore::RegisterExt('ya_pay_badge', [
    'js' => $path . '/ya_pay_badge.js',
    'css' => $path . '/ya_pay_badge.css',
]);

CUtil::InitJSCore(['ya_pay_sdk', 'ya_pay_badge']);
