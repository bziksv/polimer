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

// Фиксация фразы после паузы ввода (отдельный лёгкий запрос из JS).
if (($_REQUEST['log_phrase'] ?? '') === 'y')
{
	$query = trim((string)($_POST['q'] ?? $_REQUEST['q'] ?? ''));
	if ($query !== '' && function_exists('CUtil'))
		CUtil::decodeURIComponent($query);

	$resultCount = max(0, (int)($_REQUEST['result_count'] ?? 0));

	if (
		$query !== ''
		&& function_exists('polimerBuildTitleSearchAjaxCacheId')
		&& class_exists('\Bitrix\Main\Data\Cache')
	)
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cacheId = polimerBuildTitleSearchAjaxCacheId($query, ['TOP_COUNT' => 50]);
		if ($cache->initCache(polimerGetTitleSearchAjaxCacheTtl(), $cacheId, polimerGetTitleSearchAjaxCacheDir()))
		{
			$vars = $cache->getVars();
			if (array_key_exists('RESULT_COUNT', $vars))
				$resultCount = max(0, (int)$vars['RESULT_COUNT']);
		}
	}

	if ($query !== '' && function_exists('polimerLogSearchPhrase'))
		polimerLogSearchPhrase($query, $resultCount);

	header('Content-Type: text/plain; charset=UTF-8');
	echo '1';
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
