<?php
namespace Yandex\Market\Checkout;

/**
 * Хелпер для работы с картой цветов (значение → HEX).
 */
class ColorMapHelper
{
    public static function normalizeColorKey($s)
    {
        $s = trim((string)$s);
        $s = str_replace(['ё', 'Ё'], ['е', 'Е'], $s);
        return $s;
    }

    public static function getHexFromColorMapOrNull(array $colorMap, $valueText)
    {
        if (empty($colorMap)) {
            return null;
        }
        $valueText = (string)$valueText;
        $norm = self::normalizeColorKey($valueText);
        $valueLower = mb_strtolower($valueText, 'UTF-8');
        $normLower = mb_strtolower($norm, 'UTF-8');

        if (isset($colorMap[$valueText])) {
            $hex = (string)$colorMap[$valueText];
            return $hex !== '' ? (strpos($hex, '#') === 0 ? $hex : '#' . $hex) : null;
        }
        if (isset($colorMap[$norm])) {
            $hex = (string)$colorMap[$norm];
            return $hex !== '' ? (strpos($hex, '#') === 0 ? $hex : '#' . $hex) : null;
        }
        if (isset($colorMap[$valueLower])) {
            $hex = (string)$colorMap[$valueLower];
            return $hex !== '' ? (strpos($hex, '#') === 0 ? $hex : '#' . $hex) : null;
        }
        if (isset($colorMap[$normLower])) {
            $hex = (string)$colorMap[$normLower];
            return $hex !== '' ? (strpos($hex, '#') === 0 ? $hex : '#' . $hex) : null;
        }
        foreach ($colorMap as $key => $hex) {
            if (mb_strtolower((string)$key, 'UTF-8') === $valueLower) {
                $hex = (string)$hex;
                return $hex !== '' ? (strpos($hex, '#') === 0 ? $hex : '#' . $hex) : null;
            }
        }
        return null;
    }

    public static function normalizeColorMapKeys(array $colorMap)
    {
        $result = $colorMap;
        foreach ($colorMap as $key => $hex) {
            $norm = self::normalizeColorKey($key);
            if ($norm !== $key && !isset($result[$norm])) {
                $result[$norm] = $hex;
            }
        }
        return $result;
    }

    public static function hasUnmappedColorValues(array $colorValues, array $colorMap)
    {
        if (empty($colorMap)) {
            return true;
        }
        foreach ($colorValues as $textValue) {
            if (self::getHexFromColorMapOrNull($colorMap, $textValue) === null) {
                return true;
            }
        }
        return false;
    }
}
