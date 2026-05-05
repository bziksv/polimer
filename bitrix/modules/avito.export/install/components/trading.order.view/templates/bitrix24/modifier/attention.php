<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\UI\Extension;

Extension::load('avitoexport.trading.activity');

$values = [
	'MESSAGES' => [],
	'ACTIVITIES' => [],
];

if (!empty($arResult['ATTENTION']))
{
	$values['MESSAGES'][] = [
		'type' => 'info',
		'text' => $arResult['ATTENTION'],
	];
}

if (!empty($arResult['ACTIVITIES']))
{
	$values['ACTIVITIES'] = array_values(array_diff_key($arResult['ACTIVITIES'], [
		'setMarkings' => true,
	]));
}

$arResult['COLUMNS']['ATTENTION']['elements'][] = [
	'name' => 'ATTENTION_SECTION',
	'title' => '',
	'type' => 'section',
	'data' => [
		'showButtonPanel' => false,
		'isChangeable' => false,
		'isRemovable' => false,
		'enableTitle' => false,
	],
	'elements' => [
		[ 'name' => 'ATTENTION' ],
	],
];

$arResult['EDITOR']['ENTITY_FIELDS'][] = [
	'name' => 'ATTENTION',
	'title' => '',
	'type' => 'avito-export-attention',
	'editable' => false,
];

$arResult['EDITOR']['ENTITY_DATA']['ATTENTION'] = $values;
