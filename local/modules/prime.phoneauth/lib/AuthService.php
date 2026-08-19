<?php

namespace Prime\PhoneAuth;

use Bitrix\Main\Application;

class AuthService
{
	public const UF_CONFIRMED = 'UF_PHONE_CONFIRMED';
	public const UF_NORM = 'UF_PHONE_NORM';

	/** @return array<int,array<string,mixed>> */
	public static function findUsersByPhone(string $phone): array
	{
		$norm = Phone::national10($phone);
		if ($norm === '') {
			return [];
		}

		$connection = Application::getConnection();
		$rs = $connection->query(
			"SELECT ID, LOGIN, EMAIL, NAME, LAST_NAME, PERSONAL_PHONE
			FROM b_user
			WHERE ACTIVE='Y' AND PERSONAL_PHONE IS NOT NULL AND PERSONAL_PHONE != ''"
		);
		$matched = [];
		while ($row = $rs->fetch()) {
			if (Phone::national10((string)$row['PERSONAL_PHONE']) === $norm) {
				$matched[(int)$row['ID']] = $row;
			}
		}
		if ($matched === []) {
			return [];
		}

		foreach ($matched as $id => $row) {
			$full = \CUser::GetByID($id)->Fetch();
			if ($full) {
				$matched[$id][self::UF_CONFIRMED] = $full[self::UF_CONFIRMED] ?? 0;
				$matched[$id][self::UF_NORM] = $full[self::UF_NORM] ?? '';
			} else {
				$matched[$id][self::UF_CONFIRMED] = 0;
				$matched[$id][self::UF_NORM] = '';
			}
		}

		return array_values($matched);
	}

	public static function isConfirmed($value): bool
	{
		return $value === '1' || $value === 1 || $value === 'Y' || $value === true;
	}

	/**
	 * @param array<int,array<string,mixed>> $users
	 * @return array{status:string,users:array,confirmed:?array}
	 */
	public static function classify(array $users): array
	{
		$confirmed = [];
		foreach ($users as $user) {
			if (self::isConfirmed($user[self::UF_CONFIRMED] ?? 0)) {
				$confirmed[] = $user;
			}
		}

		if (count($confirmed) === 1) {
			return ['status' => 'login', 'users' => $users, 'confirmed' => $confirmed[0]];
		}
		if (count($users) === 1 && $confirmed === []) {
			return ['status' => 'need_verify', 'users' => $users, 'confirmed' => null];
		}
		if ($users === []) {
			return ['status' => 'not_found', 'users' => [], 'confirmed' => null];
		}

		return ['status' => 'duplicate', 'users' => $users, 'confirmed' => null];
	}

	public static function duplicateMessage(): string
	{
		return 'Этот номер уже есть в других аккаунтах. '
			. 'Если это вы — войдите по логину и паролю и подтвердите телефон в профиле. '
			. 'Если доступа к старым ящикам нет — можно завести новый аккаунт и жёстко подтвердить номер звонком с этого телефона, или подтвердить текущий, если вы уже вошли.';
	}

	/**
	 * @param array<int,array<string,mixed>> $users
	 * @return list<string>
	 */
	public static function accountEmails(array $users): array
	{
		$emails = [];
		$seen = [];
		foreach ($users as $user) {
			$email = trim((string)($user['EMAIL'] ?? ''));
			$login = trim((string)($user['LOGIN'] ?? ''));
			$label = $email !== '' ? $email : $login;
			if ($label === '') {
				continue;
			}
			$key = function_exists('mb_strtolower') ? mb_strtolower($label) : strtolower($label);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$emails[] = $label;
		}
		natcasesort($emails);

		return array_values($emails);
	}

