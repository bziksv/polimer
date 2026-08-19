<?php

namespace Prime\Alerts;

class EmailLookup
{
	public static function normalize(string $email): string
	{
		$email = trim($email);
		if ($email === '') {
			return '';
		}

		return function_exists('mb_strtolower') ? mb_strtolower($email) : strtolower($email);
	}

	public static function getExistsMessage(): string
	{
		return 'Этот e-mail уже зарегистрирован на сайте. Войдите в личный кабинет или восстановите пароль.';
	}

	public static function getExistsNoticeHtml(): string
	{
		$authUrl = '/auth/';
		$forgotUrl = '/auth/?forgot_password=yes';

		return '<div class="prime-alerts-notice prime-alerts-notice--exists signup-email-exists-notice">'
			. '<div class="prime-alerts-notice__inner">'
			. '<div class="prime-alerts-notice__icon" aria-hidden="true">!</div>'
			. '<div class="prime-alerts-notice__content">'
			. '<div class="prime-alerts-notice__title">E-mail уже используется</div>'
			. '<div class="prime-alerts-notice__text">'
			. '<p>' . htmlspecialcharsbx(self::getExistsMessage()) . '</p>'
			. '<p><a href="' . htmlspecialcharsbx($authUrl) . '">Войти</a>'
			. ' · <a href="' . htmlspecialcharsbx($forgotUrl) . '">Забыли пароль?</a></p>'
			. '</div></div></div>';
	}

	public static function isRegistered(string $email, int $excludeUserId = 0): bool
	{
		$email = self::normalize($email);
		if ($email === '' || strpos($email, '@') === false) {
			return false;
		}

		$filter = [
			'LOGIC' => 'OR',
			'=EMAIL' => $email,
			'=LOGIN' => $email,
		];
		if ($excludeUserId > 0) {
			$filter['!ID'] = $excludeUserId;
		}

		$rs = \CUser::GetList(
			($by = 'id'),
			($order = 'asc'),
			$filter,
			['FIELDS' => ['ID']]
		);

		return (bool)$rs->Fetch();
	}

	/** @return array{ok:bool,exists:bool,message:string} */
	public static function lookup(string $email, int $excludeUserId = 0): array
	{
		$email = trim($email);
		if ($email === '' || !check_email($email)) {
			return [
				'ok' => true,
				'exists' => false,
				'message' => '',
			];
		}

		$exists = self::isRegistered($email, $excludeUserId);

		return [
			'ok' => true,
			'exists' => $exists,
			'message' => $exists ? self::getExistsMessage() : '',
		];
	}
}
