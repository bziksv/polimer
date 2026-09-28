<?php
/**
 * Лёгкий ajax для поиска в шапке: без рендера главной страницы.
 * JS шлёт сюда POST (ajax_call=y, q, INPUT_ID, l).
 */
define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (($_REQUEST['ajax_call'] ?? '') !== 'y')
{
	CHTTP::SetStatus('400 Bad Request');
	echo 'bad request';
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
	die();
}

$inputId = (string)($_REQUEST['INPUT_ID'] ?? 'title-search-input');
$allowedInputs = [
	'title-search-input' => 'title-search',
	'title-search-input-mobile' => 'title-search-mobile',
];

if (!isset($allowedInputs[$inputId]))
	$inputId = 'title-search-input';

$containerId = $allowedInputs[$inputId];

$APPLICATION->IncludeComponent(
	'prime:search.title',
	'search.title',
	[
		'CATEGORY_0' => ['iblock_1c_catalog'],
		'CATEGORY_0_TITLE' => 'Каталог',
		'CATEGORY_0_iblock_1c_catalog' => ['21'],
		'CHECK_DATES' => 'N',
		'CONTAINER_ID' => $containerId,
		'INPUT_ID' => $inputId,
		'NUM_CATEGORIES' => '1',
		'ORDER' => 'date',
		'PAGE' => '#SITE_DIR#search/',
		'SHOW_INPUT' => 'N',
		'SHOW_OTHERS' => 'N',
		'TOP_COUNT' => '50',
		'CACHE_TYPE' => 'N',
		'CACHE_TIME' => '0',
		'USE_LANGUAGE_GUESS' => 'Y',
	],
	false
);

// Компонент при ajax_call сам делает die(); сюда попадаем только если запрос пустой.
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
