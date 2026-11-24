<?php
/**
 * Выключает запись поисковых фраз в b_search_phrase (модуль search, опция stat_phrase).
 * Таблицу не чистит.
 *
 * Запуск из корня сайта:
 *   php -d short_open_tag=On local/tools/disable-search-phrase-stat.php
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$before = COption::GetOptionString('search', 'stat_phrase', 'Y');
COption::SetOptionString('search', 'stat_phrase', 'N');
$after = COption::GetOptionString('search', 'stat_phrase', 'Y');

echo "stat_phrase {$before} -> {$after}\n";
