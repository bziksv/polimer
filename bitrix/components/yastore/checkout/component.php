<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponent $this */

use Bitrix\Main\Loader;

if (!Loader::includeModule('yandex.market')) {
    return;
}

$api = new \Yandex\Market\Checkout\Api();
$api->handleRequest();

$this->IncludeComponentTemplate();
