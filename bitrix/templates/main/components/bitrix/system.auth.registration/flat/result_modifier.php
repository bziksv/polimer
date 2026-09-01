<?php

use Bitrix\Main\Loader;

$arResult['PERSONAL_PHONE_REQUIRED'] = 'Y';

if (!empty($_REQUEST['USER_PERSONAL_PHONE'])) {
	$arResult['USER_PERSONAL_PHONE'] = htmlspecialcharsbx((string)$_REQUEST['USER_PERSONAL_PHONE']);
}

$arResult['PRIME_PHONEAUTH_TOKEN'] = '';
$arResult['PRIME_PHONEAUTH_CONFIRMED'] = 'N';

if (Loader::includeModule('prime.phoneauth')) {
	$token = trim((string)($_REQUEST['prime_phoneauth_token'] ?? ''));
	$phone = trim((string)($_REQUEST['USER_PERSONAL_PHONE'] ?? ''));
	if ($token !== '' && $phone !== '') {
		$norm = \Prime\PhoneAuth\Phone::national10($phone);
		if ($norm !== '' && \Prime\PhoneAuth\AuthService::registerTokenMatches($token, $norm)) {
			$arResult['PRIME_PHONEAUTH_TOKEN'] = htmlspecialcharsbx($token);
			$arResult['PRIME_PHONEAUTH_CONFIRMED'] = 'Y';
		}
	}
}
