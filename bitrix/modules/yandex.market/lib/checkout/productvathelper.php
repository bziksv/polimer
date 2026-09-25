<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Loader;

/**
 * Ставка НДС для checkBasket: целый процент (22, 20, 10, …), −1 — без НДС (контракт YCP/yastore).
 * Источник — CCatalogProduct::GetVATDataByID (как Sale Cashbox и YaPay).
 */
class ProductVatHelper
{
    public static function getVatRatePercent(int $productId): ?int
    {
        if ($productId <= 0 || !Loader::includeModule('catalog')) {
            return null;
        }

        $vatData = \CCatalogProduct::GetVATDataByID($productId);
        if (!is_array($vatData) || empty($vatData)) {
            return null;
        }

        if (
            isset($vatData['EXCLUDE_VAT'])
            && (string)$vatData['EXCLUDE_VAT'] === 'Y'
        ) {
            return -1;
        }

        if (!isset($vatData['RATE']) || $vatData['RATE'] === null || $vatData['RATE'] === '') {
            return null;
        }

        $rate = (float)$vatData['RATE'];
        if ($rate > 0 && $rate < 1) {
            $rate *= 100;
        }

        return (int)round($rate);
    }
}
