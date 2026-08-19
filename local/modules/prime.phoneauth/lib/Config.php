<?php

namespace Prime\PhoneAuth;

class Config
{
	public const MODULE_ID = 'prime.phoneauth';

	public static function get(string $name, string $default = ''): string
	{
		return (string) \Bitrix\Main\Config\Option::get(self::MODULE_ID, $name, $default);
	}

	public static function isYes(string $name, string $default = 'N'): bool
	{
		return self::get($name, $default) === 'Y';
	}

	public static function isEnabled(): bool
	{
		return self::isYes('enabled', 'Y');
	}

	public static function isCallAuthEnabled(): bool
	{
		if (!self::isEnabled()) {
			return false;
		}

		return self::isYes('call_auth_enabled', 'N') && self::getVerifyNumberDigits() !== '';
	}

	public static function isTestConfirm(): bool
	{
		if (!self::isEnabled()) {
			return false;
		}
		if (self::isYes('test_confirm', 'N')) {
			return true;
		}

		$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

		return in_array($host, ['localhost:8089', '127.0.0.1:8089', 'localhost', '127.0.0.1'], true);
	}

	public static function getVerifyNumberDigits(): string
	{
		$digits = preg_replace('/\D+/', '', self::get('verify_number', '')) ?? '';

		return strlen($digits) >= 10 ? $digits : '';
	}

	public static function getVerifyNumberDisplay(): string
	{
		$digits = self::getVerifyNumberDigits();
		if ($digits === '') {
			return '';
		}

		return Phone::format($digits);
	}

	public static function currentSiteId(): string
	{
		try {
			$lid = (string)\Bitrix\Main\Context::getCurrent()->getSite();
			if ($lid !== '') {
				return $lid;
			}
		} catch (\Throwable $e) {
			// ignore
		}

		return defined('SITE_ID') ? (string)SITE_ID : 's1';
	}

	/** @return array<int,array{lid:string,name:string,host:string}> */
	public static function sites(): array
	{
		$out = [];
		$rs = \CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
		while ($row = $rs->Fetch()) {
			$host = trim((string)($row['SERVER_NAME'] ?? ''));
			$out[] = [
				'lid' => (string)$row['LID'],
				'name' => (string)($row['NAME'] ?? $row['LID']),
				'host' => $host,
			];
		}

		return $out ?: [['lid' => 's1', 'name' => 's1', 'host' => '']];
	}

	public static function getWebhookSecret(?string $siteId = null): string
	{
		$siteId = $siteId ?: self::currentSiteId();
		$secret = trim((string)\Bitrix\Main\Config\Option::get(self::MODULE_ID, 'webhook_secret', '', $siteId));
		if ($secret === '') {
			$secret = trim((string)\Bitrix\Main\Config\Option::get(self::MODULE_ID, 'webhook_secret', ''));
		}
		if ($secret === '') {
			$secret = self::generateWebhookSecret($siteId);
		}

		return $secret;
	}

	public static function generateWebhookSecret(?string $siteId = null): string
	{
		$siteId = $siteId ?: self::currentSiteId();
		$secret = bin2hex(random_bytes(16));
		\Bitrix\Main\Config\Option::set(self::MODULE_ID, 'webhook_secret', $secret, $siteId);

		return $secret;
	}

	public static function setWebhookSecret(string $secret, ?string $siteId = null): void
	{
		$siteId = $siteId ?: self::currentSiteId();
		\Bitrix\Main\Config\Option::set(self::MODULE_ID, 'webhook_secret', $secret, $siteId);
	}

	/** @return string[] */
	public static function allWebhookSecrets(): array
	{
		$secrets = [];
		$global = trim((string)\Bitrix\Main\Config\Option::get(self::MODULE_ID, 'webhook_secret', ''));
		if ($global !== '') {
			$secrets[] = $global;
		}
		foreach (self::sites() as $site) {
			$s = self::getWebhookSecret($site['lid']);
			if ($s !== '') {
				$secrets[] = $s;
			}
		}

		return array_values(array_unique($secrets));
	}

	public static function isValidWebhookSecret(string $got): bool
	{
		if ($got === '') {
			return false;
		}
		foreach (self::allWebhookSecrets() as $secret) {
			if (hash_equals($secret, $got)) {
				return true;
			}
		}

		return false;
	}

	public static function webhookPath(?string $siteId = null): string
	{
		$secret = self::getWebhookSecret($siteId);

		return '/ajax/phoneauth-webhook.php?secret=' . rawurlencode($secret);
	}

	public static function webhookUrl(): string
	{
		return self::webhookPath();
	}

	public static function absoluteWebhookUrl(string $host, ?string $siteId = null): string
	{
		$host = preg_replace('#^https?://#i', '', trim($host)) ?? '';
		$host = rtrim($host, '/');
		if ($host === '') {
			$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
		}
		$local = preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);
		$scheme = $local ? 'http' : 'https';

		return $scheme . '://' . $host . self::webhookPath($siteId);
	}

	/**
	 * URL для кабинета Novofon: домен сайта из Битрикса, не текущий localhost.
	 *
	 * @return array<int,array{lid:string,name:string,url:string,secret:string}>
	 */
	public static function novofonWebhookUrls(): array
	{
		$rows = [];
		foreach (self::sites() as $site) {
			$host = $site['host'] !== '' ? $site['host'] : (string)($_SERVER['HTTP_HOST'] ?? '');
			$rows[] = [
				'lid' => $site['lid'],
				'name' => $site['name'],
				'url' => self::absoluteWebhookUrl($host, $site['lid']),
				'secret' => self::getWebhookSecret($site['lid']),
			];
		}

		return $rows;
	}

	/** @return string[] */
	public static function getWebhookIps(): array
	{
		$raw = self::get('webhook_ips', '37.139.38.215');
		$ips = preg_split('/[\s,;]+/', $raw) ?: [];
		$out = [];
		foreach ($ips as $ip) {
			$ip = trim($ip);
			if ($ip !== '') {
				$out[] = $ip;
			}
		}

		return $out ?: ['37.139.38.215'];
	}
}
