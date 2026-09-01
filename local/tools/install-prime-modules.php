<?php

define('NO_KEEP_STATISTIC', true);
define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('DisableEventsCheck', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: text/plain; charset=UTF-8');

$key = (string)($_GET['key'] ?? '');
if ($key !== 'polimer-prime-install-2026') {
	http_response_code(403);
	echo "forbidden\n";
	die;
}

$modules = [
	'prime.alerts' => 'prime_alerts',
	'prime.phoneauth' => 'prime_phoneauth',
];

$primeAlertsOptions = [
	'enabled' => 'Y',
	'policy_enabled' => 'Y',
	'policy_register' => 'Y',
	'policy_order' => 'Y',
	'notice_everywhere' => 'N',
	'support_email' => 'info@polimer-vrn.ru',
	'support_phone' => '+7 (473) 250-22-33',
	'extra_domains' => '',
	'profile_banner' => 'Y',
	'color_scheme' => 'polimer',
];

foreach ($modules as $moduleId => $className) {
	$installFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $moduleId . '/install/index.php';
	if (!is_file($installFile)) {
		echo "missing {$moduleId}\n";
		continue;
	}
	include_once $installFile;
	if (!class_exists($className, false)) {
		echo "class {$className} not found\n";
		continue;
	}
	$installer = new $className();
	if (!\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId)) {
		$installer->DoInstall();
		echo "installed {$moduleId}\n";
	} else {
		$installer->InstallEvents();
		echo "events refreshed {$moduleId}\n";
	}
}

if (\Bitrix\Main\Loader::includeModule('prime.alerts')) {
	foreach ($primeAlertsOptions as $name => $value) {
		if (\Bitrix\Main\Config\Option::get('prime.alerts', $name, '') === '') {
			\Bitrix\Main\Config\Option::set('prime.alerts', $name, $value);
		}
	}
	echo "options ensured prime.alerts\n";
}

try {
	$managed = \Bitrix\Main\Application::getInstance()->getManagedCache();
	if (is_object($managed) && method_exists($managed, 'cleanDir')) {
		$managed->cleanDir('b_module');
		$managed->cleanDir('b_module_to_module');
	}
} catch (\Throwable $e) {
	// ignore cache cleanup errors
}
if (function_exists('BXClearCache')) {
	BXClearCache(true);
}

echo "done\n";
