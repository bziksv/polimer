<?php

namespace Prime\Alerts;

class ProfileBanner
{
	public const SNOOZE_SECONDS = 1209600; // 14 суток
	public const SNOOZE_OPTION = 'profile_modal_snooze';

	public static function shouldShow(): bool
	{
		if (!self::isBannerEnabled() || !self::needsAttention()) {
			return false;
		}

		if (self::isQuietPath() || self::isProfileEditPath()) {
			return false;
		}

		if (self::isPersonalPath()) {
			return true;
		}

		return !self::isSnoozed();
	}

	public static function shouldShowInline(): bool
	{
		if (!self::isBannerEnabled() || !self::needsAttention()) {
			return false;
		}

		return self::isProfileEditPath();
	}

	protected static function isBannerEnabled(): bool
	{
		if (!Config::isEnabled() || !Config::isYes('profile_banner', 'Y')) {
			return false;
		}

		global $USER;

		return is_object($USER) && $USER->IsAuthorized();
	}

	protected static function needsAttention(): bool
	{
		return self::emailUnconfirmed() || self::emailNeedsAttention() || self::phoneIssue();
	}

	public static function isQuietPath(): bool
	{
		try {
			$path = (string)\Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPageDirectory();
		} catch (\Throwable $e) {
			return false;
		}

		$skip = ['/confirm', '/auth', '/login', '/make-order'];
		foreach ($skip as $prefix) {
			if ($path === $prefix || strpos($path, $prefix . '/') === 0) {
				return true;
			}
		}

		return false;
	}

	public static function isPersonalPath(): bool
	{
		try {
			$path = (string)\Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPageDirectory();
			return $path === '/personal' || strpos($path, '/personal/') === 0;
		} catch (\Throwable $e) {
			return false;
		}
	}

	/** Страницы, где пользователь уже редактирует профиль — баннер не нужен. */
	public static function isProfileEditPath(): bool
	{
		try {
			$request = \Bitrix\Main\Context::getCurrent()->getRequest();
			$path = (string)$request->getRequestedPageDirectory();
			$uri = (string)$request->getRequestUri();
		} catch (\Throwable $e) {
			return false;
		}

		foreach ([
			'/personal/info',
			'/personal/security',
			'/personal/profile',
		] as $prefix) {
			if ($path === $prefix || strpos($path, $prefix . '/') === 0 || strpos($path, $prefix . '.php') === 0) {
				return true;
			}
		}

		if ((bool)preg_match('#/personal/(info|security|profile)(\.php)?(/|$)#', $path)) {
			return true;
		}

		return (bool)preg_match('#/personal/(info|security|profile)(\.php)?(\?|$|/)#', $uri);
	}

	public static function emailUnconfirmed(?array $data = null): bool
	{
		$data = $data ?: self::profileData();

		return trim((string)($data['confirmCode'] ?? '')) !== '';
	}

	public static function isJustRegistered(): bool
	{
		try {
			$session = \Bitrix\Main\Application::getInstance()->getSession();

			return $session->get('PRIME_ALERTS_JUST_REGISTERED') === 'Y';
		} catch (\Throwable $e) {
			return false;
		}
	}

	public static function clearJustRegistered(): void
	{
		try {
			\Bitrix\Main\Application::getInstance()->getSession()->remove('PRIME_ALERTS_JUST_REGISTERED');
		} catch (\Throwable $e) {
			// ignore
		}
	}

	public static function dismissEmailConfirm(): void
	{
		try {
			\Bitrix\Main\Application::getInstance()->getSession()->set('prime_alerts_email_confirm_dismissed', 'Y');
		} catch (\Throwable $e) {
			// ignore
		}
	}

	public static function isEmailConfirmDismissed(): bool
	{
		try {
			return \Bitrix\Main\Application::getInstance()->getSession()->get('prime_alerts_email_confirm_dismissed') === 'Y';
		} catch (\Throwable $e) {
			return false;
		}
	}

	public static function emailNeedsAttention(?array $data = null): bool
	{
		$data = $data ?: self::profileData();
		$email = trim((string)($data['email'] ?? ''));

		return $email !== '' && !EmailPolicy::isAllowed($email);
	}

	public static function phoneAuthAvailable(): bool
	{
		return self::phoneAuthState() !== null;
	}

	public static function phoneNeedsAttention(): bool
	{
		$state = self::phoneAuthState();
		if ($state === null) {
			return false;
		}
		if (!empty($state['confirmed'])) {
			return false;
		}

		return trim((string)($state['phone'] ?? '')) !== '';
	}

