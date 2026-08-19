<?php

namespace Prime\PhoneAuth;

class Phone
{
	public static function digits(string $phone): string
	{
		return preg_replace('/\D+/', '', $phone) ?? '';
	}

	/** 11 цифр с 7, либо пусто */
	public static function e164Digits(string $phone): string
	{
		$digits = self::digits($phone);
		if (strlen($digits) === 11 && $digits[0] === '8') {
			$digits = '7' . substr($digits, 1);
		}
		if (strlen($digits) === 10) {
			$digits = '7' . $digits;
		}
		if (preg_match('/^7[3-9]\d{9}$/', $digits)) {
			return $digits;
		}

		return '';
	}

	/** 10-значный национальный номер РФ */
	public static function national10(string $phone): string
	{
		$e164 = self::e164Digits($phone);

		return $e164 !== '' ? substr($e164, 1) : '';
	}

	public static function isValid(string $phone): bool
	{
		return self::e164Digits($phone) !== '';
	}

	public static function format(string $phone): string
	{
		$e164 = self::e164Digits($phone);
		if ($e164 === '' || !preg_match('/^7(\d{3})(\d{3})(\d{2})(\d{2})$/', $e164, $m)) {
			return trim($phone);
		}

		return '+7-' . $m[1] . '-' . $m[2] . '-' . $m[3] . '-' . $m[4];
	}

	public static function match(string $a, string $b): bool
	{
		$na = self::national10($a);
		$nb = self::national10($b);

		return $na !== '' && $na === $nb;
	}
}
