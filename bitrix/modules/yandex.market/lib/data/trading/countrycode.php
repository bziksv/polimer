<?php

namespace Yandex\Market\Data\Trading;

class CountryCode
{
    public static function formatMarkingCode(string $markingCode)
    {
        $formatted = lcfirst(str_replace('_', '', ucwords(strtolower($markingCode), '_')));

        return $formatted !== '' ? $formatted : $markingCode;
    }

    public static function isValidCountryCode(string $value): bool
    {
        return preg_match('/^[A-Z]{2}$/', $value) === 1;
    }
}