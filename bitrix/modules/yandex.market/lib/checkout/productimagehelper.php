<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;

/**
 * Картинка товара: стандартные поля инфоблока или свойство (тип «файл» / строка-URL).
 */
final class ProductImageHelper
{
    private const MODULE_ID = 'yandex.market';

    public static function resolveUrl(int $elementId, ?int $iblockId, array $elementRow): ?string
    {
        $iblockId = (int)($iblockId ?: ($elementRow['IBLOCK_ID'] ?? 0));
        $parentId = self::getParentProductId($elementId);
        $parentRow = $parentId > 0 ? self::fetchElementPicturesRow($parentId) : null;

        $url = self::urlFromStandardPictures($elementRow);
        if ($url !== null && $url !== '') {
            return $url;
        }
        if ($parentRow) {
            $url = self::urlFromStandardPictures($parentRow);
            if ($url !== null && $url !== '') {
                return $url;
            }
        }

        $propCode = trim((string) Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_IMAGE_PROPERTY', ''));
        if ($propCode === '') {
            return null;
        }

        if ($iblockId > 0) {
            $url = self::urlFromProperty($iblockId, $elementId, $propCode);
            if ($url !== null && $url !== '') {
                return $url;
            }
        }
        if ($parentRow) {
            $parentIblock = (int)($parentRow['IBLOCK_ID'] ?? 0);
            if ($parentIblock > 0 && $parentId > 0) {
                $url = self::urlFromProperty($parentIblock, $parentId, $propCode);
                if ($url !== null && $url !== '') {
                    return $url;
                }
            }
        }

        return null;
    }

    private static function getParentProductId(int $elementId): int
    {
        if (!\Bitrix\Main\Loader::includeModule('catalog')) {
            return 0;
        }
        $info = \CCatalogSku::GetProductInfo($elementId);
        return (int)($info['ID'] ?? 0);
    }

    private static function fetchElementPicturesRow(int $id): ?array
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return null;
        }
        $res = \CIBlockElement::GetList(
            [],
            ['ID' => $id],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
        );
        $row = $res->GetNext();
        return is_array($row) ? $row : null;
    }

    private static function urlFromStandardPictures(array $row): ?string
    {
        $imageId = !empty($row['DETAIL_PICTURE']) ? $row['DETAIL_PICTURE'] : ($row['PREVIEW_PICTURE'] ?? null);
        if (empty($imageId)) {
            return null;
        }
        if (is_array($imageId)) {
            $imageId = $imageId['ID'] ?? $imageId['VALUE'] ?? null;
        }
        if (empty($imageId)) {
            return null;
        }
        $image = \CFile::GetFileArray($imageId);
        if (!$image || empty($image['SRC'])) {
            return null;
        }
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        
        if (defined('BX_IMG_SERVER') || strpos($image['SRC'], 'http') === 0) {
            return $image['SRC'];
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return UrlManager::getInstance()->getHostUrl() . $image['SRC'];
        }
        return $protocol . '://' . $host . $image['SRC'];
    }

    private static function urlFromProperty(int $iblockId, int $elementId, string $propCode): ?string
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return null;
        }
        $propMeta = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propCode])->Fetch();
        if (!$propMeta) {
            return null;
        }
        $type = (string)($propMeta['PROPERTY_TYPE'] ?? '');
        $userType = (string)($propMeta['USER_TYPE'] ?? '');

        $db = \CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => $propCode]);
        while ($propRow = $db->Fetch()) {
            $url = self::propertyValueRowToUrl($propRow, $type, $userType);
            if ($url !== null && $url !== '') {
                return $url;
            }
        }
        return null;
    }

    private static function propertyValueRowToUrl(array $propRow, string $propertyType, string $userType): ?string
    {
        $value = $propRow['VALUE'] ?? null;
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if ($propertyType === 'F') {
            $fileIds = [];
            if (is_array($value)) {
                if (isset($value['ID'])) {
                    $fileIds[] = (int)$value['ID'];
                } else {
                    foreach ($value as $v) {
                        if (is_array($v)) {
                            $id = (int)($v['ID'] ?? $v['VALUE'] ?? 0);
                        } else {
                            $id = (int)$v;
                        }
                        if ($id > 0) {
                            $fileIds[] = $id;
                        }
                    }
                }
            } else {
                $raw = trim((string)$value);
                if ($raw !== '' && strpos($raw, ',') !== false) {
                    foreach (explode(',', $raw) as $part) {
                        $id = (int)trim($part);
                        if ($id > 0) {
                            $fileIds[] = $id;
                        }
                    }
                } else {
                    $fileIds[] = (int)$value;
                }
            }
            foreach ($fileIds as $fileId) {
                if ($fileId <= 0) {
                    continue;
                }
                $path = \CFile::GetPath($fileId);
                if ($path === '' || $path === false) {
                    $file = \CFile::GetFileArray($fileId);
                    $path = $file['SRC'] ?? '';
                }
                if ($path === '' || $path === false) {
                    continue;
                }
                if (preg_match('#^https?://#i', $path)) {
                    return $path;
                }
                return UrlManager::getInstance()->getHostUrl() . (strpos($path, '/') === 0 ? $path : '/' . $path);
            }
            return null;
        }

        if ($propertyType === 'S') {
            if (is_array($value)) {
                $value = $value['TEXT'] ?? $value['VALUE'] ?? reset($value);
            }
            $str = trim((string)$value);
            if ($str === '') {
                return null;
            }
            if (strpos($str, ',') !== false && preg_match('/^[\d,\s]+$/', $str)) {
                foreach (explode(',', $str) as $part) {
                    $fid = (int)trim($part);
                    if ($fid <= 0) {
                        continue;
                    }
                    $path = \CFile::GetPath($fid);
                    if ($path !== '' && $path !== false) {
                        return UrlManager::getInstance()->getHostUrl() . (strpos($path, '/') === 0 ? $path : '/' . $path);
                    }
                }
                return null;
            }
            if (preg_match('#^https?://#i', $str)) {
                return $str;
            }
            if (isset($str[0]) && $str[0] === '/') {
                return UrlManager::getInstance()->getHostUrl() . $str;
            }
            if (ctype_digit($str) || (is_numeric($str) && (int)$str > 0)) {
                $fid = (int)$str;
                $path = \CFile::GetPath($fid);
                if ($path !== '' && $path !== false) {
                    return UrlManager::getInstance()->getHostUrl() . (strpos($path, '/') === 0 ? $path : '/' . $path);
                }
            }
            return UrlManager::getInstance()->getHostUrl() . '/' . ltrim($str, '/');
        }

        return null;
    }
}
