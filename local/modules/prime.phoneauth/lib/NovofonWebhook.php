<?php

namespace Prime\PhoneAuth;

class NovofonWebhook
{
	/** @var string[] */
	protected static $callerKeys = [
		'contact_phone_number',
		'caller_number',
		'caller_id',
		'calling_number',
		'numa',
		'from',
		'phone',
		'contact',
		'clid',
		'ani',
	];

	/** @var string[] */
	protected static $calledKeys = [
		'virtual_phone_number',
		'called_number',
		'numb',
		'to',
		'destination',
	];

	/** @return array<string,string> */
	public static function parseParams(): array
	{
		$bag = [];
		foreach ($_GET as $key => $value) {
			if (is_string($value) || is_numeric($value)) {
				$bag[strtolower((string)$key)] = (string)$value;
			}
		}
		foreach ($_POST as $key => $value) {
			if (is_string($value) || is_numeric($value)) {
				$bag[strtolower((string)$key)] = (string)$value;
			}
		}

		$raw = file_get_contents('php://input');
		if (is_string($raw) && $raw !== '') {
			$json = json_decode($raw, true);
			if (is_array($json)) {
				self::flatten($json, $bag);
			}
		}

		return $bag;
	}

	/** @param array<string,mixed> $input @param array<string,string> $bag */
	protected static function flatten(array $input, array &$bag, int $depth = 0): void
	{
		if ($depth > 4) {
			return;
		}
		foreach ($input as $key => $value) {
			if ($value === null) {
				continue;
			}
			$k = strtolower((string)$key);
			if (is_string($value) || is_numeric($value) || is_bool($value)) {
				$bag[$k] = (string)$value;
			} elseif (is_array($value)) {
				self::flatten($value, $bag, $depth + 1);
			}
		}
	}

	/** @param array<string,string> $bag */
	protected static function pick(array $bag, array $keys): string
	{
		foreach ($keys as $key) {
			if (!empty($bag[$key]) && trim($bag[$key]) !== '') {
				return trim($bag[$key]);
			}
		}

		return '';
	}

	/** @return array{ok:bool,status?:int,message:string} */
	public static function verifyRequest(array $bag): array
	{
		$got = (string)($bag['secret'] ?? '');
		if (!Config::isValidWebhookSecret($got)) {
			return ['ok' => false, 'status' => 401, 'message' => 'Invalid webhook secret'];
		}

		$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
		$ip = preg_replace('/^::ffff:/', '', $ip) ?? $ip;
		if ($ip !== '' && $ip !== '127.0.0.1' && $ip !== '::1') {
			$allowed = Config::getWebhookIps();
			if ($allowed !== [] && !in_array($ip, $allowed, true)) {
				$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
				$local = in_array($host, ['localhost:8089', '127.0.0.1:8089', 'localhost', '127.0.0.1'], true);
				if (!$local) {
					return ['ok' => false, 'status' => 403, 'message' => 'Forbidden IP'];
				}
			}
		}

		return ['ok' => true, 'message' => 'ok'];
	}

	/** @param array<string,string> $bag @return array{ok:bool,message:string} */
	public static function handle(array $bag): array
	{
		$caller = self::pick($bag, self::$callerKeys);
		$called = self::pick($bag, self::$calledKeys);
		$verify = Config::getVerifyNumberDigits();

		if ($caller === '') {
			return ['ok' => false, 'message' => 'No caller number in webhook'];
		}

		if ($verify !== '' && $called !== '') {
			$calledDigits = Phone::digits($called);
			if ($calledDigits !== '' && !Phone::match($calledDigits, $verify)) {
				return ['ok' => false, 'message' => 'Call to unexpected number'];
			}
		}

		return AuthService::confirmByCallerPhone($caller);
	}
}
