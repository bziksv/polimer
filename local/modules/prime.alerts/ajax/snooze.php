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

global $USER;
if (!is_object($USER) || !$USER->IsAuthorized() || !check_bitrix_sessid()) {
	http_response_code(403);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'error' => 'auth']);
	die;
}

if (!\Bitrix\Main\Loader::includeModule('prime.alerts')) {
	http_response_code(500);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'error' => 'module']);
	die;
}

if ((string)($_POST['mode'] ?? '') === 'dismiss') {
	\Prime\Alerts\ProfileBanner::dismissEmailConfirm();
	\Prime\Alerts\ProfileBanner::clearJustRegistered();
	echo \Bitrix\Main\Web\Json::encode(['ok' => true, 'dismissed' => true]);
	die;
}

$until = \Prime\Alerts\ProfileBanner::snooze();
echo \Bitrix\Main\Web\Json::encode(['ok' => true, 'until' => $until]);
