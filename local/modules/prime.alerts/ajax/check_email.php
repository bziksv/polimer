<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'error' => 'method']);
	die;
}

if (!check_bitrix_sessid()) {
	http_response_code(403);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'error' => 'sessid']);
	die;
}

if (!\Bitrix\Main\Loader::includeModule('prime.alerts')) {
	http_response_code(500);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'error' => 'module']);
	die;
}

$email = trim((string)($_POST['email'] ?? ''));
$result = \Prime\Alerts\EmailLookup::lookup($email);

echo \Bitrix\Main\Web\Json::encode($result);
