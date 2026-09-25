<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

/**
 * Резолв внешнего идентификатора товара (id из API) во внутренний ID элемента инфоблока и обратно.
 */
class ProductIdResolver
{
    const MODULE_ID = 'yandex.market';
    const FIELD_ID = 'ID';
    const FIELD_XML_ID = 'XML_ID';
    const FIELD_CODE = 'CODE';
    const FIELD_PROPERTY = 'PROPERTY';

    private static $lastDebug = [];

    private static function getSearchIblockIds()
    {
        $productIblockId = (int) Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_IBLOCK_ID', 0);
        $useSku = Option::get(self::MODULE_ID, 'USE_SKU', 'N') === 'Y';
        $skuIblockId = (int) Option::get(self::MODULE_ID, 'SKU_IBLOCK_ID', 0);

        $ids = [];
        if ($useSku && $skuIblockId > 0) {
            $ids[] = $skuIblockId;
        }
        if ($productIblockId > 0 && !in_array($productIblockId, $ids, true)) {
            $ids[] = $productIblockId;
        }
        return $ids;
    }

    public static function resolveToInternalId($externalId)
    {
        self::$lastDebug = ['externalId' => $externalId, 'steps' => []];

        if ($externalId === '' || $externalId === null) {
            self::$lastDebug['steps'][] = 'early_return: empty';
            return null;
        }

        $fieldRaw = Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_ID_FIELD', self::FIELD_ID);
        $field = trim((string) $fieldRaw);
        if (!in_array($field, [self::FIELD_ID, self::FIELD_XML_ID, self::FIELD_CODE, self::FIELD_PROPERTY], true)) {
            $field = self::FIELD_ID;
        }
        self::$lastDebug['field'] = $field;
        self::$lastDebug['field_raw_from_db'] = $fieldRaw;
        self::$lastDebug['YAKIT_PRODUCT_IBLOCK_ID_raw'] = Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_IBLOCK_ID', '');
        self::$lastDebug['YAKIT_PRODUCT_ID_PROPERTY_raw'] = Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_ID_PROPERTY', '');

        if ($field === self::FIELD_ID) {
            $str = trim((string) $externalId);
            if ($str === '' || (string)(int) $str !== $str) {
                self::$lastDebug['steps'][] = 'ID_mode: rejected (not strict integer)';
                return null;
            }
            $id = (int) $str;
            if ($id <= 0) {
                self::$lastDebug['steps'][] = 'ID_mode: id<=0';
                return null;
            }
            if (!Loader::includeModule('iblock')) {
                return $id;
            }
            $el = ElementTable::getById($id)->fetch();
            self::$lastDebug['steps'][] = 'ID_mode: element ' . ($el ? 'found' : 'not found');
            self::$lastDebug['note'] = 'Search by internal ID only.';
            return $el ? (int) $el['ID'] : null;
        }

        $iblockIds = self::getSearchIblockIds();
        self::$lastDebug['iblockIds'] = $iblockIds;
        if (empty($iblockIds)) {
            self::$lastDebug['steps'][] = 'return: empty iblockIds';
            return null;
        }
        if (!Loader::includeModule('iblock')) {
            self::$lastDebug['steps'][] = 'return: iblock module not loaded';
            return null;
        }

        $value = trim((string) $externalId);
        if ($value === '') {
            self::$lastDebug['steps'][] = 'return: empty value';
            return null;
        }

        self::$lastDebug['value'] = $value;
        self::$lastDebug['perIblock'] = [];
        foreach ($iblockIds as $iblockId) {
            $result = self::resolveInIblock($iblockId, $field, $value);
            self::$lastDebug['perIblock'][$iblockId] = $result !== null ? 'found:' . $result : 'not_found';
            if ($result !== null) {
                self::$lastDebug['note'] = 'Search by field only. Match was in iblock ' . $iblockId;
                return $result;
            }
        }

        self::$lastDebug['steps'][] = 'return: not found in any iblock';
        return null;
    }

    public static function getLastDebug()
    {
        $d = self::$lastDebug;
        self::$lastDebug = [];
        return $d;
    }

