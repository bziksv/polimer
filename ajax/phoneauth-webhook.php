<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=UTF-8');

$loaded = \Bitrix\Main\Loader::includeModule('prime.phoneauth');
if (!$loaded) {
	$inc = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/prime.phoneauth/include.php';
	if (is_file($inc)) {
		require_once $inc;
		$loaded = class_exists(\Prime\PhoneAuth\NovofonWebhook::class);
	}
}
if (!$loaded) {
	http_response_code(500);
	echo \Bitrix\Main\Web\Json::encode(['ok' => false, 'message' => 'module']);
	die;
}

$bag = \Prime\PhoneAuth\NovofonWebhook::parseParams();
$check = \Prime\PhoneAuth\NovofonWebhook::verifyRequest($bag);
if (!$check['ok']) {
	error_log('prime.phoneauth webhook deny: ' . \Bitrix\Main\Web\Json::encode([
		'message' => (string)($check['message'] ?? ''),
		'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
	]));
	http_response_code((int)($check['status'] ?? 403));
	echo \Bitrix\Main\Web\Json::encode($check);
	die;
}

$result = \Prime\PhoneAuth\NovofonWebhook::handle($bag);
error_log('prime.phoneauth webhook: ' . \Bitrix\Main\Web\Json::encode([
	'ok' => !empty($result['ok']),
	'message' => (string)($result['message'] ?? ''),
	'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
]));
echo \Bitrix\Main\Web\Json::encode($result);