	/**
	 * Проверка номера до регистрации / без звонка.
	 * @return array<string,mixed>
	 */
	public static function lookup(string $phoneRaw): array
	{
		$norm = Phone::national10($phoneRaw);
		if ($norm === '') {
			return [
				'ok' => false,
				'status' => 'invalid',
				'canConfirm' => false,
				'accounts' => [],
				'error' => 'Укажите корректный номер телефона РФ.',
			];
		}

		$users = self::findUsersByPhone($norm);
		$accounts = self::accountEmails($users);
		$confirmed = [];
		foreach ($users as $user) {
			if (self::isConfirmed($user[self::UF_CONFIRMED] ?? 0)) {
				$confirmed[] = $user;
			}
		}

		if ($users === []) {
			return [
				'ok' => true,
				'status' => 'free',
				'canConfirm' => true,
				'accounts' => [],
				'message' => 'Подтвердите номер звонком — после регистрации сможете входить по телефону.',
			];
		}

		if ($confirmed !== []) {
			return [
				'ok' => true,
				'status' => 'taken',
				'canConfirm' => false,
				'canClaim' => true,
				'accounts' => $accounts,
				'message' => self::duplicateMessage(),
			];
		}

		return [
			'ok' => true,
			'status' => 'exists',
			'canConfirm' => false,
			'canClaim' => true,
			'accounts' => $accounts,
			'message' => self::duplicateMessage(),
		];
	}

	/** @return array<string,mixed> */
	public static function startRegister(string $phoneRaw, bool $claim = false): array
	{
		if (!Config::isEnabled()) {
			return ['ok' => false, 'error' => 'Вход по телефону выключен.'];
		}

		global $USER;
		if (is_object($USER) && $USER->IsAuthorized()) {
			$norm = Phone::national10($phoneRaw);

			return $norm !== ''
				? self::startVerifyForUser((int)$USER->GetID(), $norm, $claim)
				: ['ok' => false, 'error' => 'Укажите корректный номер телефона РФ.'];
		}

		$lookup = self::lookup($phoneRaw);
		if (!$lookup['ok']) {
			return $lookup;
		}

		$hasAccounts = !empty($lookup['accounts']);
		if ($hasAccounts && !$claim) {
			return [
				'ok' => false,
				'status' => 'duplicate',
				'error' => (string)($lookup['message'] ?? self::duplicateMessage()),
				'accounts' => $lookup['accounts'],
				'canClaim' => true,
			];
		}

		if (!Config::isCallAuthEnabled() && !Config::isTestConfirm()) {
			return ['ok' => false, 'error' => 'Подтверждение звонком пока недоступно.'];
		}

		$norm = Phone::national10($phoneRaw);
		$challenge = Challenge::create(0, $norm, Challenge::TYPE_REGISTER);
		$message = $hasAccounts
			? 'Позвоните на указанный номер с этого телефона. Звонок подтвердит, что номер ваш: после регистрации вход по нему будет у нового аккаунта.'
			: 'Позвоните на указанный номер с этого телефона. После звонка завершите регистрацию — номер сохранится подтверждённым.';

		return self::challengePayload($challenge, 'need_verify', $norm, $message);
	}

	public static function releasePhoneFromOthers(int $keepUserId, string $norm): void
	{
		if ($norm === '' || $keepUserId <= 0) {
			return;
		}
		foreach (self::findUsersByPhone($norm) as $user) {
			$id = (int)($user['ID'] ?? 0);
			if ($id <= 0 || $id === $keepUserId) {
				continue;
			}
			if (!self::isConfirmed($user[self::UF_CONFIRMED] ?? 0)) {
				continue;
			}
			$ob = new \CUser();
			$ob->Update($id, [self::UF_CONFIRMED => 0]);
		}
	}

	public static function consumeRegisterToken(string $token, string $phoneNorm): bool
	{
		$row = Challenge::getByToken($token);
		if (!$row) {
			return false;
		}
		if ((string)$row['STATUS'] !== Challenge::STATUS_CONFIRMED) {
			return false;
		}
		if ((int)$row['USER_ID'] !== 0) {
			return false;
		}
		if ((string)$row['TYPE'] !== Challenge::TYPE_REGISTER) {
			return false;
		}
		if ((string)$row['PHONE'] !== $phoneNorm) {
			return false;
		}

		$confirmedAt = strtotime((string)$row['DATE_CONFIRM']) ?: strtotime((string)$row['DATE_CREATE']);
		if ($confirmedAt && (time() - $confirmedAt) > 3600) {
			return false;
		}

		Challenge::setStatus((int)$row['ID'], Challenge::STATUS_CANCELLED);

		return true;
	}

