<?php
/**
 * Картинки разделов каталога: экспорт, обработка (прозрачный фон), загрузка в Bitrix.
 *
 * CLI:
 *   php -d short_open_tag=On tools/catalog-section-images.php list [--depth=2] [--limit=20]
 *   php -d short_open_tag=On tools/catalog-section-images.php export [--ids=1145,1238] [--all]
 *   php -d short_open_tag=On tools/catalog-section-images.php apply [--ids=1145] [--dry-run]
 *   php -d short_open_tag=On tools/catalog-section-images.php restore [--ids=1145] [--all]
 *
 * Web (админ / token): /tools/catalog-section-images.php?action=list&token=...
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/polimer_section_image.php';

PolimerSectionImageTool::run();