    private static function resolveInIblock($iblockId, $field, $value)
    {
        $filter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'];

        if ($field === self::FIELD_XML_ID) {
            $filter['XML_ID'] = $value;
        } elseif ($field === self::FIELD_CODE) {
            $filter['CODE'] = $value;
        } elseif ($field === self::FIELD_PROPERTY) {
            $propCode = Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_ID_PROPERTY', '');
            if ($propCode === '') {
                return null;
            }
            $filter['PROPERTY_' . $propCode] = $value;
        } else {
            return null;
        }

        if ($field === self::FIELD_PROPERTY) {
            $res = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                $filter,
                false,
                ['nTopCount' => 1],
                ['ID']
            );
            $row = $res ? $res->Fetch() : null;
            if (!$row && isset($filter['PROPERTY_' . $propCode])) {
                unset($filter['PROPERTY_' . $propCode]);
                $filter['PROPERTY_' . $propCode . '_VALUE'] = $value;
                $res = \CIBlockElement::GetList(
                    ['ID' => 'ASC'],
                    $filter,
                    false,
                    ['nTopCount' => 1],
                    ['ID']
                );
                $row = $res ? $res->Fetch() : null;
            }
            if ($row && !self::elementPropertyValueEquals($iblockId, (int) $row['ID'], $propCode, $value)) {
                $row = null;
            }
        } else {
            $row = ElementTable::getList([
                'filter' => $filter,
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();
        }

        return $row ? (int) $row['ID'] : null;
    }

    private static function elementPropertyValueEquals($iblockId, $elementId, $propCode, $expectedValue)
    {
        $propCode = trim((string) $propCode);
        if ($propCode === '') {
            return false;
        }
        $res = \CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => $propCode]);
        if (!$res) {
            return false;
        }
        while ($prop = $res->Fetch()) {
            $val = isset($prop['VALUE']) ? $prop['VALUE'] : '';
            if (is_array($val)) {
                $val = reset($val);
            }
            if ((string) $val === (string) $expectedValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $internalId
     * @param bool $allowInternalFallback при false для полей XML_ID/CODE/PROPERTY не подставляется ID элемента
     */
    public static function getExternalId($internalId, $allowInternalFallback = true)
    {
        $internalId = (int) $internalId;
        if ($internalId <= 0) {
            return '';
        }

        $field = self::normalizeIdField(
            Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_ID_FIELD', self::FIELD_ID)
        );

        if ($field === self::FIELD_ID) {
            return (string) $internalId;
        }

        if (!Loader::includeModule('iblock')) {
            return $allowInternalFallback ? (string) $internalId : '';
        }

        $value = self::readFieldValueFromElement($internalId, $field);
        if ($value !== '') {
            return $value;
        }

        if (Loader::includeModule('catalog')) {
            $productInfo = \CCatalogSku::GetProductInfo($internalId);
            if (is_array($productInfo) && !empty($productInfo['ID'])) {
                $parentValue = self::readFieldValueFromElement((int) $productInfo['ID'], $field);
                if ($parentValue !== '') {
                    return $parentValue;
                }
            }
        }

        return $allowInternalFallback ? (string) $internalId : '';
    }

    private static function normalizeIdField($fieldRaw)
    {
        $field = trim((string) $fieldRaw);
        if (!in_array($field, [self::FIELD_ID, self::FIELD_XML_ID, self::FIELD_CODE, self::FIELD_PROPERTY], true)) {
            return self::FIELD_ID;
        }

        return $field;
    }

    private static function readFieldValueFromElement($elementId, $field)
    {
        $elementId = (int) $elementId;
        if ($elementId <= 0) {
            return '';
        }

        if ($field === self::FIELD_XML_ID || $field === self::FIELD_CODE) {
            $row = ElementTable::getList([
                'filter' => ['ID' => $elementId],
                'select' => ['ID', 'XML_ID', 'CODE'],
                'limit' => 1,
            ])->fetch();

            if (!$row) {
                return '';
            }

            if ($field === self::FIELD_XML_ID) {
                return trim((string) ($row['XML_ID'] ?? ''));
            }

            return trim((string) ($row['CODE'] ?? ''));
        }

        if ($field === self::FIELD_PROPERTY) {
            $propCode = trim((string) Option::get(self::MODULE_ID, 'YAKIT_PRODUCT_ID_PROPERTY', ''));
            if ($propCode === '') {
                return '';
            }

            $row = ElementTable::getList([
                'filter' => ['ID' => $elementId],
                'select' => ['ID', 'IBLOCK_ID'],
                'limit' => 1,
            ])->fetch();

            if (!$row) {
                return '';
            }

            $res = \CIBlockElement::GetProperty((int) $row['IBLOCK_ID'], $elementId, [], ['CODE' => $propCode]);
            if (!$res) {
                return '';
            }

            while ($prop = $res->Fetch()) {
                $val = isset($prop['VALUE']) ? $prop['VALUE'] : '';
                if (is_array($val)) {
                    $val = reset($val);
                }
                $val = trim((string) $val);
                if ($val !== '') {
                    return $val;
                }
            }
        }

        return '';
    }
}
