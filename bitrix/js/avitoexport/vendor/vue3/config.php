<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

$coreBitrixVue3 = \Bitrix\Main\UI\Extension::getConfig("ui.vue3");

$config = [
	'js' => [ 'vue3.min.js', 'noConflict.js' ],
];

if ($coreBitrixVue3 !== null)
{
	$config = [
		'js' => [ 'noConflict.js' ],
		'rel' => [ 'ui.vue3' ],
	];
}

return $config;