<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Yandex\Market\Checkout\CheckoutEvents;
use Yandex\Market\Checkout\ProductImageHelper;
use Yandex\Market\Checkout\ColorMapHelper;
use Yandex\Market\Checkout\ProductIdResolver;
use Yandex\Market\Checkout\ProductVatHelper;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\Model\Price;
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Discount;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Yandex\Market\Utils\PhpSerializer;

class CheckBasketHandler extends BaseHandler
{
    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        try {
            if (!Loader::includeModule('catalog') || !Loader::includeModule('iblock') || !Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            $input = file_get_contents('php://input');
            $requestData = Json::decode($input);

            if (empty($requestData['items']) || !is_array($requestData['items'])) {
                return $this->error('Invalid request format: items array is required', 400);
            }

            $warehouseId = isset($requestData['warehouse_id']) ? $requestData['warehouse_id'] : null;

            $responseItems = [];
            $notFoundItems = [];
            $notFoundDebug = null;
            $basketPrices = $this->getBasketPrices($requestData['items']);

            foreach ($requestData['items'] as $requestIndex => $requestItem) {
                if (empty($requestItem['id'])) {
                    continue;
                }
                $requestedQuantity = isset($requestItem['quantity']) && (string) $requestItem['quantity'] !== '' ? intval($requestItem['quantity']) : 1;
                if ($requestedQuantity < 1) {
                    $requestedQuantity = 1;
                }

                $externalId = $requestItem['id'];

                $productId = ProductIdResolver::resolveToInternalId($externalId);
                if ($productId === null) {
                    $notFoundItems[] = $externalId;
                    if ($notFoundDebug === null) {
                        $notFoundDebug = ProductIdResolver::getLastDebug();
                    }
                    continue;
                }

                $element = ElementTable::getList([
                    'filter' => ['ID' => $productId, 'ACTIVE' => 'Y'],
                    'select' => ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE'],
                    'limit' => 1
                ])->fetch();

                if (!$element) {
                    $notFoundItems[] = $externalId;
                    continue;
                }

                $product = ProductTable::getList([
                    'filter' => ['ID' => $productId],
                    'select' => ['ID', 'WIDTH', 'HEIGHT', 'LENGTH', 'WEIGHT',]
                ])->fetch();

                $priceData = isset($basketPrices[$requestIndex])
                    ? $basketPrices[$requestIndex]
                    : $this->getProductPrice($productId);
                $warehouseAvailability = $this->getWarehouseAvailability($productId, $warehouseId);
                $imageUrl = $this->getProductImage($element);
                $productUrl = $this->getProductUrl($element);
                $responseItem = [
                    'id' => ProductIdResolver::getExternalId($productId),
                    'name' => $element['NAME'],
                    'regular_price' => $priceData['regular_price'],
                    'final_price' => $priceData['final_price'],
                    'warehouses' => $warehouseAvailability['warehouses'],
                ];

                if ($imageUrl) {
                    $responseItem['img'] = $imageUrl;
                }

                if ($productUrl) {
                    $responseItem['url'] = $productUrl;
                }

                $this->appendVatToResponseItem($responseItem, $productId);

                if ($product && ($product['WIDTH'] || $product['HEIGHT'] || $product['LENGTH'] || $product['WEIGHT'])) {
                    $responseItem['dimensions'] = [
                        'width' => (int)$product['WIDTH'] ?: 0,
                        'height' => (int)$product['HEIGHT'] ?: 0,
                        'depth' => (int)$product['LENGTH'] ?: 0,
                        'weight' => (int)$product['WEIGHT'] ?: 0
                    ];
                }

                // Характеристики и вариации только при включённой опции и выбранном инфоблоке торговых предложений
                $useSku = Option::get('yandex.market', 'USE_SKU', 'N');
                $skuIblockId = Option::get('yandex.market', 'SKU_IBLOCK_ID', '');
                if ($useSku === 'Y' && $skuIblockId !== '') {
                    $colorMap = $this->getColorMap();
                    $forceColorAsText = false;
                    $variations = $this->getProductVariations($element['ID'], $element['IBLOCK_ID'], $warehouseId, $forceColorAsText, $colorMap, $forceColorAsText);
                    if (!empty($variations)) {
                        $responseItem['variations'] = $variations;
                    }
                    // forceColorAsText вычислен внутри getProductVariations по тем же офферам, что в ответе
                    $characteristics = $this->getProductCharacteristics($element['ID'], $element['IBLOCK_ID'], $colorMap, $forceColorAsText);
                    if (!empty($characteristics)) {
                        $responseItem['characteristics'] = $characteristics;
                    }
                }

                $responseItems[] = $responseItem;
            }

            if (!empty($notFoundItems)) {
                $msg = 'Products not found: ' . implode(', ', $notFoundItems);
                if (
                    \Yandex\Market\Config::isDevMode()
                    && ($this->request->get('debug') === '1' || $this->request->getHeader('X-Debug') === '1')
                ) {
                    return $this->errorWithData($msg, 404, self::ERROR_NOT_FOUND, [
                        'debug' => ['externalIds' => $notFoundItems],
                    ]);
                }

                return $this->error($msg, 404);
            }

            $response = ['items' => $responseItems];
            $event = new Event('yandex.market', CheckoutEvents::ON_CHECK_BASKET_RESPONSE, [
                'response' => $response,
                'request' => $requestData,
                'warehouse_id' => $warehouseId,
            ]);
            $event->send();
            foreach ($event->getResults() as $result) {
                if ($result->getType() === EventResult::ERROR) {
                    continue;
                }
                $params = $result->getParameters();
                if (!is_array($params)) {
                    continue;
                }
                if (isset($params['response']) && is_array($params['response'])) {
                    $response = array_merge($response, $params['response']);
                }
                if (isset($params['items']) && is_array($params['items'])) {
                    $response['items'] = $params['items'];
                }
            }

            return $this->response($response);

        } catch (\Exception $e) {
            return $this->error('Failed to check basket: ' . $e->getMessage(), 500);
        }
    }

