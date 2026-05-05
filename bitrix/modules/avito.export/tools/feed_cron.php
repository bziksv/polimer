<?php

define('BX_CRONTAB', true);
define('NO_AGENT_CHECK', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main;
use Avito\Export;

set_time_limit(600);

Main\Loader::includeModule('avito.export');

$setupId = (int)$_SERVER['argv'][1];
$feed = Export\Feed\Setup\Model::getById($setupId);

$controllerExport = new Export\Feed\Engine\Controller($feed, [
	'TIME_LIMIT' => 600,
	'INIT_TIME' => new Main\Type\DateTime(),
	'USE_TMP' => true,
]);
$controllerExport->export(Export\Feed\Engine\Controller::ACTION_RESTART);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
