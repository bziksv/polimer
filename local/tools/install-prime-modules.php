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

\Bitrix\Main\Data\ManagedCache::clearByTag('bitrix_module_to_module');
if (function_exists('BXClearCache')) {
	BXClearCache(true);
}

echo "done\n";