    private function appendVatToResponseItem(array &$responseItem, int $productId): void
    {
        $vat = ProductVatHelper::getVatRatePercent($productId);
        if ($vat !== null) {
            $responseItem['vat'] = $vat;
        }
    }

    private function getBasketPrices(array $requestItems)
    {
        $siteId = \Bitrix\Main\Context::getCurrent()->getSite();
        $currency = SiteCurrencyTable::getSiteCurrency($siteId) ?: 'RUB';
        $basket = Basket::create($siteId);
        $requestIndexesByBasketCode = [];
        $requestedQuantitiesByBasketCode = [];

        foreach ($requestItems as $requestIndex => $requestItem) {
            if (empty($requestItem['id'])) {
                continue;
            }

            $productId = ProductIdResolver::resolveToInternalId($requestItem['id']);
            if ($productId === null) {
                continue;
            }

            $element = ElementTable::getList([
                'filter' => ['ID' => $productId, 'ACTIVE' => 'Y'],
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();
            if (!$element) {
                continue;
            }

            $quantity = isset($requestItem['quantity']) && (string)$requestItem['quantity'] !== ''
                ? (int)$requestItem['quantity']
                : 1;
            if ($quantity < 1) {
                $quantity = 1;
            }

            $basketItem = $basket->createItem('catalog', $productId);
            $fields = [
                'QUANTITY' => $quantity,
                'CURRENCY' => $currency,
                'LID' => $siteId,
                'PRODUCT_PROVIDER_CLASS' => class_exists('\\Bitrix\\Catalog\\Product\\CatalogProvider')
                    ? '\\Bitrix\\Catalog\\Product\\CatalogProvider'
                    : '\\CCatalogProductProvider',
            ];
            $setResult = $basketItem->setFields($fields);
            if (!$setResult->isSuccess()) {
                throw new \RuntimeException(implode(', ', $setResult->getErrorMessages()));
            }

            $basketCode = $basketItem->getBasketCode();
            $requestIndexesByBasketCode[$basketCode] = $requestIndex;
            $requestedQuantitiesByBasketCode[$basketCode] = $quantity;
        }

        if (empty($requestIndexesByBasketCode)) {
            return [];
        }

        $refreshResult = $basket->refresh();
        if (!$refreshResult->isSuccess()) {
            throw new \RuntimeException(implode(', ', $refreshResult->getErrorMessages()));
        }

        // checkBasket reports price and stock independently. Restore the
        // requested cart after refresh so stock limits do not change discounts.
        foreach ($basket as $basketItem) {
            $basketCode = $basketItem->getBasketCode();
            if (!isset($requestedQuantitiesByBasketCode[$basketCode])) {
                continue;
            }

            $basketItem->setFieldNoDemand('QUANTITY', $requestedQuantitiesByBasketCode[$basketCode]);
            $basketItem->setFieldNoDemand('CAN_BUY', 'Y');
        }

        $discounts = Discount::buildFromBasket(
            $basket,
            new Discount\Context\Fuser($basket->getFUserId(true))
        );
        $discountResult = $discounts->calculate();
        if (!$discountResult->isSuccess()) {
            throw new \RuntimeException(implode(', ', $discountResult->getErrorMessages()));
        }
        $appliedDiscounts = $discounts->getApplyResult(true);

        $prices = [];
        foreach ($basket as $basketItem) {
            $basketCode = $basketItem->getBasketCode();
            if (!isset($requestIndexesByBasketCode[$basketCode])) {
                continue;
            }

            $finalPrice = (float)$basketItem->getPrice();
            if (isset($appliedDiscounts['PRICES']['BASKET'][$basketCode]['PRICE'])) {
                $finalPrice = (float)$appliedDiscounts['PRICES']['BASKET'][$basketCode]['PRICE'];
            }

            $prices[$requestIndexesByBasketCode[$basketCode]] = [
                'regular_price' => (float)$basketItem->getBasePrice(),
                'final_price' => $finalPrice,
            ];
        }

        return $prices;
    }

    private function getProductPrice($productId)
    {
        \Bitrix\Main\Loader::includeModule('catalog');
        \Bitrix\Main\Loader::includeModule('sale');
        $optimalPrice = \CCatalogProduct::GetOptimalPrice(
            $productId,
            1,
            [],
            'N',
            [],
            \Bitrix\Main\Context::getCurrent()->getSite()
        );

        $regularPrice = 0;
        $finalPrice = 0;

        if ($optimalPrice && isset($optimalPrice['RESULT_PRICE'])) {
            $regularPrice = floatval($optimalPrice['RESULT_PRICE']['BASE_PRICE']);
            $finalPrice = floatval($optimalPrice['RESULT_PRICE']['DISCOUNT_PRICE']);
        }
        
        return [
            'regular_price' => $regularPrice,
            'final_price' => $finalPrice
        ];
    }

    private function getWarehouseAvailability($productId, $warehouseId = null)
    {
        $warehouses = [];
        $isStoreControl = $this->isStoreControlEnabled();

        // Опция «Продавать все активные товары» — не проверять остатки; available_quantity из настройки «Количество товара по умолчанию»
        $sellWithoutStockCheck = Option::get('yandex.market', 'SELL_WITHOUT_STOCK_CHECK', 'N') === 'Y';
        if ($sellWithoutStockCheck) {
            $availableQty = max(1, (int) Option::get('yandex.market', 'DEFAULT_PRODUCT_QUANTITY', '1'));
            if ($this->useGeneralStockOnly()) {
                return $this->applyWarehousePagination([[
                    'id' => $this->getGeneralWarehouseForApi()['id'],
                    'available_quantity' => $availableQty,
                ]]);
            }
            if ($warehouseId !== null && $warehouseId !== '') {
                $requestedStore = StoreTable::getList([
                    'filter' => ['ID' => $warehouseId, 'ACTIVE' => 'Y'],
                    'select' => ['ID'],
                    'limit' => 1
                ])->fetch();
                if (!$requestedStore) {
                    return $this->applyWarehousePagination([]);
                }

                return $this->applyWarehousePagination([[
                    'id' => (string)$requestedStore['ID'],
                    'available_quantity' => $availableQty,
                ]]);
            }

            $activeStores = StoreTable::getList([
                'filter' => ['ACTIVE' => 'Y'],
                'select' => ['ID'],
                'order' => ['ID' => 'ASC'],
            ]);

            while ($store = $activeStores->fetch()) {
                $warehouses[] = [
                    'id' => (string)$store['ID'],
                    'available_quantity' => $availableQty,
                ];
            }

            if (!empty($warehouses)) {
                return $this->applyWarehousePagination($warehouses);
            }

            return $this->applyWarehousePagination([[
                'id' => $this->getVirtualWarehouse()['id'],
                'available_quantity' => $availableQty,
            ]]);
        }

        // Только общий остаток из каталога, в API — виртуальный склад id=1
        if ($this->useGeneralStockOnly()) {
            $product = ProductTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['ID', 'QUANTITY', 'QUANTITY_RESERVED'],
            ])->fetch();
            $availableQuantity = $product ? (float) $product['QUANTITY'] : 0;

            return $this->applyWarehousePagination([[
                'id' => $this->getGeneralWarehouseForApi()['id'],
                'available_quantity' => (int) $availableQuantity,
            ]]);
        }

        // Получаем общий остаток товара
        $product = ProductTable::getList([
            'filter' => ['ID' => $productId],
            'select' => ['ID', 'QUANTITY', 'QUANTITY_RESERVED']
        ])->fetch();

        $totalQuantity = $product ? (float)$product['QUANTITY'] : 0;
        // Не вычитаем резервы - показываем общее количество
        $availableQuantity = $totalQuantity;

        // Проверяем наличие складов в системе
        $hasWarehouses = $this->hasWarehouses();
        
        // Проверяем наличие остатков по складам
        $hasStock = $this->hasWarehouseStock($productId);
        
        // При выключенном складском учёте сначала смотрим остатки по складам; если есть — отдаём их.
        if (!$isStoreControl) {
            $filterStore = ['PRODUCT_ID' => $productId];
            if ($warehouseId !== null) {
                $store = \Bitrix\Catalog\StoreTable::getList([
                    'filter' => ['ID' => $warehouseId],
                    'select' => ['ID'],
                    'limit' => 1
                ])->fetch();
                if (!$store) {
                    return $this->applyWarehousePagination([]);
                }
                $filterStore['STORE_ID'] = $store['ID'];
            }
            $storeProducts = StoreProductTable::getList([
                'filter' => $filterStore,
                'select' => ['STORE_ID', 'AMOUNT', 'QUANTITY_RESERVED'],
            ]);
            $byStore = [];
            while ($row = $storeProducts->fetch()) {
                $store = \Bitrix\Catalog\StoreTable::getById($row['STORE_ID'])->fetch();
                if ($store && $store['ACTIVE'] === 'Y') {
                    $byStore[] = ['id' => (string)$store['ID'], 'available_quantity' => (int)$row['AMOUNT']];
                }
            }
            if (!empty($byStore)) {
                return $this->applyWarehousePagination($byStore);
            }
            // Нет остатков по складам — один склад с общим остатком (ProductTable.QUANTITY)
            $firstWarehouse = StoreTable::getList([
                'filter' => ['ACTIVE' => 'Y'],
                'select' => ['ID'],
                'order' => ['ID' => 'ASC'],
                'limit' => 1
            ])->fetch();

            if ($firstWarehouse) {
                $warehouseIdForResponse = (string)$firstWarehouse['ID'];
            } else {
                $virtualWarehouse = $this->getVirtualWarehouse();
                $warehouseIdForResponse = $virtualWarehouse['id'];
            }

            return $this->applyWarehousePagination([[
                'id' => $warehouseIdForResponse,
                'available_quantity' => $availableQuantity
            ]]);
        }

        // Складской учёт включен - работаем со складами
        // Если общий остаток > 0, но складов нет или остатков по складам нет - используем виртуальный склад
        if ($availableQuantity > 0 && (!$hasWarehouses || !$hasStock)) {
            $virtualWarehouse = $this->getVirtualWarehouse();
            return $this->applyWarehousePagination([[
                'id' => $virtualWarehouse['id'],
                'available_quantity' => $availableQuantity
            ]]);
        }

        // Формируем фильтр
        $filter = ['PRODUCT_ID' => $productId];
        
        // Если указан конкретный склад - фильтруем по нему
        if ($warehouseId !== null) {
            // Ищем склад по ID
            $store = \Bitrix\Catalog\StoreTable::getList([
                'filter' => ['ID' => $warehouseId],
                'select' => ['ID'],
                'limit' => 1
            ])->fetch();
            
            if ($store) {
                $filter['STORE_ID'] = $store['ID'];
            } else {
                // Если склад не найден - возвращаем пустой массив
                return $this->applyWarehousePagination([]);
            }
        }

        $storeProducts = StoreProductTable::getList([
            'filter' => $filter,
            'select' => ['STORE_ID', 'AMOUNT', 'QUANTITY_RESERVED'],
            'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'STORE',
                    '\Bitrix\Catalog\StoreTable',
                    ['=this.STORE_ID' => 'ref.ID'],
                    ['join_type' => 'inner']
                )
            ]
        ]);

        while ($storeProduct = $storeProducts->fetch()) {
            $store = \Bitrix\Catalog\StoreTable::getById($storeProduct['STORE_ID'])->fetch();
            
            if ($store && $store['ACTIVE'] === 'Y') {
                $amount = (int)$storeProduct['AMOUNT'];
                // Не вычитаем резервы - показываем общее количество
                $available = $amount;
                $warehouses[] = [
                    'id' => (string)$store['ID'],
                    'available_quantity' => $available
                ];
            }
        }

        return $this->applyWarehousePagination($warehouses);
    }

    private function applyWarehousePagination(array $warehouses)
    {
        $totalCount = count($warehouses);
        return [
            'warehouses' => array_values($warehouses),
            'total_count' => $totalCount,
        ];
    }

    private function getProductImage($element)
    {
        $id = (int)($element['ID'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $iblockId = isset($element['IBLOCK_ID']) ? (int)$element['IBLOCK_ID'] : null;

        return ProductImageHelper::resolveUrl($id, $iblockId, $element);
    }

    private function getProductUrl($element)
    {
        $el = \CIBlockElement::GetByID($element['ID'])->GetNext();
        
        if ($el && $el['DETAIL_PAGE_URL']) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return $protocol . '://' . $host . $el['DETAIL_PAGE_URL'];
        }

        return null;
    }

    /**
     * Получает все вариации торгового предложения и их свойства.
     * По тем же офферам собирает все значения цвета и передаёт наружу forceColorAsText (по ссылке).
     *
     * @param int $skuId ID текущего торгового предложения
     * @param int $iblockId ID инфоблока
     * @param int|null $warehouseId ID склада
     * @param bool $forceColorAsText не используется; результат записывается в $forceColorAsTextOut
     * @param array|null $colorMap карта значение цвета → HEX
     * @param bool|null $forceColorAsTextOut по ссылке: «хоть один цвет не в маппинге» → true
     * @return array Массив вариаций
     */
    private function getProductVariations($skuId, $iblockId, $warehouseId = null, $forceColorAsText = false, array $colorMap = null, &$forceColorAsTextOut = null)
    {
        try {
            $productInfo = \CCatalogSku::GetProductInfo($skuId, $iblockId);
            if (!$productInfo || empty($productInfo['ID'])) {
                return [];
            }

            $baseProductId = $productInfo['ID'];
            $baseProductIblockId = $productInfo['IBLOCK_ID'];
            $skuInfo = \CCatalogSKU::GetInfoByProductIBlock($baseProductIblockId);
            if (!$skuInfo || empty($skuInfo['IBLOCK_ID'])) {
                return [];
            }

            $offersIblockId = $skuInfo['IBLOCK_ID'];
            $productPropertyId = $skuInfo['SKU_PROPERTY_ID'] ?? null;
            $colorPropertyId = Option::get('yandex.market', 'SKU_COLOR_PROPERTY', '');
            if ($colorMap === null) {
                $colorMap = $this->getColorMap();
            }

            // Собираем все значения цвета: основной элемент + все вариации (те же офферы, что в ответе)
            $allColorValues = [];
            if ($colorPropertyId !== '') {
                $propRes = \CIBlockProperty::GetByID($colorPropertyId);
                $colorProp = $propRes ? $propRes->GetNext() : null;
                if ($colorProp && !empty($colorProp['CODE'])) {
                    $colorCode = $colorProp['CODE'];
                    $colorPropType = $colorProp['PROPERTY_TYPE'] ?? '';
                    if ((int)$colorProp['IBLOCK_ID'] === (int)$iblockId) {
                        $el = \CIBlockElement::GetByID($skuId)->GetNextElement();
                        if ($el) {
                            $props = $el->GetProperties();
                            if (isset($props[$colorCode]['VALUE'])) {
                                $value = $this->normalizeSingleColorValue($props[$colorCode]['VALUE']);
                                if (!is_array($value)) {
                                    $text = $this->resolvePropertyValueToText($value, $colorPropType);
                                    if ($text !== null && $text !== '') {
                                        $allColorValues[] = $text;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $variationProperties = $this->getVariationProperties($offersIblockId, $productPropertyId);
            
            // Получаем все вариации базового товара
            $filter = [
                'IBLOCK_ID' => $offersIblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y'
            ];
            
            // Если есть свойство связи с товаром, фильтруем по нему
            if ($productPropertyId) {
                $filter['PROPERTY_' . $productPropertyId] = $baseProductId;
            }
            
            // Первый проход: собираем данные вариаций и все значения цвета
            $variationRows = [];
            $res = \CIBlockElement::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                $filter,
                false,
                false,
                ['ID', 'NAME', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']
            );
            
            while ($variation = $res->GetNextElement()) {
                $variationFields = $variation->GetFields();
                $variationId = $variationFields['ID'];
                
                // Пропускаем текущий товар, так как он уже есть на уровне выше
                if ((string)$variationId === (string)$skuId) {
                    continue;
                }
                
                // Получаем свойства вариации
                $variationProps = $variation->GetProperties();
                
                // Формируем массив свойств, которыми отличаются вариации
                $properties = [];
                foreach ($variationProperties as $propCode => $propInfo) {
                    if (isset($variationProps[$propCode])) {
                        $propValue = $variationProps[$propCode];
                        $value = null;
                        
                        // Получаем значение свойства
                        if (isset($propValue['VALUE'])) {
                            if (is_array($propValue['VALUE'])) {
                                $value = $propValue['VALUE'];
                            } else {
                                $value = $propValue['VALUE'];
                            }
                        }
                        
                        // Пропускаем пустые значения
                        if (empty($value) && $value !== '0' && $value !== 0) {
                            continue;
                        }
                        
                        // Для списков и справочников получаем текстовое значение.
                        // Важно: ориентируемся на фактический тип из VALUE-блока свойства оффера.
                        $actualPropertyType = isset($propValue['PROPERTY_TYPE']) ? (string)$propValue['PROPERTY_TYPE'] : (string)$propInfo['PROPERTY_TYPE'];
                        if ($actualPropertyType === 'L' || $actualPropertyType === 'E') {
                            if (is_array($value)) {
                                $textValues = [];
                                foreach ($value as $val) {
                                    if (empty($val) && $val !== '0' && $val !== 0) {
                                        continue;
                                    }
                                    if ($actualPropertyType === 'L') {
                                        if (!empty($propValue['VALUE_ENUM'])) {
                                            if (is_array($propValue['VALUE_ENUM'])) {
                                                foreach ($propValue['VALUE_ENUM'] as $enumText) {
                                                    $enumText = trim((string)$enumText);
                                                    if ($enumText !== '') {
                                                        $textValues[] = $enumText;
                                                    }
                                                }
                                            } else {
                                                $enumText = trim((string)$propValue['VALUE_ENUM']);
                                                if ($enumText !== '') {
                                                    $textValues[] = $enumText;
                                                }
                                            }
                                            if (!empty($textValues)) {
                                                break;
                                            }
                                        }
                                        // Для списка получаем значение из вариантов
                                        $enumRes = \CIBlockPropertyEnum::GetList(
                                            [],
                                            ['ID' => $val]
                                        );
                                        if ($enum = $enumRes->GetNext()) {
                                            $textValues[] = $enum['VALUE'];
                                        }
                                    } else {
                                        // Для привязки к элементам получаем название
                                        $elRes = \CIBlockElement::GetByID($val);
                                        if ($el = $elRes->GetNext()) {
                                            $textValues[] = $el['NAME'];
                                        }
                                    }
                                }
                                $value = !empty($textValues) ? (count($textValues) === 1 ? $textValues[0] : $textValues) : $value;
                            } else {
                                if ($actualPropertyType === 'L') {
                                    if (!empty($propValue['VALUE_ENUM'])) {
                                        $value = $propValue['VALUE_ENUM'];
                                    } else {
                                    $enumRes = \CIBlockPropertyEnum::GetList(
                                        [],
                                        ['ID' => $value]
                                    );
                                    if ($enum = $enumRes->GetNext()) {
                                        $value = $enum['VALUE'];
                                    }
                                    }
                                } else {
                                    $elRes = \CIBlockElement::GetByID($value);
                                    if ($el = $elRes->GetNext()) {
                                        $value = $el['NAME'];
                                    }
                                }
                            }
                        }

                        $isColorProperty = $colorPropertyId !== '' && (string)$propInfo['ID'] === (string)$colorPropertyId;
                        if ($isColorProperty) {
                            $value = $this->normalizeSingleColorValue($value);
                        }
                        
                        $properties[$propCode] = [
                            'code' => $propCode,
                            'name' => $propInfo['NAME'],
                            'value' => $value,
                            'property_id' => $propInfo['ID']
                        ];
                        if ($isColorProperty && !is_array($value)) {
                            $allColorValues[] = (string)$value;
                        }
                    }
                }
                $variationRows[] = ['fields' => $variationFields, 'properties' => $properties];
            }

            $forceColorAsText = ColorMapHelper::hasUnmappedColorValues($allColorValues, $colorMap);
            if ($forceColorAsTextOut !== null) {
                $forceColorAsTextOut = $forceColorAsText;
            }

            $variations = [];
            foreach ($variationRows as $row) {
                $variationFields = $row['fields'];
                $properties = $row['properties'];
                $variationId = $variationFields['ID'];

                $characteristics = [];
                foreach ($properties as $prop) {
                    if (is_array($prop['value'])) {
                        continue;
                    }
                    $propId = isset($prop['property_id']) ? (string)$prop['property_id'] : '';
                    if ($colorPropertyId !== '' && $propId === (string)$colorPropertyId) {
                        $valueText = (string)$prop['value'];
                        $hex = ColorMapHelper::getHexFromColorMapOrNull($colorMap, $valueText);
                        $useTextForColor = $forceColorAsText || ($hex === null);
                        if ($useTextForColor) {
                            $characteristics[] = [
                                'code' => $prop['code'],
                                'name' => $prop['name'],
                                'value' => $prop['value'],
                                'type' => 'text'
                            ];
                        } else {
                            $characteristics[] = [
                                'code' => $prop['code'],
                                'name' => $prop['name'],
                                'value' => $hex,
                                'value_text' => $valueText,
                                'type' => 'color'
                            ];
                        }
                    } else {
                        $characteristics[] = [
                            'code' => $prop['code'],
                            'name' => $prop['name'],
                            'value' => $prop['value'],
                            'type' => 'text'
                        ];
                    }
                }

                $variationPriceData = $this->getProductPrice($variationId);
                $variationWarehouseAvailability = $this->getWarehouseAvailability($variationId, $warehouseId);
                $variationImageUrl = $this->getProductImage($variationFields);
                $variationUrl = $this->getProductUrl($variationFields);

                $variationData = [
                    'id' => ProductIdResolver::getExternalId($variationId),
                    'name' => $variationFields['NAME'],
                    'regular_price' => $variationPriceData['regular_price'],
                    'final_price' => $variationPriceData['final_price'],
                    'warehouses' => $variationWarehouseAvailability['warehouses'],
                ];
                if ($variationImageUrl) {
                    $variationData['img'] = $variationImageUrl;
                }
                if ($variationUrl) {
                    $variationData['url'] = $variationUrl;
                }
                $this->appendVatToResponseItem($variationData, (int)$variationId);
                if (!empty($characteristics)) {
                    $variationData['characteristics'] = $characteristics;
                }
                $variations[] = $variationData;
            }
            
            return $variations;
            
        } catch (\Exception $e) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
            return [];
        }
    }

    /**
     * Получает характеристики товара в формате массива объектов
     *
     * @param int $elementId ID элемента
     * @param int $iblockId ID инфоблока
     * @param array|null $colorMap карта значение цвета → HEX (если null, загружается getColorMap())
     * @param bool $forceColorAsText если true, для свойства «Цвет» везде value как текст, type: text (без hex)
     * @return array Массив характеристик [['code' => ..., 'name' => ..., 'value' => ...], ...]
     */
    private function getProductCharacteristics($elementId, $iblockId, array $colorMap = null, $forceColorAsText = false)
    {
        $characteristics = [];
        
        try {
            // Настройки для свойства «Цвет»
            $colorPropertyId = Option::get('yandex.market', 'SKU_COLOR_PROPERTY', '');
            if ($colorMap === null) {
                $colorMap = $this->getColorMap();
            }
            
            // Получаем настройки модуля для фильтрации свойств
            $useSku = Option::get('yandex.market', 'USE_SKU', 'N');
            $skuPropertiesValue = Option::get('yandex.market', 'SKU_PROPERTIES', '');
            $allowedPropertyIds = [];
            
            if ($useSku === 'Y' && !empty($skuPropertiesValue)) {
                $unserialized = PhpSerializer::decode($skuPropertiesValue);
                if (is_array($unserialized)) {
                    $allowedPropertyIds = array_map('intval', $unserialized);
                }
            }
            if ($useSku === 'Y' && empty($allowedPropertyIds)) {
                return [];
            }
            
            $res = \CIBlockElement::GetByID($elementId);
            if (!$element = $res->GetNextElement()) {
                return [];
            }
            
            $elementProps = $element->GetProperties();
            
            // Получаем список свойств инфоблока
            $propertyIterator = \CIBlockProperty::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    'ACTIVE' => 'Y'
                ]
            );
            
            while ($prop = $propertyIterator->GetNext()) {
                // Если включена фильтрация по настройкам, проверяем, что свойство выбрано
                if (!empty($allowedPropertyIds) && !in_array((int)$prop['ID'], $allowedPropertyIds)) {
                    continue;
                }
                $propCode = strtoupper($prop['CODE'] ?? '');
                
                // Исключаем служебные свойства
                if (stripos($propCode, 'PHOTO') !== false) {
                    continue;
                }
                if (strpos($propCode, 'CML2_') === 0) {
                    continue;
                }
                if ($prop['PROPERTY_TYPE'] === 'F') {
                    continue;
                }
                
                // Получаем значение свойства элемента
                if (!isset($elementProps[$prop['CODE']])) {
                    continue;
                }
                
                $propValue = $elementProps[$prop['CODE']];
                $value = null;
                
                // Получаем значение свойства
                if (isset($propValue['VALUE'])) {
                    if (is_array($propValue['VALUE'])) {
                        $value = $propValue['VALUE'];
                    } else {
                        $value = $propValue['VALUE'];
                    }
                }
                
                // Пропускаем пустые значения
                if (empty($value) && $value !== '0' && $value !== 0) {
                    continue;
                }
                
                // Для списков и справочников получаем текстовое значение.
                // Берём фактический тип из структуры значения свойства элемента.
                $actualPropertyType = isset($propValue['PROPERTY_TYPE']) ? (string)$propValue['PROPERTY_TYPE'] : (string)$prop['PROPERTY_TYPE'];
                if ($actualPropertyType === 'L' || $actualPropertyType === 'E') {
                    if (is_array($value)) {
                        $textValues = [];
                        foreach ($value as $val) {
                            if (empty($val) && $val !== '0' && $val !== 0) {
                                continue;
                            }
                            if ($actualPropertyType === 'L') {
                                if (!empty($propValue['VALUE_ENUM'])) {
                                    if (is_array($propValue['VALUE_ENUM'])) {
                                        foreach ($propValue['VALUE_ENUM'] as $enumText) {
                                            $enumText = trim((string)$enumText);
                                            if ($enumText !== '') {
                                                $textValues[] = $enumText;
                                            }
                                        }
                                    } else {
                                        $enumText = trim((string)$propValue['VALUE_ENUM']);
                                        if ($enumText !== '') {
                                            $textValues[] = $enumText;
                                        }
                                    }
                                    if (!empty($textValues)) {
                                        break;
                                    }
                                }
                                $enumRes = \CIBlockPropertyEnum::GetList(
                                    [],
                                    ['ID' => $val]
                                );
                                if ($enum = $enumRes->GetNext()) {
                                    $textValues[] = $enum['VALUE'];
                                }
                            } else {
                                $elRes = \CIBlockElement::GetByID($val);
                                if ($el = $elRes->GetNext()) {
                                    $textValues[] = $el['NAME'];
                                }
                            }
                        }
                        $value = !empty($textValues) ? (count($textValues) === 1 ? $textValues[0] : $textValues) : $value;
                    } else {
                        if ($actualPropertyType === 'L') {
                            if (!empty($propValue['VALUE_ENUM'])) {
                                $value = $propValue['VALUE_ENUM'];
                            } else {
                                $enumRes = \CIBlockPropertyEnum::GetList(
                                    [],
                                    ['ID' => $value]
                                );
                                if ($enum = $enumRes->GetNext()) {
                                    $value = $enum['VALUE'];
                                }
                            }
                        } else {
                            $elRes = \CIBlockElement::GetByID($value);
                            if ($el = $elRes->GetNext()) {
                                $value = $el['NAME'];
                            }
                        }
                    }
                }

                $isColorProperty = $colorPropertyId !== '' && (string)$prop['ID'] === (string)$colorPropertyId;
                if ($isColorProperty) {
                    $value = $this->normalizeSingleColorValue($value);
                }
                
                // Пропускаем свойства с несколькими значениями (массив)
                if (is_array($value)) {
                    continue;
                }
                
                // Свойство «Цвет»: при forceColorAsText или отсутствии маппинга — value как текст, type: text
                if ($isColorProperty) {
                    $valueText = (string)$value;
                    $hex = ColorMapHelper::getHexFromColorMapOrNull($colorMap, $valueText);
                    $useTextForColor = $forceColorAsText || ($hex === null);
                    if ($useTextForColor) {
                        $characteristics[] = [
                            'code' => $prop['CODE'],
                            'name' => $prop['NAME'],
                            'value' => $value,
                            'type' => 'text'
                        ];
                    } else {
                        $characteristics[] = [
                            'code' => $prop['CODE'],
                            'name' => $prop['NAME'],
                            'value' => $hex,
                            'value_text' => $valueText,
                            'type' => 'color'
                        ];
                    }
                } else {
                    $characteristics[] = [
                        'code' => $prop['CODE'],
                        'name' => $prop['NAME'],
                        'value' => $value,
                        'type' => 'text'
                    ];
                }
            }
        } catch (\Exception $e) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
        }

        return $characteristics;
    }

    /**
     * Приводит штатное multiple-значение цвета с одним заполненным элементом к scalar.
     * Настоящие multi-value массивы остаются массивами и отсекаются существующей защитой.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeSingleColorValue($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $nonEmptyValues = [];
        foreach ($value as $item) {
            if (empty($item) && $item !== '0' && $item !== 0) {
                continue;
            }
            $nonEmptyValues[] = $item;
        }

        if (count($nonEmptyValues) === 1 && !is_array($nonEmptyValues[0])) {
            return $nonEmptyValues[0];
        }

        return $nonEmptyValues;
    }

    /**
     * Возвращает карту соответствия «значение цвета» → HEX только из сохранённых настроек (SKU_COLOR_MAP).
     * Пресет в админке не подставляется — в API только то, что выбрал пользователь.
     *
     * @return array [ valueText => hex, ... ]
     */
    private function getColorMap()
    {
        $colorMapJson = Option::get('yandex.market', 'SKU_COLOR_MAP', '');
        if ($colorMapJson === '') {
            return [];
        }
        $colorMap = is_string($colorMapJson) ? (json_decode($colorMapJson, true) ?: []) : [];
        if (!is_array($colorMap) || empty($colorMap)) {
            return [];
        }
        return ColorMapHelper::normalizeColorMapKeys($colorMap);
    }

    /**
     * Проверяет, есть ли хотя бы одно значение цвета (у основного товара или у вариаций), которого нет в маппинге.
     *
     * @param int $elementId ID элемента (товара или SKU)
     * @param int $iblockId ID инфоблока элемента
     * @param int|null $warehouseId не используется, для совместимости с getProductVariations
     * @param array $colorMap карта значение → HEX
     * @return bool true — если маппинг пустой или хотя бы один цвет не в маппинге
     */
    private function hasUnmappedColorValue($elementId, $iblockId, $warehouseId, array $colorMap)
    {
        $colorPropertyId = Option::get('yandex.market', 'SKU_COLOR_PROPERTY', '');
        if ($colorPropertyId === '') {
            return false;
        }
        $values = $this->getAllColorValuesForItem($elementId, $iblockId, $colorPropertyId);
        return ColorMapHelper::hasUnmappedColorValues($values, $colorMap);
    }

    /**
     * Собирает все текстовые значения свойства «Цвет» у основного товара и у всех вариаций.
     *
     * @param int $elementId ID элемента
     * @param int $iblockId ID инфоблока элемента
     * @param string $colorPropertyId ID свойства «Цвет»
     * @return string[]
     */
    private function getAllColorValuesForItem($elementId, $iblockId, $colorPropertyId)
    {
        $result = [];
        $propRes = \CIBlockProperty::GetByID($colorPropertyId);
        if (!($colorProp = $propRes->GetNext()) || empty($colorProp['CODE'])) {
            return $result;
        }
        $colorCode = $colorProp['CODE'];
        $colorPropType = $colorProp['PROPERTY_TYPE'] ?? '';

        // Значение у основного элемента
        if ((int)$colorProp['IBLOCK_ID'] === (int)$iblockId) {
            $res = \CIBlockElement::GetByID($elementId);
            if ($el = $res->GetNextElement()) {
                $props = $el->GetProperties();
                if (isset($props[$colorCode]['VALUE'])) {
                    $val = $props[$colorCode]['VALUE'];
                    $text = $this->resolvePropertyValueToText($val, $colorPropType);
                    if ($text !== null && $text !== '') {
                        $result[] = $text;
                    }
                }
            }
        }

        // Значения у вариаций: получаем родительский товар (если элемент — оффер) или считаем элемент основным товаром
        $productInfo = \CCatalogSku::GetProductInfo($elementId, $iblockId);
        if (!$productInfo || empty($productInfo['ID'])) {
            // Fallback: GetProductInfo может возвращать пустой результат (оффер в части версий Bitrix или основной товар)
            $catalogInfo = \CCatalogSku::GetInfoByIBlock($iblockId);
            if (!$catalogInfo || !is_array($catalogInfo)) {
                return $result;
            }
            $productIblockId = isset($catalogInfo['PRODUCT_IBLOCK_ID']) ? (int)$catalogInfo['PRODUCT_IBLOCK_ID'] : 0;
            $offersIblockIdFromCatalog = isset($catalogInfo['IBLOCK_ID']) ? (int)$catalogInfo['IBLOCK_ID'] : 0;
            $skuPropertyId = isset($catalogInfo['SKU_PROPERTY_ID']) ? (int)$catalogInfo['SKU_PROPERTY_ID'] : 0;
            // Текущий элемент в инфоблоке офферов — получаем ID основного товара из свойства связи
            if ($offersIblockIdFromCatalog > 0 && (int)$iblockId === $offersIblockIdFromCatalog && $skuPropertyId > 0) {
                $linkPropRes = \CIBlockProperty::GetByID($skuPropertyId);
                $linkProp = $linkPropRes ? $linkPropRes->GetNext() : null;
                if ($linkProp && !empty($linkProp['CODE'])) {
                    $el = \CIBlockElement::GetByID($elementId)->GetNextElement();
                    if ($el) {
                        $props = $el->GetProperties();
                        if (isset($props[$linkProp['CODE']]['VALUE'])) {
                            $linkVal = $props[$linkProp['CODE']]['VALUE'];
                            $productId = is_array($linkVal) ? (int)reset($linkVal) : (int)$linkVal;
                            if ($productId > 0 && $productIblockId > 0) {
                                $productInfo = ['ID' => $productId, 'IBLOCK_ID' => $productIblockId];
                            }
                        }
                    }
                }
            }
            // Текущий элемент — основной товар (инфоблок каталога товаров)
            if ((!$productInfo || empty($productInfo['ID'])) && $productIblockId > 0 && (int)$iblockId === $productIblockId) {
                $productInfo = ['ID' => $elementId, 'IBLOCK_ID' => $iblockId];
            }
            if (!$productInfo || empty($productInfo['ID'])) {
                return $result;
            }
        }
        $skuInfo = \CCatalogSKU::GetInfoByProductIBlock($productInfo['IBLOCK_ID']);
        if (!$skuInfo || empty($skuInfo['IBLOCK_ID'])) {
            return $result;
        }
        $offersIblockId = $skuInfo['IBLOCK_ID'];
        $productPropertyId = $skuInfo['SKU_PROPERTY_ID'] ?? null;
        // Свойство «Цвет» может не входить в список свойств вариаций; проверяем, что оно из инфоблока офферов
        if ((int)$colorProp['IBLOCK_ID'] !== (int)$offersIblockId) {
            return $result;
        }
        $filter = [
            'IBLOCK_ID' => $offersIblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y'
        ];
        if ($productPropertyId) {
            $filter['PROPERTY_' . $productPropertyId] = $productInfo['ID'];
        }
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            $filter,
            false,
            false,
            ['ID']
        );
        // Собираем цвет у каждой вариации (включая текущий элемент, если он в списке офферов)
        while ($variation = $res->GetNextElement()) {
            $vProps = $variation->GetProperties();
            if (!isset($vProps[$colorCode]['VALUE'])) {
                continue;
            }
            $val = $vProps[$colorCode]['VALUE'];
            $text = $this->resolvePropertyValueToText($val, $colorPropType);
            if ($text !== null && $text !== '') {
                $result[] = $text;
            }
        }
        return $result;
    }

    /**
     * Преобразует значение свойства в текст (для списка и привязки к элементам).
     *
     * @param mixed $value
     * @param string $propertyType L|E|S|N
     * @return string|null
     */
    private function resolvePropertyValueToText($value, $propertyType)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            $value = reset($value);
        }
        if ($propertyType === 'L') {
            $enumRes = \CIBlockPropertyEnum::GetList([], ['ID' => $value]);
            if ($enum = $enumRes->GetNext()) {
                return (string)$enum['VALUE'];
            }
        }
        if ($propertyType === 'E') {
            $elRes = \CIBlockElement::GetByID($value);
            if ($el = $elRes->GetNext()) {
                return (string)$el['NAME'];
            }
        }
        return (string)$value;
    }

    /**
     * Получает свойства, которыми отличаются вариации торгового предложения
     * 
     * @param int $offersIblockId ID инфоблока торговых предложений
     * @param int|null $productPropertyId ID свойства связи с товаром (исключаем из списка)
     * @return array Массив свойств [CODE => [NAME, PROPERTY_TYPE, ...]]
     */
    private function getVariationProperties($offersIblockId, $productPropertyId = null)
    {
        $properties = [];
        
        try {
            // Получаем настройки модуля для фильтрации свойств
            $useSku = Option::get('yandex.market', 'USE_SKU', 'N');
            $skuPropertiesValue = Option::get('yandex.market', 'SKU_PROPERTIES', '');
            $allowedPropertyIds = [];
            
            if ($useSku === 'Y' && !empty($skuPropertiesValue)) {
                $unserialized = PhpSerializer::decode($skuPropertiesValue);
                if (is_array($unserialized)) {
                    $allowedPropertyIds = array_map('intval', $unserialized);
                }
            }
            if ($useSku === 'Y' && empty($allowedPropertyIds)) {
                return [];
            }

            $res = \CIBlockProperty::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                [
                    'IBLOCK_ID' => $offersIblockId,
                    'ACTIVE' => 'Y'
                ]
            );
            
            while ($prop = $res->GetNext()) {
                // Исключаем свойство связи с товаром
                if ($productPropertyId && $prop['ID'] == $productPropertyId) {
                    continue;
                }
                
                // Если включена фильтрация по настройкам, проверяем, что свойство выбрано
                if (!empty($allowedPropertyIds) && !in_array((int)$prop['ID'], $allowedPropertyIds)) {
                    continue;
                }
                
                $propCode = strtoupper($prop['CODE'] ?? '');
                
                // Исключаем свойства, содержащие PHOTO в коде
                if (stripos($propCode, 'PHOTO') !== false) {
                    continue;
                }
                
                // Исключаем свойства, начинающиеся с CML2_
                if (strpos($propCode, 'CML2_') === 0) {
                    continue;
                }
                
                // Исключаем свойства типа файл
                if ($prop['PROPERTY_TYPE'] === 'F') {
                    continue;
                }
                
                // Включаем только свойства, которые могут различаться у вариаций
                // Обычно это списки, справочники, строки, числа
                if (in_array($prop['PROPERTY_TYPE'], ['L', 'E', 'S', 'N'])) {
                    $properties[$prop['CODE']] = [
                        'ID' => $prop['ID'],
                        'CODE' => $prop['CODE'],
                        'NAME' => $prop['NAME'],
                        'PROPERTY_TYPE' => $prop['PROPERTY_TYPE']
                    ];
                }
            }
        } catch (\Exception $e) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
        }
        
        return $properties;
    }
}
