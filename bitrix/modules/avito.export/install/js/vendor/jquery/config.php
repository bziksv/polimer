<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Avito\Export\Admin;

$jquery = 'jquery3';

if (Main\Loader::includeModule('avito.export'))
{
	$jquery = Admin\Library::resolve('jquery3', [
		'jquery2',
		'jquery',
	]);
}

return [
	'js' => [ 'noConflict.js' ],
	'rel' => [ $jquery ],
];