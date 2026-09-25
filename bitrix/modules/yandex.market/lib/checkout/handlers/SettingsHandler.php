<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Bitrix\Sale\Internals\StatusLangTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\SiteCurrencyTable;

class SettingsHandler extends BaseHandler
{
    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        try {
            if (!Loader::includeModule('catalog')) {
                return $this->error('Catalog module not available', 500);
            }

            if (!Loader::includeModule('sale')) {
                return $this->error('Sale module not available', 500);
            }

            $isStoreControl = $this->isStoreControlEnabled();
            $quantityTraceEnabled = Option::get('catalog', 'default_quantity_trace', 'N') === 'Y';

            if ($this->useGeneralStockOnly()) {
                $gw = $this->getGeneralWarehouseForApi();
                $warehousesList = [['id' => $gw['id'], 'name' => $gw['name']]];
            } else {
                $warehouses = StoreTable::getList([
                    'filter' => ['ACTIVE' => 'Y'],
                    'select' => ['ID', 'TITLE', 'XML_ID'],
                    'order' => ['ID' => 'ASC']
                ]);

                $warehousesList = [];
                while ($warehouse = $warehouses->fetch()) {
                    $warehousesList[] = [
                        'id' => (string)($warehouse['XML_ID'] ?: $warehouse['ID']),
                        'name' => $warehouse['TITLE'] ?: ''
                    ];
                }
            }

            $enableReservation = Option::get('catalog', 'enable_reservation', 'N');
            $reserveCondition = Option::get('sale', 'product_reserve_condition', '');
            $reserveClearPeriod = Option::get('sale', 'product_reserve_clear_period', 0);

            $isReservationEnabled = false;
            if ($isStoreControl) {
                $isReservationEnabled = true;
            } elseif ($enableReservation === 'Y' || !empty($reserveCondition)) {
                $isReservationEnabled = true;
            }

            $moduleSettings = $this->getModuleSettings();
            $systemInfo = $this->getSystemInfo();
            $catalogStats = $this->getCatalogStats();
            $reservationDetails = $this->getReservationDetails($enableReservation, $reserveCondition, $reserveClearPeriod);
            $catalogSettings = $this->getCatalogSettings();
            $services = $this->getServicesInfo();
            $siteInfo = $this->getSiteInfo();
            $orderProperties = $this->getOrderPropertiesInfo();

            return $this->response([
                'store_control_enabled' => $isStoreControl,
                'quantity_trace_enabled' => $quantityTraceEnabled,
                'reservation_enabled' => $isReservationEnabled,
                'warehouses_count' => count($warehousesList),
                'warehouses' => $warehousesList,
                'module_settings' => $moduleSettings,
                'system_info' => $systemInfo,
                'catalog_stats' => $catalogStats,
                'reservation_details' => $reservationDetails,
                'catalog_settings' => $catalogSettings,
                'services' => $services,
                'site_info' => $siteInfo,
                'order_properties' => $orderProperties
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to get settings: ' . $e->getMessage(), 500);
        }
    }

    private function getModuleSettings()
    {
        $m = $this->moduleId;
        $jwtToken = Option::get($m, 'JWT_TOKEN', '');
        $apiToken = trim((string) Option::get($m, 'YANDEX_KIT_API_TOKEN', ''));

        return [
            'jwt_token_configured' => $jwtToken !== '',
            'yandex_kit_api_token_configured' => $apiToken !== '',
            'yandex_kit_store_id' => trim((string) Option::get($m, 'YANDEX_KIT_STORE_ID', '')),
            'yandex_kit_api_url' => Option::get($m, 'YANDEX_KIT_API_URL', 'https://integration.yastore.yandex.net/'),
            'yastore_user_id' => (int) Option::get($m, 'YASTORE_USER_ID', 0),
            'site_id' => (string) Option::get($m, 'SITE_ID', ''),
            'yakit_product_id_field' => Option::get($m, 'YAKIT_PRODUCT_ID_FIELD', 'ID'),
            'yakit_product_iblock_id' => (int) Option::get($m, 'YAKIT_PRODUCT_IBLOCK_ID', 0),
            'yakit_product_id_property' => (string) Option::get($m, 'YAKIT_PRODUCT_ID_PROPERTY', ''),
            'yakit_product_image_property' => (string) Option::get($m, 'YAKIT_PRODUCT_IMAGE_PROPERTY', ''),
            'send_order_emails' => Option::get($m, 'SEND_ORDER_EMAILS', 'N') === 'Y',
            'use_general_stock_only' => Option::get($m, 'USE_GENERAL_STOCK_ONLY', 'N') === 'Y',
            'sell_without_stock_check' => Option::get($m, 'SELL_WITHOUT_STOCK_CHECK', 'N') === 'Y',
            'default_product_quantity' => max(1, (int) Option::get($m, 'DEFAULT_PRODUCT_QUANTITY', '1')),
            'status_on_placed' => Option::get($m, 'STATUS_ON_PLACED', 'P'),
            'status_on_cancel' => Option::get($m, 'STATUS_ON_CANCEL', 'C'),
            'status_on_delivered' => Option::get($m, 'STATUS_ON_DELIVERED', 'F'),
            'auto_cancel_on_status_change' => Option::get($m, 'AUTO_CANCEL_ON_STATUS_CHANGE', 'N') === 'Y',
            'auto_cancel_status' => Option::get($m, 'AUTO_CANCEL_STATUS', 'C'),
            'auto_complete_on_status_change' => Option::get($m, 'AUTO_COMPLETE_ON_STATUS_CHANGE', 'N') === 'Y',
            'auto_complete_status' => Option::get($m, 'AUTO_COMPLETE_STATUS', 'F'),
            'show_button' => Option::get($m, 'SHOW_BUTTON', 'N') === 'Y',
            'yakit_button_text' => (string) Option::get($m, 'YAKIT_BUTTON_TEXT', 'Оформить заказ'),
            'yakit_basket_page_path' => (string) Option::get($m, 'YAKIT_BASKET_PAGE_PATH', '/personal/cart/'),
            'button_anchor' => (string) Option::get($m, 'BUTTON_ANCHOR', '.basket-checkout-section-inner'),
            'yakit_button_insert_after' => (string) Option::get($m, 'YAKIT_BUTTON_INSERT_AFTER', ''),
            'yakit_button_css' => (string) Option::get($m, 'YAKIT_BUTTON_CSS', ''),
            'use_sku' => Option::get($m, 'USE_SKU', 'N') === 'Y',
            'sku_iblock_id' => (int) Option::get($m, 'SKU_IBLOCK_ID', 0),
            'sku_properties' => (string) Option::get($m, 'SKU_PROPERTIES', ''),
            'sku_color_property' => (string) Option::get($m, 'SKU_COLOR_PROPERTY', ''),
            'sku_color_map' => (string) Option::get($m, 'SKU_COLOR_MAP', ''),
            'delivery_service_id' => (int) Option::get($m, 'YANDEX_KIT_DELIVERY_ID', 0),
            'payment_system_id' => (int) Option::get($m, 'YANDEX_KIT_PAY_SYSTEM_ID', 0),
        ];
    }

    private function getSystemInfo()
    {
        $modulesLoaded = [
            'sale' => Loader::includeModule('sale'),
            'catalog' => Loader::includeModule('catalog'),
            'iblock' => Loader::includeModule('iblock'),
        ];

        $moduleVersion = $this->getModuleVersion();

        return [
            'php_version' => PHP_VERSION,
            'bitrix_version' => defined('SM_VERSION') ? SM_VERSION : 'unknown',
            'module_version' => $moduleVersion,
            'modules_loaded' => $modulesLoaded
        ];
    }

    private function getModuleVersion()
    {
        $module = \CModule::CreateModuleObject('yandex.market');

        if ($module && isset($module->MODULE_VERSION)) {
            return $module->MODULE_VERSION;
        }

        return 'unknown';
    }

    private function getCatalogStats()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            return ['total_products' => 0, 'active_products' => 0];
        }