	public static function registerTokenMatches(string $token, string $phoneNorm): bool
	{
		$row = Challenge::getByToken($token);
		if (!$row) {
			return false;
		}
		if ((string)$row['STATUS'] !== Challenge::STATUS_CONFIRMED) {
			return false;
		}
		if ((int)$row['USER_ID'] !== 0) {
			return false;
		}
		if ((string)$row['TYPE'] !== Challenge::TYPE_REGISTER) {
			return false;
		}

		return (string)$row['PHONE'] === $phoneNorm;
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function start(string $phoneRaw, ?int $asUserId = null, bool $claim = false): array
	{
		if (!Config::isEnabled()) {
			return ['ok' => false, 'error' => 'Вход по телефону выключен.'];
		}

		$norm = Phone::national10($phoneRaw);
		if ($norm === '') {
			return ['ok' => false, 'error' => 'Укажите корректный номер телефона РФ.'];
		}

		if ($asUserId) {
			return self::startVerifyForUser($asUserId, $norm, $claim);
		}

		$users = self::findUsersByPhone($norm);
		$cls = self::classify($users);

		if ($cls['status'] === 'not_found') {
			return [
				'ok' => false,
				'status' => 'not_found',
				'error' => 'Номер не найден. Войдите по логину и паролю или зарегистрируйтесь.',
			];
		}

		if ($cls['status'] === 'duplicate') {
			return [
				'ok' => false,
				'status' => 'duplicate',
				'error' => self::duplicateMessage(),
				'accounts' => self::accountEmails($cls['users']),
			];
		}

		if (!Config::isCallAuthEnabled() && !Config::isTestConfirm()) {
			return ['ok' => false, 'error' => 'Подтверждение звонком пока недоступно. Войдите по логину и паролю.'];
		}

		if ($cls['status'] === 'login') {
			$user = $cls['confirmed'];
			$challenge = Challenge::create((int)$user['ID'], $norm, Challenge::TYPE_LOGIN);

			return self::challengePayload($challenge, 'login', $norm, 'Позвоните на указанный номер — звонок сбросится автоматически.');
		}

		$user = $cls['users'][0];
		$challenge = Challenge::create((int)$user['ID'], $norm, Challenge::TYPE_VERIFY);

		return self::challengePayload(
			$challenge,
			'need_verify',
			$norm,
			'Номер ещё не подтверждён. Позвоните на указанный номер с этого телефона — так мы подтвердим его, и можно будет входить по звонку.'
		);
	}

	/** @return array<string,mixed> */
	protected static function startVerifyForUser(int $userId, string $norm, bool $claim = false): array
	{
		global $USER;
		if (!is_object($USER) || !$USER->IsAuthorized() || (int)$USER->GetID() !== $userId) {
			return ['ok' => false, 'error' => 'Нужно войти в аккаунт, чтобы подтвердить номер.'];
		}

		$rs = \CUser::GetByID($userId);
		$row = $rs ? $rs->Fetch() : false;
		if (!$row) {
			return ['ok' => false, 'error' => 'Пользователь не найден.'];
		}

		$phoneNorm = Phone::national10((string)$row['PERSONAL_PHONE']);
		if ($phoneNorm !== $norm) {
			$user = new \CUser();
			if (!$user->Update($userId, ['PERSONAL_PHONE' => Phone::format('7' . $norm)])) {
				$err = trim((string)$user->LAST_ERROR);
				return ['ok' => false, 'error' => $err !== '' ? $err : 'Не удалось сохранить номер.'];
			}
		}

		$others = [];
		$confirmedOthers = [];
		foreach (self::findUsersByPhone($norm) as $u) {
			if ((int)$u['ID'] === $userId) {
				continue;
			}
			$others[] = $u;
			if (self::isConfirmed($u[self::UF_CONFIRMED] ?? 0)) {
				$confirmedOthers[] = $u;
			}
		}
		if ($confirmedOthers !== [] && !$claim) {
			return [
				'ok' => false,
				'status' => 'duplicate',
				'error' => 'Этот номер уже подтверждён в другом аккаунте. Укажите другой телефон или войдите в тот аккаунт.',
				'accounts' => self::accountEmails($others),
				'canClaim' => true,
			];
		}

		if (self::isUserPhoneConfirmed($userId)) {
			return ['ok' => true, 'status' => 'already', 'message' => 'Номер уже подтверждён.'];
		}

		if (!Config::isCallAuthEnabled() && !Config::isTestConfirm()) {
			return ['ok' => false, 'error' => 'Подтверждение звонком пока недоступно.'];
		}

		$challenge = Challenge::create($userId, $norm, Challenge::TYPE_VERIFY);

		return self::challengePayload(
			$challenge,
			'need_verify',
			$norm,
			'Позвоните на указанный номер с этого телефона — так мы подтвердим номер в профиле.'
		);
	}

	/**
	 * @param array{token:string,expiresAt:int,id:int} $challenge
	 * @return array<string,mixed>
	 */
	protected static function challengePayload(array $challenge, string $status, string $norm, string $message): array
	{
		return [
			'ok' => true,
			'status' => $status,
			'token' => $challenge['token'],
			'expiresAt' => $challenge['expiresAt'],
			'phone' => Phone::format('7' . $norm),
			'callNumber' => Config::getVerifyNumberDisplay(),
			'testConfirm' => Config::isTestConfirm(),
			'message' => $message,
		];
	}

	public static function isUserPhoneConfirmed(int $userId): bool
	{
		$rs = \CUser::GetByID($userId);
		$row = $rs ? $rs->Fetch() : false;
		if (!$row) {
			return false;
		}

		return self::isConfirmed($row[self::UF_CONFIRMED] ?? 0);
	}

	public static function markConfirmed(int $userId, string $norm): void
	{
		$user = new \CUser();
		$user->Update($userId, [
			self::UF_CONFIRMED => 1,
			self::UF_NORM => $norm,
		]);
		self::releasePhoneFromOthers($userId, $norm);
	}

	/** @return array<string,mixed> */
	public static function status(string $token): array
	{
		$row = Challenge::getByToken($token);
		if (!$row) {
			return ['ok' => false, 'status' => 'missing', 'error' => 'Запрос входа не найден.'];
		}
		Challenge::expireIfNeeded($row);

		$status = (string)$row['STATUS'];
		if ($status === Challenge::STATUS_PENDING) {
			return ['ok' => true, 'status' => 'pending'];
		}
		if ($status === Challenge::STATUS_EXPIRED) {
			return ['ok' => false, 'status' => 'expired', 'error' => 'Время истекло. Запросите вход ещё раз.'];
		}
		if ($status === Challenge::STATUS_CANCELLED) {
			return ['ok' => false, 'status' => 'cancelled', 'error' => 'Запрос отменён.'];
		}
		if ($status !== Challenge::STATUS_CONFIRMED) {
			return ['ok' => false, 'status' => $status, 'error' => 'Запрос ещё не подтверждён.'];
		}

		$userId = (int)$row['USER_ID'];
		if ($userId <= 0) {
			return [
				'ok' => true,
				'status' => 'confirmed',
				'redirect' => '',
			];
		}

		global $USER;
		if (!is_object($USER) || !$USER->IsAuthorized() || (int)$USER->GetID() !== $userId) {
			$USER->Authorize($userId);
		}

		return [
			'ok' => true,
			'status' => 'confirmed',
			'redirect' => '/personal/',
		];
	}

	public static function confirmByCallerPhone(string $callerRaw): array
	{
		$norm = Phone::national10($callerRaw);
		if ($norm === '') {
			return ['ok' => false, 'message' => 'Invalid caller phone'];
		}

		$pending = Challenge::pendingForPhone($norm);
		if ($pending === []) {
			return ['ok' => false, 'message' => 'No matching pending call login'];
		}

		$row = $pending[0];
		$userId = (int)$row['USER_ID'];
		if ((string)$row['TYPE'] === Challenge::TYPE_VERIFY && $userId > 0) {
			self::markConfirmed($userId, $norm);
		}

		Challenge::setStatus((int)$row['ID'], Challenge::STATUS_CONFIRMED);

		return ['ok' => true, 'message' => 'Call login confirmed'];
	}

	public static function testConfirm(string $token): array
	{
		if (!Config::isTestConfirm()) {
			return ['ok' => false, 'error' => 'Тестовое подтверждение выключено.'];
		}

		$row = Challenge::getByToken($token);
		if (!$row) {
			return ['ok' => false, 'error' => 'Запрос не найден.'];
		}
		Challenge::expireIfNeeded($row);
		if ((string)$row['STATUS'] !== Challenge::STATUS_PENDING) {
			return ['ok' => false, 'error' => 'Запрос уже обработан.'];
		}

		$norm = (string)$row['PHONE'];
		$userId = (int)$row['USER_ID'];
		if ((string)$row['TYPE'] === Challenge::TYPE_VERIFY && $userId > 0) {
			self::markConfirmed($userId, $norm);
		}
		Challenge::setStatus((int)$row['ID'], Challenge::STATUS_CONFIRMED);

		return self::status($token);
	}

	/** @return array{phone:string,confirmed:bool,duplicate:bool,accounts:list<string>} */
	public static function profileState(?int $userId = null): array
	{
		global $USER;
		if ($userId === null) {
			$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
		}
		if ($userId <= 0) {
			return ['phone' => '', 'confirmed' => false, 'duplicate' => false, 'accounts' => []];
		}

		$rs = \CUser::GetByID($userId);
		$row = $rs ? $rs->Fetch() : false;
		if (!$row) {
			return ['phone' => '', 'confirmed' => false, 'duplicate' => false, 'accounts' => []];
		}

		$phone = trim((string)$row['PERSONAL_PHONE']);
		$norm = Phone::national10($phone);
		$confirmed = self::isConfirmed($row[self::UF_CONFIRMED] ?? 0);
		$duplicate = false;
		$accounts = [];
		if ($norm !== '') {
			$all = self::findUsersByPhone($norm);
			$duplicate = count($all) > 1;
			if ($duplicate) {
				$accounts = self::accountEmails($all);
			}
		}

		return [
			'phone' => $phone,
			'confirmed' => $confirmed,
			'duplicate' => $duplicate,
			'accounts' => $accounts,
		];
	}

	public const PROMPT_SNOOZE_OPTION = 'profile_phone_modal_snooze';
	public const PROMPT_SNOOZE_SECONDS = 1209600;

	public static function isPromptSnoozed(): bool
	{
		global $USER;
		if (!is_object($USER) || !$USER->IsAuthorized()) {
			return true;
		}

		return (int)\CUserOptions::GetOption(Config::MODULE_ID, self::PROMPT_SNOOZE_OPTION, '0') > time();
	}

	public static function snoozePrompt(int $seconds = self::PROMPT_SNOOZE_SECONDS): int
	{
		$until = time() + max(1, $seconds);
		\CUserOptions::SetOption(Config::MODULE_ID, self::PROMPT_SNOOZE_OPTION, (string)$until);

		return $until;
	}

	public static function hasAlertsProfileUi(): bool
	{
		try {
			if (!\Bitrix\Main\Loader::includeModule('prime.alerts')) {
				return false;
			}
			if (!\Prime\Alerts\Config::isEnabled() || !\Prime\Alerts\Config::isYes('profile_banner', 'Y')) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}

		return true;
	}

	public static function isPersonalPage(): bool
	{
		try {
			$path = (string)\Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPageDirectory();
			return $path === '/personal' || strpos($path, '/personal/') === 0;
		} catch (\Throwable $e) {
			return false;
		}
	}
}
