<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

/** @var \CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

if ($this->GetPageName() === 'exception') { return; }

if (!isset($templateFolder)) { $templateFolder = $this->GetFolder(); }

$arResult['EDITOR'] = [
	'ENTITY_FIELDS' => [],
	'ENTITY_DATA' => [],
];
$arResult['JS_MESSAGES'] = [];

include __DIR__ . '/modifier/columns.php';
include __DIR__ . '/modifier/attention.php';
include __DIR__ . '/modifier/properties.php';
include __DIR__ . '/modifier/basket.php';

$arResult['EDITOR']['ENTITY_CONFIG'] = array_values($arResult['COLUMNS']);
