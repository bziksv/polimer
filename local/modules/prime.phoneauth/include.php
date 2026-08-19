<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('prime.phoneauth', [
	'Prime\\PhoneAuth\\Config' => 'lib/Config.php',
	'Prime\\PhoneAuth\\Phone' => 'lib/Phone.php',
	'Prime\\PhoneAuth\\Challenge' => 'lib/Challenge.php',
	'Prime\\PhoneAuth\\AuthService' => 'lib/AuthService.php',
	'Prime\\PhoneAuth\\NovofonWebhook' => 'lib/NovofonWebhook.php',
	'Prime\\PhoneAuth\\Handlers' => 'lib/Handlers.php',
	'Prime\\PhoneAuth\\Frontend' => 'lib/Frontend.php',
]);

$eventsVer = '1.0.1';
if (Option::get('prime.phoneauth', 'events_version', '') !== $eventsVer) {
	$installFile = __DIR__ . '/install/index.php';
	if (is_file($installFile)) {
		include_once $installFile;
		if (class_exists('prime_phoneauth', false)) {
			$ob = new prime_phoneauth();
			$ob->InstallEvents();
			$ob->InstallUserFields();
			if (class_exists('\\Prime\\PhoneAuth\\Challenge')) {
				\Prime\PhoneAuth\Challenge::installTable();
			}
			Option::set('prime.phoneauth', 'events_version', $eventsVer);
		}
	}
}
