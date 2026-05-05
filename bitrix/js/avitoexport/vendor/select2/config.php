<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Avito\Export\Admin;

if (
	Main\Loader::includeModule('avito.export')
	&& Admin\Library::includedScript('select2')
)
{
	return [
		'js' => [ '../jquery/noConflict.js' ],
	];
}

$encodingSuffix = Main\Application::isUtfMode() ? 'utf8' : 'cp1251';

return [
	'rel' => [ 'avitoexport.vendor.jquery' ],
	'css' => 'select2.min.css',
	'js' => array_filter([
		'select2.min.js',
		LANGUAGE_ID === 'ru' ? 'ru.' . $encodingSuffix . '.js' : null,
	]),
];