	public static function phoneIssue(): bool
	{
		global $USER;
		if (!is_object($USER) || !$USER->IsAuthorized()) {
			return false;
		}

		$state = self::phoneAuthState();
		if ($state === null) {
			return false;
		}
		if (!empty($state['confirmed'])) {
			return false;
		}

		return true;
	}

	/** @return array{phone:string,confirmed:bool,duplicate:bool,accounts:list<string>}|null */
	public static function phoneAuthState(): ?array
	{
		static $state = false;
		if ($state !== false) {
			return $state;
		}
		$state = null;
		try {
			if (!\Bitrix\Main\Loader::includeModule('prime.phoneauth')) {
				return null;
			}
			if (!\Prime\PhoneAuth\Config::isEnabled()) {
				return null;
			}
			$state = \Prime\PhoneAuth\AuthService::profileState();
		} catch (\Throwable $e) {
			$state = null;
		}

		return $state;
	}

	public static function snoozeUntil(): int
	{
		global $USER;
		if (!is_object($USER) || !$USER->IsAuthorized()) {
			return 0;
		}

		return (int)\CUserOptions::GetOption(Config::MODULE_ID, self::SNOOZE_OPTION, '0');
	}

	public static function isSnoozed(): bool
	{
		return self::snoozeUntil() > time();
	}

	public static function snooze(int $seconds = self::SNOOZE_SECONDS): int
	{
		$until = time() + max(1, $seconds);
		\CUserOptions::SetOption(Config::MODULE_ID, self::SNOOZE_OPTION, (string)$until);

		return $until;
	}

	/** @return array{email:string,phone:string,profileUrl:string,confirmCode:string} */
	public static function profileData(): array
	{
		global $USER;
		$email = '';
		$phone = '';
		$confirmCode = '';
		if (is_object($USER) && $USER->IsAuthorized()) {
			$email = trim((string)$USER->GetEmail());
			$login = trim((string)$USER->GetLogin());
			$rs = \CUser::GetByID((int)$USER->GetID());
			if ($row = $rs->Fetch()) {
				$phone = trim((string)($row['PERSONAL_PHONE'] ?? ''));
				$confirmCode = trim((string)($row['CONFIRM_CODE'] ?? ''));
				if ($email === '') {
					$email = trim((string)($row['EMAIL'] ?? ''));
				}
				if ($login === '') {
					$login = trim((string)($row['LOGIN'] ?? ''));
				}
			}
			if ($email === '' && strpos($login, '@') !== false) {
				$email = $login;
			}
		}

		return [
			'email' => $email,
			'phone' => $phone,
			'profileUrl' => '/personal/info.php',
			'confirmCode' => $confirmCode,
		];
	}

	public static function getDefaultTitle(): string
	{
		$emailUnconfirmed = self::emailUnconfirmed();
		$emailBad = self::emailNeedsAttention();
		$phoneBad = self::phoneNeedsAttention();
		if ($emailUnconfirmed) {
			return 'Подтвердите почту';
		}
		if ($emailBad && $phoneBad) {
			return 'Проверьте почту и телефон в профиле';
		}
		if ($emailBad) {
			return 'Проверьте почту в профиле';
		}
		if ($phoneBad) {
			return 'Подтвердите телефон';
		}

		return 'Проверьте почту и телефон в профиле';
	}

	public static function getDefaultText(): string
	{
		$emailUnconfirmed = self::emailUnconfirmed();
		$emailBad = self::emailNeedsAttention();
		$phoneBad = self::phoneNeedsAttention();
		$phoneModule = self::phoneAuthAvailable();
		$parts = [];
		if ($emailUnconfirmed) {
			if (self::isJustRegistered()) {
				$parts[] = '<p>На указанный адрес отправлена ссылка для подтверждения регистрации. '
					. 'Можете сразу оформлять заказы — просто перейдите по ссылке из письма, когда будет удобно.</p>';
			} else {
				$parts[] = '<p>Вы до сих пор не подтвердили почту. '
					. 'Перейдите по ссылке из письма — так мы будем уверены, что ящик ваш.</p>';
			}
		} elseif ($emailBad) {
			$parts[] = '<p>В вашем профиле указан иностранный почтовый адрес или зарубежная почтовая служба. '
				. 'Рекомендуем сменить его на ящик в зоне <strong>.ru</strong> / <strong>.su</strong> '
				. 'либо на российский сервис (Яндекс, Mail.ru).</p>';
		}
		if ($phoneModule && $phoneBad) {
			$parts[] = '<p>Подтвердите номер звонком — после этого можно будет входить в кабинет по телефону, без пароля.</p>';
		} elseif (!$phoneModule && !$emailUnconfirmed) {
			$parts[] = '<p>Также просим проверить телефон, указанный в профиле. '
				. 'Позже на сайте появится вход по номеру — важно, чтобы он был актуальным.</p>';
		}

		return implode("\n", $parts);
	}

