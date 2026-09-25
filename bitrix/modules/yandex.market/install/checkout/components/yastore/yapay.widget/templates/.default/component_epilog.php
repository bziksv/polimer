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

\CJSCore::RegisterExt('ya_pay_widget', [
    'js' => $path . '/ya_pay_widget.js',
]);

CUtil::InitJSCore(['ya_pay_sdk', 'ya_pay_widget']);
