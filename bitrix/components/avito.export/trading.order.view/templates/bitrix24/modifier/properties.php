<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */

foreach ($arResult['PROPERTIES'] as $blockCode => $block)
{
	if (empty($block['FIELDS'])) { continue; }

	$column = isset($arResult['COLUMNS'][$blockCode]) ? $blockCode : 'INFO';

	$configSection = [
		'name' => $blockCode,
		'title' => $block['NAME'],
		'type' => 'section',
		'data' => [
			'showButtonPanel' => false,
			'isChangeable' => false,
			'isRemovable' => false,
			'enableToggling' => false, // disable edit
		],
		'elements' => [],
	];

	foreach ($block['FIELDS'] as $propertyCode => $property)
	{
		$configSection['elements'][] = [
			'name' => $propertyCode,
		];

		$arResult['EDITOR']['ENTITY_FIELDS'][] = [
			'name' => $propertyCode,
			'title' => html_entity_decode($property['NAME']),
			'type' => 'avito-export-property',
			'editable' => false,
		];

		$property['VALUE'] = html_entity_decode(htmlspecialcharsback($property['VALUE']));

		$arResult['EDITOR']['ENTITY_DATA'][$propertyCode] = $property;
	}

	$arResult['COLUMNS'][$column]['elements'][] = $configSection;
}
