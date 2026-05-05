<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $this \CBitrixComponentTemplate */

$arResult['COLUMNS']['COMMON']['elements'][] = [
	'name' => 'COMMON',
	'title' => Loc::getMessage('AVITO_EXPORT_BASKET_TITLE'),
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
		'enableToggling' => false,
	],
	'elements' => [
		[ 'name' => 'BASKET' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'BASKET',
	'title' => Loc::getMessage('AVITO_EXPORT_BASKET_TITLE'),
	'type' => 'avito-export-basket',
	'editable' => false,
	'isDragEnabled' => false,
	'enabledMenu' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['BASKET'] = [
	'COLUMNS' => [
		'NUMBER' => Loc::getMessage('AVITO_EXPORT_BASKET_NUMBER'),
		'NAME' => Loc::getMessage('AVITO_EXPORT_BASKET_NAME'),
		'PRICE' => Loc::getMessage('AVITO_EXPORT_BASKET_PRICE'),
		'QUANTITY' => Loc::getMessage('AVITO_EXPORT_BASKET_QUANTITY'),
	],
	'ROWS' => $arResult['BASKET_ROWS'],
	'SUMMARY' => $arResult['BASKET_TOTAL'],
	'ACTIVITIES' => array_values(array_intersect_key($arResult['ACTIVITIES'], [
		'setMarkings' => true,
	])),
];


$arResult['JS_MESSAGES']['Basket'] = [
	'COMMISSION' => Loc::getMessage('AVITO_EXPORT_BASKET_COMMISSION'),
	'DISCOUNT' => Loc::getMessage('AVITO_EXPORT_BASKET_DISCOUNT'),
	'QUANTITY_UNIT' => Loc::getMessage('AVITO_EXPORT_BASKET_QUANTITY_UNIT'),
];