	public static function getTitle(): string
	{
		if (self::emailUnconfirmed()) {
			$title = self::getDefaultTitle();
		} else {
			$custom = trim(Config::get('profile_banner_title', ''));
			$title = $custom !== '' ? $custom : self::getDefaultTitle();
		}

		return EmailPolicy::applyMacrosPlain(self::applyProfileMacrosPlain($title));
	}

	public static function getBodyHtml(): string
	{
		if (self::emailUnconfirmed()) {
			$body = self::getDefaultText();
		} else {
			$custom = trim(Config::get('profile_banner_text', ''));
			$body = $custom !== '' ? $custom : self::getDefaultText();
		}

		return EmailPolicy::applyMacrosHtml(self::applyProfileMacrosHtml($body));
	}

	public static function render(): string
	{
		if (!self::shouldShow()) {
			return '';
		}

		return self::buildMarkup('modal');
	}

	public static function renderInline(): string
	{
		if (!self::shouldShowInline()) {
			return '';
		}

		return self::buildMarkup('inline');
	}

	protected static function buildMarkup(string $mode): string
	{
		$inline = $mode === 'inline';
		$data = self::profileData();
		$email = $data['email'] !== '' ? $data['email'] : 'не указан';
		self::phoneAuthState();
		$phone = self::displayPhone($data['phone']);
		$needConfirm = self::phoneAuthAvailable() && self::phoneNeedsAttention();
		$emailUnconfirmed = self::emailUnconfirmed($data);
		$emailBad = self::emailNeedsAttention($data);
		$showSnooze = !$inline;
		$emailEsc = htmlspecialcharsbx($email);
		$phoneEsc = htmlspecialcharsbx($phone);
		$urlEsc = htmlspecialcharsbx($data['profileUrl']);
		$title = htmlspecialcharsbx(self::getTitle());
		$body = self::getBodyHtml();

		$phoneEmpty = trim((string)($data['phone'] ?? '')) === '';
		$canPhone = self::phoneAuthAvailable();
		$phoneState = self::phoneAuthState();
		$phoneConfirmed = $canPhone && !empty($phoneState['confirmed']);
		$sessid = function_exists('bitrix_sessid') ? bitrix_sessid() : '';
		$emailLi = '<li><span>Почта в профиле</span><strong>' . $emailEsc . '</strong></li>';
		if ($emailUnconfirmed) {
			$emailLi = '<li>'
				. '<span>Почта в профиле</span>'
				. '<div class="prime-alerts-profile-modal__phone-row">'
				. '<strong>' . $emailEsc . '</strong>'
				. '<button type="button" class="prime-phoneauth-prompt__btn is-compact" data-email-confirm="1" data-sessid="' . htmlspecialcharsbx($sessid) . '">Выслать письмо</button>'
				. '</div></li>';
		}
		$phoneLi = '<li><span>Телефон в профиле</span><strong>' . $phoneEsc . '</strong></li>';
		if ($canPhone && ($phoneEmpty || $needConfirm)) {
			if ($inline && !$phoneEmpty) {
				$phoneLi = '<li>'
					. '<span>Телефон в профиле</span>'
					. '<div class="prime-alerts-profile-modal__phone-row">'
					. '<strong>' . $phoneEsc . '</strong>'
					. '<span class="prime-alerts-profile-banner__hint">Подтвердите номер в форме ниже</span>'
					. '</div></li>';
			} else {
				$phoneLi = '<li>'
					. '<span>Телефон в профиле</span>'
					. '<div class="prime-alerts-profile-modal__phone-row">'
					. ($phoneEmpty ? '' : '<strong>' . $phoneEsc . '</strong>')
					. '<span class="is-inline" data-prime-phone-confirm="1"></span>'
					. '</div></li>';
			}
		} elseif ($phoneConfirmed) {
			$phoneLi = '<li>'
				. '<span>Телефон в профиле</span>'
				. '<div class="prime-alerts-profile-modal__phone-row">'
				. '<strong>' . $phoneEsc . '</strong>'
				. '<div class="prime-phoneauth-status is-ok">Номер подтверждён — можно входить по телефону</div>'
				. '</div></li>';
		}
		$facts = '<ul class="prime-alerts-profile-modal__facts">' . $emailLi . $phoneLi . '</ul>'
			. '<div class="prime-alerts-profile-modal__note" data-prime-phone-note="1"></div>';

		if ($inline) {
			return '<div class="prime-alerts-profile-banner" data-email-unconfirmed="' . ($emailUnconfirmed ? '1' : '0') . '">'
				. '<div class="prime-alerts-profile-banner__title">' . $title . '</div>'
				. '<div class="prime-alerts-profile-banner__text">' . $body . '</div>'
				. $facts
				. '</div>';
		}

		$primary = ($emailUnconfirmed && !$emailBad)
			? '<button type="button" class="prime-alerts-profile-modal__btn" data-prime-alerts-close="1">Понятно, продолжить</button>'
			: '<a class="prime-alerts-profile-modal__btn" href="' . $urlEsc . '">Изменить данные в профиле</a>';
		$snooze = $showSnooze
			? '<button type="button" class="prime-alerts-profile-modal__snooze" data-prime-alerts-snooze="1">Отложить на 2 недели</button>'
			: '';

		return '<div class="prime-alerts-profile-modal" role="dialog" aria-modal="true" aria-labelledby="prime-alerts-profile-title" data-email-unconfirmed="' . ($emailUnconfirmed ? '1' : '0') . '">'
			. '<div class="prime-alerts-profile-modal__overlay"></div>'
			. '<div class="prime-alerts-profile-modal__box">'
			. '<button type="button" class="prime-alerts-profile-modal__close" data-prime-alerts-close="1" aria-label="Закрыть"></button>'
			. '<div class="prime-alerts-profile-modal__icon" aria-hidden="true">!</div>'
			. '<div id="prime-alerts-profile-title" class="prime-alerts-profile-modal__title">' . $title . '</div>'
			. '<div class="prime-alerts-profile-modal__text">' . $body . '</div>'
			. $facts
			. '<div class="prime-alerts-profile-modal__actions">'
			. $primary
			. $snooze
			. '</div>'
			. '</div></div>';
	}