        $catalogIblocks = \Bitrix\Catalog\CatalogIblockTable::getList(['select' => ['IBLOCK_ID']]);

        $iblockIds = [];
        while ($catalog = $catalogIblocks->fetch()) {
            $iblockIds[] = (int)$catalog['IBLOCK_ID'];
        }

        if (empty($iblockIds)) {
            return ['total_products' => 0, 'active_products' => 0];
        }

        $totalProducts = ElementTable::getCount(['IBLOCK_ID' => $iblockIds]);
        $activeProducts = ElementTable::getCount(['ACTIVE' => 'Y', 'IBLOCK_ID' => $iblockIds]);

        return [
            'total_products' => (int)$totalProducts,
            'active_products' => (int)$activeProducts
        ];
    }

    private function getCatalogSettings()
    {
        return [
            'quantity_trace_enabled' => Option::get('catalog', 'default_quantity_trace', 'N') === 'Y',
            'can_buy_zero' => Option::get('catalog', 'default_can_buy_zero', 'N') === 'Y',
            'allow_negative_amount' => Option::get('catalog', 'allow_negative_amount', 'N') === 'Y'
        ];
    }

    private function getReservationDetails($enableReservation, $reserveCondition, $reserveClearPeriod)
    {
        if (class_exists('Bitrix\Sale\Reservation\Configuration\ReserveCondition')) {
            $reserveConditionMap = [
                \Bitrix\Sale\Reservation\Configuration\ReserveCondition::ON_CREATE => 'При создании заказа',
                \Bitrix\Sale\Reservation\Configuration\ReserveCondition::ON_PAY => 'При оплате',
                \Bitrix\Sale\Reservation\Configuration\ReserveCondition::ON_FULL_PAY => 'При полной оплате',
                \Bitrix\Sale\Reservation\Configuration\ReserveCondition::ON_ALLOW_DELIVERY => 'При разрешении доставки',
                \Bitrix\Sale\Reservation\Configuration\ReserveCondition::ON_SHIP => 'При отгрузке'
            ];
        } else {
            $reserveConditionMap = [
                'O' => 'При создании заказа',
                'P' => 'При оплате',
                'F' => 'При полной оплате',
                'D' => 'При разрешении доставки',
                'S' => 'При отгрузке'
            ];
        }

        $reserveConditionName = null;
        if (!empty($reserveCondition) && isset($reserveConditionMap[$reserveCondition])) {
            $reserveConditionName = $reserveConditionMap[$reserveCondition];
        }

        return [
            'catalog_enable_reservation' => $enableReservation,
            'reserve_condition' => [
                'code' => $reserveCondition ?: null,
                'name' => $reserveConditionName
            ],
            'reserve_clear_period_days' => (int)$reserveClearPeriod
        ];
    }

    private function getServicesInfo()
    {
        $m = $this->moduleId;
        $deliveryServiceId = (int)Option::get($m, 'YANDEX_KIT_DELIVERY_ID', 0);
        $paymentSystemId = (int)Option::get($m, 'YANDEX_KIT_PAY_SYSTEM_ID', 0);

        $deliveryInfo = ['configured' => false, 'id' => $deliveryServiceId, 'name' => null];

        if ($deliveryServiceId > 0) {
            try {
                $deliveryData = DeliveryManager::getById($deliveryServiceId);
                if ($deliveryData) {
                    $deliveryInfo['configured'] = true;
                    $deliveryInfo['name'] = $deliveryData['NAME'] ?? null;
                }
            } catch (\Exception $e) {
            }
        }

        $paymentInfo = ['configured' => false, 'id' => $paymentSystemId, 'name' => null];

        if ($paymentSystemId > 0) {
            try {
                $paymentData = PaySystemManager::getById($paymentSystemId);
                if ($paymentData) {
                    $paymentInfo['configured'] = true;
                    $paymentInfo['name'] = $paymentData['NAME'] ?? null;
                }
            } catch (\Exception $e) {
            }
        }

        return [
            'delivery' => $deliveryInfo,
            'payment' => $paymentInfo
        ];
    }

    private function getSiteInfo()
    {
        $siteId = Context::getCurrent()->getSite();
        $currency = null;

        try {
            $currency = SiteCurrencyTable::getSiteCurrency($siteId);
        } catch (\Exception $e) {
        }

        return [
            'site_id' => $siteId,
            'currency' => $currency,
            'language' => defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru'
        ];
    }

    private function getOrderPropertiesInfo()
    {
        $propertiesToCheck = [
            'EXTERNAL_ORDER_ID',
            'DELIVERY_TYPE',
            'DELIVERY_SERVICE',
            'STREET',
            'BUILDING',
            'APARTMENT'
        ];

        $propertiesStatus = [];

        try {
            $personTypes = PersonTypeTable::getList([
                'select' => ['ID'],
                'filter' => ['ACTIVE' => 'Y']
            ]);

            $personTypeIds = [];
            while ($personType = $personTypes->fetch()) {
                $personTypeIds[] = $personType['ID'];
            }

            foreach ($personTypeIds as $personTypeId) {
                foreach ($propertiesToCheck as $propertyCode) {
                    $property = OrderPropsTable::getList([
                        'filter' => ['PERSON_TYPE_ID' => $personTypeId, 'CODE' => $propertyCode],
                        'select' => ['ID', 'CODE', 'NAME'],
                        'limit' => 1
                    ])->fetch();

                    if (!isset($propertiesStatus[$propertyCode])) {
                        $propertiesStatus[$propertyCode] = ['exists' => false, 'person_types' => []];
                    }

                    if ($property) {
                        $propertiesStatus[$propertyCode]['exists'] = true;
                        if (!in_array($personTypeId, $propertiesStatus[$propertyCode]['person_types'])) {
                            $propertiesStatus[$propertyCode]['person_types'][] = $personTypeId;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return $propertiesStatus;
    }
}
