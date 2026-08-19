<?php

namespace Prime\PhoneAuth;

use Bitrix\Main\Web\Json;

class Frontend
{
	public static function onEndBufferContent(&$content): void
	{
		if (PHP_SAPI === 'cli' || (defined('ADMIN_SECTION') && ADMIN_SECTION === true)) {
			return;
		}
		if (!is_string($content) || $content === '' || stripos($content, '</body>') === false) {
			return;
		}
		if (strpos($content, 'PRIME_PHONEAUTH') !== false) {
			return;
		}
		if (!\Bitrix\Main\Loader::includeModule('prime.phoneauth')) {
			return;
		}

		$moduleEnabled = Config::isEnabled();
		$hasRegistrationPhone = strpos($content, 'name="USER_PERSONAL_PHONE"') !== false
			|| strpos($content, 'REGISTER[PERSONAL_PHONE]') !== false;
		if (!$moduleEnabled && strpos($content, 'prime-phoneauth-tabs') === false && !$hasRegistrationPhone) {
			return;
		}

		try {
			$request = \Bitrix\Main\Context::getCurrent()->getRequest();
			if ($request->isAjaxRequest()) {
				return;
			}
		} catch (\Throwable $e) {
			// ignore
		}

		global $USER;
		$authorized = is_object($USER) && $USER->IsAuthorized();
		$profile = $authorized ? AuthService::profileState() : ['phone' => '', 'confirmed' => false, 'duplicate' => false, 'accounts' => []];

		$standalonePrompt = $authorized
			&& !$profile['confirmed']
			&& trim((string)$profile['phone']) !== ''
			&& !AuthService::hasAlertsProfileUi()
			&& !AuthService::isPersonalPage()
			&& !AuthService::isPromptSnoozed();

		$config = [
			'enabled' => $moduleEnabled,
			'callAuth' => Config::isCallAuthEnabled(),
			'lookupOnly' => !$moduleEnabled || !Config::isCallAuthEnabled(),
			'sessid' => function_exists('bitrix_sessid') ? bitrix_sessid() : '',
			'startUrl' => '/ajax/phoneauth.php?action=start',
			'lookupUrl' => '/ajax/phoneauth.php?action=lookup',
			'statusUrl' => '/ajax/phoneauth.php?action=status',
			'testUrl' => '/ajax/phoneauth.php?action=test',
			'snoozeUrl' => '/ajax/phoneauth.php?action=snooze',
			'callNumber' => Config::getVerifyNumberDisplay(),
			'testConfirm' => Config::isTestConfirm(),
			'authorized' => $authorized,
			'phone' => $profile['phone'] !== '' ? \Prime\PhoneAuth\Phone::format($profile['phone']) : '',
			'confirmed' => $profile['confirmed'],
			'duplicate' => $profile['duplicate'],
			'duplicateMessage' => AuthService::duplicateMessage(),
			'duplicateAccounts' => $profile['accounts'],
			'standalonePrompt' => $standalonePrompt,
		];

		$css = '/local/modules/prime.phoneauth/assets/auth.css?v=1.2.3';
		$js = '/local/modules/prime.phoneauth/assets/auth.js?v=1.0.24';
		$alertsCss = '';
		if ($hasRegistrationPhone && \Bitrix\Main\Loader::includeModule('prime.alerts')) {
			$alertsCss = '<link rel="stylesheet" href="/local/modules/prime.alerts/assets/style.css?v=1.5.20">' . "\n";
		}
		$inject = "\n" . $alertsCss
			. "<link rel=\"stylesheet\" href=\"" . htmlspecialcharsbx($css) . "\">\n"
			. '<script>window.PRIME_PHONEAUTH=' . Json::encode($config) . ';</script>' . "\n"
			. '<script src="' . htmlspecialcharsbx($js) . '"></script>' . "\n";

		$content = preg_replace('/<\/body>/i', $inject . '</body>', $content, 1);
	}
}