	protected static function displayPhone(string $phone): string
	{
		$phone = trim($phone);
		if ($phone === '') {
			return 'не указан';
		}
		if (\Bitrix\Main\Loader::includeModule('prime.phoneauth')) {
			$formatted = \Prime\PhoneAuth\Phone::format($phone);
			if ($formatted !== '') {
				return $formatted;
			}
		}

		return $phone;
	}

	protected static function applyProfileMacrosPlain(string $text): string
	{
		$data = self::profileData();
		$email = $data['email'] !== '' ? $data['email'] : 'не указан';
		$phoneRaw = $data['phone'] !== '' ? $data['phone'] : 'не указан';
		$phone = $data['phone'] !== '' ? self::displayPhone($data['phone']) : 'не указан';

		return str_replace(
			['#PROFILE_EMAIL#', '#PROFILE_PHONE#', '#PROFILE_EMAIL_RAW#', '#PROFILE_PHONE_RAW#', '#PROFILE_URL#'],
			[$email, $phone, $email, $phoneRaw, $data['profileUrl']],
			$text
		);
	}

	protected static function applyProfileMacrosHtml(string $text): string
	{
		$data = self::profileData();
		$email = $data['email'] !== '' ? $data['email'] : 'не указан';
		$phoneRaw = $data['phone'] !== '' ? $data['phone'] : 'не указан';
		$phone = $data['phone'] !== '' ? self::displayPhone($data['phone']) : 'не указан';
		$emailEsc = htmlspecialcharsbx($email);
		$phoneEsc = htmlspecialcharsbx($phone);
		$phoneRawEsc = htmlspecialcharsbx($phoneRaw);
		$urlEsc = htmlspecialcharsbx($data['profileUrl']);

		return str_replace(
			['#PROFILE_EMAIL#', '#PROFILE_PHONE#', '#PROFILE_EMAIL_RAW#', '#PROFILE_PHONE_RAW#', '#PROFILE_URL#'],
			[$emailEsc, $phoneEsc, $emailEsc, $phoneRawEsc, $urlEsc],
			$text
		);
	}
}
