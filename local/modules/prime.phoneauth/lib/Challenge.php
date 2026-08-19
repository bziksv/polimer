<?php

namespace Prime\PhoneAuth;

use Bitrix\Main\Application;

class Challenge
{
	public const TABLE = 'b_prime_phoneauth_challenge';
	public const TTL_SECONDS = 300;
	public const TYPE_LOGIN = 'login';
	public const TYPE_VERIFY = 'verify';
	public const TYPE_REGISTER = 'register';
	public const STATUS_PENDING = 'pending';
	public const STATUS_CONFIRMED = 'confirmed';
	public const STATUS_EXPIRED = 'expired';
	public const STATUS_CANCELLED = 'cancelled';

	public static function installTable(): void
	{
		$connection = Application::getConnection();
		if ($connection->isTableExists(self::TABLE)) {
			return;
		}

		$connection->queryExecute("
			CREATE TABLE " . self::TABLE . " (
				ID int NOT NULL AUTO_INCREMENT,
				TOKEN char(32) NOT NULL,
				USER_ID int NOT NULL,
				PHONE varchar(20) NOT NULL,
				TYPE varchar(16) NOT NULL DEFAULT 'login',
				STATUS varchar(16) NOT NULL DEFAULT 'pending',
				DATE_CREATE datetime NOT NULL,
				DATE_EXPIRE datetime NOT NULL,
				DATE_CONFIRM datetime NULL,
				PRIMARY KEY (ID),
				UNIQUE KEY ux_prime_phoneauth_token (TOKEN),
				KEY ix_prime_phoneauth_status_phone (STATUS, PHONE),
				KEY ix_prime_phoneauth_user (USER_ID)
			)
		");
	}

	/** @return array<string,mixed>|null */
	public static function getByToken(string $token): ?array
	{
		$token = preg_replace('/[^a-f0-9]/', '', strtolower($token)) ?? '';
		if (strlen($token) !== 32) {
			return null;
		}

		$connection = Application::getConnection();
		$row = $connection->query(
			"SELECT * FROM " . self::TABLE . " WHERE TOKEN='" . $connection->getSqlHelper()->forSql($token) . "' LIMIT 1"
		)->fetch();

		return $row ?: null;
	}

	public static function cancelPendingForUser(int $userId): void
	{
		if ($userId <= 0) {
			return;
		}
		$connection = Application::getConnection();
		$connection->queryExecute(
			"UPDATE " . self::TABLE . " SET STATUS='" . self::STATUS_CANCELLED . "'"
			. " WHERE USER_ID=" . $userId
			. " AND STATUS='" . self::STATUS_PENDING . "'"
		);
	}

	public static function cancelPendingGuestForPhone(string $phoneNational10): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->queryExecute(
			"UPDATE " . self::TABLE . " SET STATUS='" . self::STATUS_CANCELLED . "'"
			. " WHERE USER_ID=0"
			. " AND PHONE='" . $helper->forSql($phoneNational10) . "'"
			. " AND STATUS='" . self::STATUS_PENDING . "'"
		);
	}

	/** @return array{token:string,expiresAt:int,id:int} */
	public static function create(int $userId, string $phoneNational10, string $type): array
	{
		if ($userId > 0) {
			self::cancelPendingForUser($userId);
		} else {
			self::cancelPendingGuestForPhone($phoneNational10);
		}

		$token = bin2hex(random_bytes(16));
		$now = time();
		$expire = $now + self::TTL_SECONDS;
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->queryExecute(
			"INSERT INTO " . self::TABLE . " (TOKEN, USER_ID, PHONE, TYPE, STATUS, DATE_CREATE, DATE_EXPIRE)"
			. " VALUES ("
			. "'" . $helper->forSql($token) . "',"
			. $userId . ","
			. "'" . $helper->forSql($phoneNational10) . "',"
			. "'" . $helper->forSql($type) . "',"
			. "'" . self::STATUS_PENDING . "',"
			. "'" . date('Y-m-d H:i:s', $now) . "',"
			. "'" . date('Y-m-d H:i:s', $expire) . "'"
			. ")"
		);

		return [
			'token' => $token,
			'expiresAt' => $expire,
			'id' => (int)$connection->getInsertedId(),
		];
	}

	public static function expireIfNeeded(array &$row): void
	{
		if (($row['STATUS'] ?? '') !== self::STATUS_PENDING) {
			return;
		}
		$expire = strtotime((string)$row['DATE_EXPIRE']);
		if ($expire !== false && $expire < time()) {
			self::setStatus((int)$row['ID'], self::STATUS_EXPIRED);
			$row['STATUS'] = self::STATUS_EXPIRED;
		}
	}

	public static function setStatus(int $id, string $status): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$extra = '';
		if ($status === self::STATUS_CONFIRMED) {
			$extra = ", DATE_CONFIRM='" . date('Y-m-d H:i:s') . "'";
		}
		$connection->queryExecute(
			"UPDATE " . self::TABLE . " SET STATUS='" . $helper->forSql($status) . "'" . $extra
			. " WHERE ID=" . $id
		);
	}

	/** @return array<int,array<string,mixed>> */
	public static function pendingForPhone(string $phoneNational10): array
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$now = date('Y-m-d H:i:s');
		$rs = $connection->query(
			"SELECT * FROM " . self::TABLE
			. " WHERE STATUS='" . self::STATUS_PENDING . "'"
			. " AND PHONE='" . $helper->forSql($phoneNational10) . "'"
			. " AND DATE_EXPIRE > '" . $helper->forSql($now) . "'"
			. " ORDER BY ID DESC"
		);
		$rows = [];
		while ($row = $rs->fetch()) {
			$rows[] = $row;
		}

		return $rows;
	}
}
