<?php

namespace Darneo\Ozon\Api;

use Bitrix\Main\Localization\Loc;

class Config
{
    public const LANG = 'RU';
    public const HOST = 'https://api-seller.ozon.ru';

    public static function getDocumentationByUrl(string $url): array
    {
        $host = 'https://docs.ozon.ru/api/seller/#operation/';
        $url = str_replace(self::HOST, '', $url);

        return match ($url) {
            '/v1/analytics/data' => [
                'URL' => $host . 'AnalyticsAPI_AnalyticsGetData',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ANALYTICS')
            ],
            '/v1/description-category/tree' => [
                'URL' => $host . 'DescriptionCategoryAPI_GetTree',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_TREE')
            ],
            '/v1/description-category/attribute' => [
                'URL' => $host . 'DescriptionCategoryAPI_GetAttributes',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ATTR')
            ],
            '/v1/description-category/attribute/values' => [
                'URL' => $host . 'DescriptionCategoryAPI_GetAttributeValues',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ATTR_VALUE')
            ],
            '/v1/product/import/prices' => [
                'URL' => $host . 'ProductAPI_ImportProductsPrices',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_PRICE')
            ],
            '/v1/product/import/info' => [
                'URL' => $host . 'ProductAPI_GetImportProductsInfo',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEM_STATUS')
            ],
            '/v1/warehouse/list' => [
                'URL' => $host . 'WarehouseAPI_WarehouseList',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_STORE')
            ],
            '/v2/posting/fbo/list' => [
                'URL' => $host . 'PostingAPI_GetFboPostingList',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_FBO')
            ],
            '/v2/product/info' => [
                'URL' => $host . 'ProductAPI_GetProductInfoV2',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEM_INFO')
            ],
            '/v2/product/import', '/v3/product/import' => [
                'URL' => $host . 'ProductAPI_ImportProductsV2',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEM_ADD')
            ],
            '/v2/product/list' => [
                'URL' => $host . 'ProductAPI_GetProductList',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEMS')
            ],
            '/v2/products/stocks' => [
                'URL' => $host . 'ProductAPI_ImportProductsStocks',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEM_STORE')
            ],
            '/v3/posting/fbs/list' => [
                'URL' => $host . 'PostingAPI_GetFbsPostingListV3',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_FBS')
            ],
            '/v3/product/info/stocks' => [
                'URL' => $host . 'ProductAPI_GetProductInfoStocksV3',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_ITEM_INFO_QUANTITY')
            ],
            '/v4/product/info/limit' => [
                'URL' => $host . 'ProductAPI_GetUploadQuota',
                'TITLE' => Loc::getMessage('DARNEO_OZON_API_CONFIG_LIMIT')
            ],
            default => '',
        };
    }
}
