<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

$arResult['SPECIAL_FIELDS'] = [];
$arResult['SPECIAL_FIELDS_SHOWN'] = [];

include __DIR__ . '/modifier/tab-request.php';
include __DIR__ . '/modifier/iblock-data.php';
include __DIR__ . '/modifier/special-refresh-period.php';
include __DIR__ . '/modifier/special-field-finalize.php';
