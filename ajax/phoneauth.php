<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=UTF-8');

function primePhoneauthJson(array $data, int $code = 200): void
{
	http_response_code($code);
	echo \Bitrix\Main\Web\Json::encode($data);
	die;
}

$loaded = \Bitrix\Main\Loader::includeModule('prime.phoneauth');
if (!$loaded) {
	$inc = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/prime.phoneauth/include.php';
	if (is_file($inc)) {
		require_once $inc;
		$loaded = class_exists(\Prime\PhoneAuth\AuthService::class);
	}
}
if (!$loaded) {
	primePhoneauthJson(['ok' => false, 'error' => 'module'], 500);
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'lookup') {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !check_bitrix_sessid()) {
		primePhoneauthJson(['ok' => false, 'error' => 'auth'], 403);
	}
	$phone = (string)($_POST['phone'] ?? '');
	primePhoneauthJson(\Prime\PhoneAuth\AuthService::lookup($phone));
}

if ($action === 'start') {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !check_bitrix_sessid()) {
		primePhoneauthJson(['ok' => false, 'error' => 'auth'], 403);
	}
	$phone = (string)($_POST['phone'] ?? '');
	$verify = (string)($_POST['verify'] ?? '') === 'Y';
	$register = (string)($_POST['register'] ?? '') === 'Y';
	$claim = (string)($_POST['claim'] ?? '') === 'Y';
	global $USER;
	$asUser = ($verify && is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : null;
	if ($asUser && $phone === '') {
		$row = \CUser::GetByID($asUser)->Fetch();
		$phone = (string)($row['PERSONAL_PHONE'] ?? '');
	}
	if ($register && !$asUser) {
		primePhoneauthJson(\Prime\PhoneAuth\AuthService::startRegister($phone, $claim));
	}
	primePhoneauthJson(\Prime\PhoneAuth\AuthService::start($phone, $asUser, $claim));
}

if ($action === 'status') {
	$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
	primePhoneauthJson(\Prime\PhoneAuth\AuthService::status($token));
}

if ($action === 'test') {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !check_bitrix_sessid()) {
		primePhoneauthJson(['ok' => false, 'error' => 'auth'], 403);
	}
	$token = (string)($_POST['token'] ?? '');
	primePhoneauthJson(\Prime\PhoneAuth\AuthService::testConfirm($token));
}

if ($action === 'snooze') {
	if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !check_bitrix_sessid()) {
		primePhoneauthJson(['ok' => false, 'error' => 'auth'], 403);
	}
	global $USER;
	if (!is_object($USER) || !$USER->IsAuthorized()) {
		primePhoneauthJson(['ok' => false, 'error' => 'auth'], 403);
	}
	\Prime\PhoneAuth\AuthService::snoozePrompt();
	primePhoneauthJson(['ok' => true]);
}

primePhoneauthJson(['ok' => false, 'error' => 'unknown action'], 400);
