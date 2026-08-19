<?php

namespace Prime\PhoneAuth;

class Handlers
{
	public static function onBeforeUserRegister(&$arFields)
	{
		self::ensurePersonalPhoneFromPost($arFields);

		return self::validateRegisterPhone($arFields);
	}

	public static function onBeforeUserAdd(&$arFields)
	{
		self::ensurePersonalPhoneFromPost($arFields);
		if (!self::validateRegisterPhone($arFields)) {
			return false;
		}
		self::syncPhoneFields($arFields, 0);
		self::applyRegisterConfirmation($arFields);

		return true;
	}

	public static function onAfterUserRegister(&$arFields): void
	{
		$userId = (int)($arFields['USER_ID'] ?? $arFields['ID'] ?? 0);
		if ($userId <= 0) {
			return;
		}

		$phone = trim((string)($_POST['USER_PERSONAL_PHONE'] ?? $_REQUEST['USER_PERSONAL_PHONE'] ?? ''));
		if ($phone === '') {
			return;
		}

		$norm = Phone::national10($phone);
		if ($norm === '') {
			return;
		}

		$token = (string)($_POST['prime_phoneauth_token'] ?? '');
		if ($token === '' || !AuthService::registerTokenMatches($token, $norm)) {
			return;
		}

		$rs = \CUser::GetByID($userId);
		$row = $rs ? $rs->Fetch() : false;
		if ($row && AuthService::isConfirmed($row[AuthService::UF_CONFIRMED] ?? 0)) {
			if (AuthService::consumeRegisterToken($token, $norm)) {
				AuthService::releasePhoneFromOthers($userId, $norm);
			}

			return;
		}

		$user = new \CUser();
		$user->Update($userId, [
			'PERSONAL_PHONE' => $phone,
			AuthService::UF_CONFIRMED => 1,
			AuthService::UF_NORM => $norm,
		]);

		if (AuthService::consumeRegisterToken($token, $norm)) {
			AuthService::releasePhoneFromOthers($userId, $norm);
		}
	}

	public static function onAfterUserAdd(&$arFields): void
	{
		$id = (int)($arFields['ID'] ?? 0);
		if ($id <= 0) {
			return;
		}
		$token = (string)($_POST['prime_phoneauth_token'] ?? '');
		if ($token === '') {
			return;
		}
		$norm = Phone::national10((string)($arFields['PERSONAL_PHONE'] ?? ''));
		if ($norm === '') {
			return;
		}
		if (AuthService::consumeRegisterToken($token, $norm)) {
			AuthService::releasePhoneFromOthers($id, $norm);
		}
	}

	public static function onBeforeUserUpdate(&$arFields): void
	{
		$id = (int)($arFields['ID'] ?? 0);
		self::syncPhoneFields($arFields, $id);
	}

	protected static function ensurePersonalPhoneFromPost(array &$arFields): void
	{
		if (trim((string)($arFields['PERSONAL_PHONE'] ?? '')) !== '') {
			return;
		}

		$phone = trim((string)($_POST['USER_PERSONAL_PHONE'] ?? $_REQUEST['USER_PERSONAL_PHONE'] ?? ''));
		if ($phone !== '') {
			$arFields['PERSONAL_PHONE'] = $phone;
		}
	}

	protected static function hasValidRegisterToken(string $norm): bool
	{
		if ($norm === '') {
			return false;
		}

		$token = (string)($_POST['prime_phoneauth_token'] ?? '');

		return $token !== '' && AuthService::registerTokenMatches($token, $norm);
	}

	protected static function validateRegisterPhone(array &$arFields): bool
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
			return true;
		}

		$login = strtolower(trim((string)($arFields['LOGIN'] ?? '')));
		if ($login === 'technical_boc' || strpos($login, 'technical_') === 0) {
			return true;
		}

		$phone = trim((string)($arFields['PERSONAL_PHONE'] ?? ''));
		if ($phone === '') {
			return true;
		}

		$lookup = AuthService::lookup($phone);
		if (empty($lookup['accounts'])) {
			return true;
		}

		$norm = Phone::national10($phone);
		$token = (string)($_POST['prime_phoneauth_token'] ?? '');
		if ($token !== '' && AuthService::registerTokenMatches($token, $norm)) {
			return true;
		}

		global $APPLICATION;
		if (is_object($APPLICATION)) {
			$APPLICATION->ThrowException((string)($lookup['message'] ?? AuthService::duplicateMessage()));
		}

		return false;
	}

	protected static function syncPhoneFields(array &$arFields, int $userId): void
	{
		if (!array_key_exists('PERSONAL_PHONE', $arFields)) {
			return;
		}

		$newNorm = Phone::national10((string)$arFields['PERSONAL_PHONE']);
		if ($newNorm !== '') {
			$arFields[AuthService::UF_NORM] = $newNorm;
		} else {
			$arFields[AuthService::UF_NORM] = '';
		}

		$oldNorm = '';
		$wasConfirmed = false;
		if ($userId > 0) {
			$rs = \CUser::GetByID($userId);
			$old = $rs ? $rs->Fetch() : false;
			if ($old) {
				$oldNorm = Phone::national10((string)($old['PERSONAL_PHONE'] ?? ''));
				$wasConfirmed = AuthService::isConfirmed($old[AuthService::UF_CONFIRMED] ?? 0);
			}
		}

		if ($newNorm !== $oldNorm) {
			$arFields[AuthService::UF_CONFIRMED] = self::hasValidRegisterToken($newNorm) ? 1 : 0;
		} elseif ($wasConfirmed) {
			$arFields[AuthService::UF_CONFIRMED] = 1;
		}
	}

	protected static function applyRegisterConfirmation(array &$arFields): void
	{
		$token = (string)($_POST['prime_phoneauth_token'] ?? '');
		if ($token === '') {
			return;
		}
		$norm = Phone::national10((string)($arFields['PERSONAL_PHONE'] ?? ''));
		if ($norm === '' || !AuthService::registerTokenMatches($token, $norm)) {
			return;
		}
		$arFields[AuthService::UF_CONFIRMED] = 1;
		$arFields[AuthService::UF_NORM] = $norm;
	}
}
