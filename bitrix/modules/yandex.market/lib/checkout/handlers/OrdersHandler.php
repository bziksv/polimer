<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;
use Yandex\Market\Checkout\ProductIdResolver;
use Bitrix\Main\Context;
use Bitrix\Sale\Order;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Configuration;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Sale\Registry;
use Yandex\Market\Checkout\Handlers as CheckoutHandlers;
use Yandex\Market\Checkout\Services\BasketItemsGrouper;
use Yandex\Market\Checkout\Services\PromocodeCalculator;
use Yandex\Market\Checkout\Services\BasketPriceValidator;

class OrdersHandler extends BaseHandler
{
    /** @var PromocodeCalculator|null */
    private $promocodeCalculator;

    /** @var BasketPriceValidator|null */
    private $basketPriceValidator;

    /** @var BasketItemsGrouper|null */
    private $basketItemsGrouper;

    public static function getSupportedActions(): array
    {
        return ['placed', 'cancel', 'delivered', ''];
    }

    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        $action = $this->request->get('action');
        
        switch ($action) {
            case 'placed':
                return $this->handleMarkOrderPlaced($orderId);

            case 'cancel':
                return $this->handleCancelOrder($orderId);

            case 'delivered':
                return $this->handleMarkOrderDelivered($orderId);

            default:
                return $this->handleCreateOrder($orderId);
        }
    }
    
    private function handleCreateOrder($orderId = null)
    {
        try {
            if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
                return $this->error('Required modules not available', 500);
            }

            $input = file_get_contents('php://input');
            $requestData = Json::decode($input);

            if (empty($requestData['order_id']) || empty($requestData['warehouse_id']) || 
                empty($requestData['items']) || empty($requestData['customer']) || 
                empty($requestData['delivery'])) {
                return $this->error('Invalid request format: required fields are missing', 400);
            }

            $externalOrderId = $requestData['order_id'];
            // Для старых версий/ретраев: если kit_order_id не передан, считаем kit_order_id = order_id
            $kitOrderId = !empty($requestData['kit_order_id']) ? $requestData['kit_order_id'] : $externalOrderId;
            $warehouseId = $requestData['warehouse_id'];
            $internalStoreId = $this->resolveInternalStoreIdForOrder($warehouseId);
            $grouping = $this->getBasketItemsGrouper()->group($requestData['items']);
            if ($grouping === null) {
                return $this->error('Invalid item price format', 400, self::ERROR_INVALID_INPUT);
            }
            $items = $grouping['items'];
            $requiredQuantitiesByProductId = $grouping['required_quantity_by_product_id'];
            $customer = $requestData['customer'];
            $delivery = $requestData['delivery'];
            $paymentMethod = $requestData['payment_method'] ?? null;
            $promocodes = [];

            if (array_key_exists('promocodes', $requestData)) {
                if (!is_array($requestData['promocodes'])) {
                    return $this->error('Invalid request format: promocodes must be an array', 400);
                }
                $promocodes = $requestData['promocodes'];
            }

            // Проверка дубликата по order_id (XML_ID)
            $existingByXmlId = Order::getList([
                'filter' => ['XML_ID' => $externalOrderId],
                'select' => ['ID', 'XML_ID', 'CANCELED'],
                'limit' => 1
            ])->fetch();

            if ($existingByXmlId) {
                // XML_ID сам по себе не подтверждает принадлежность к YCP: тот же идентификатор
                // может носить заказ из обмена с 1С или созданный оператором вручную. Снимать
                // отмену с чужого заказа нельзя — отвечаем как на занятый идентификатор.
                // Дедупликация по kit_order_id (ниже) в такой проверке не нуждается: там заказ
                // ищется через свойство YANDEX_ORDER_ID, то есть владение следует из способа поиска.
                if (
                    $existingByXmlId['CANCELED'] === 'Y'
                    && $this->isYandexCheckoutOrder((int)$existingByXmlId['ID'])
                ) {
                    return $this->restoreCanceledOrderAsCreated(
                        (int)$existingByXmlId['ID'],
                        $externalOrderId
                    );
                }

                return $this->errorWithData(
                    'Order already exists',
                    409,
                    self::ERROR_CONFLICT,
                    ['order_id' => (string)$existingByXmlId['XML_ID']]
                );
            }

            // Проверка по kit_order_id (свойство YANDEX_ORDER_ID)
            $existingByKitOrderId = OrderPropsValueTable::getList([
                'filter' => [
                    '=VALUE' => $kitOrderId,
                    '=ENTITY_TYPE' => Registry::ENTITY_ORDER,
                    '=PROPERTY.CODE' => 'YANDEX_ORDER_ID'
                ],
                'select' => ['ORDER_ID'],
                'limit' => 1
            ])->fetch();

            if (!empty($existingByKitOrderId['ORDER_ID'])) {
                $previousOrder = Order::getList([
                    'filter' => ['ID' => (int)$existingByKitOrderId['ORDER_ID']],
                    'select' => ['ID', 'XML_ID', 'CANCELED'],
                    'limit' => 1
                ])->fetch();

                if ($previousOrder) {
                    if ($previousOrder['CANCELED'] === 'Y') {
                        return $this->restoreCanceledOrderAsCreated(
                            (int)$previousOrder['ID'],
                            $externalOrderId
                        );
                    }

                    return $this->errorWithData(
                        'Order already exists',
                        409,
                        self::ERROR_CONFLICT,
                        ['order_id' => (string)$previousOrder['XML_ID']]
                    );
                }
            }

            // Ищем существующего пользователя: сначала по email, потом по PERSONAL_PHONE
            $userId = $this->findUser($customer);

            // Если пользователь не найден, создаем нового
            if (!$userId) {
                $createResult = $this->createUser($customer, $externalOrderId);
                if (!$createResult['success']) {
                    return $this->error($createResult['error'], 500);
                }
                $userId = $createResult['userId'];
            }
            
            // Создаем заказ
            $siteId = Context::getCurrent()->getSite();
            $order = Order::create($siteId, $userId);
            $order->setField('XML_ID', $externalOrderId);
            $order->setField('STORE_ID', $internalStoreId);
            
            // Получаем тип плательщика из контекста (по SITE_ID)
            $personTypeId = $this->getPersonTypeIdBySite($siteId);
            if ($personTypeId) {
                $order->setPersonTypeId($personTypeId);
            }

            // Свойства, привязанные к доставке/оплате (например ADDRESS), появляются в коллекции
            // только после назначения службы доставки и платежной системы — заполняем их позже.

            $priceExpectations = $this->getBasketPriceValidator()->capturePriceExpectationsFromItems($items);
            if ($priceExpectations === null) {
                return $this->error('Invalid item price format', 400, self::ERROR_INVALID_INPUT);
            }

            $basket = Basket::create($siteId);
            $basketError = $this->getPromocodeCalculator()->buildBasketFromRequestItems($basket, $items, $siteId);
            if ($basketError !== null) {
                $httpCode = strpos($basketError, 'Product not found') === 0 ? 404 : 400;
                $errorCode = $httpCode === 404 ? self::ERROR_PRODUCT_NOT_FOUND : self::ERROR_INVALID_INPUT;
                return $this->error($basketError, $httpCode, $errorCode);
            }

            if ($this->getBasketPriceValidator()->hasBasketItemCountMismatch($basket, $priceExpectations)) {
                return $this->error('Failed to build basket: item count mismatch', 500, self::ERROR_INTERNAL);
            }

            $order->setBasket($basket);

            $isStoreControl = $this->isStoreControlEnabled();
            $storeControlForShipment = $isStoreControl && !$this->useGeneralStockOnly();

            $deliveryPriceError = $this->validateCheckoutDeliveryPrice($delivery);
            if ($deliveryPriceError !== null) {
                return $this->error($deliveryPriceError, 400, self::ERROR_INVALID_INPUT);
            }

            $this->applyCheckoutShipment($order, $basket, $delivery, $storeControlForShipment, (int)$internalStoreId);

            if (!empty($promocodes)) {
                $promocodeError = $this->getPromocodeCalculator()->applyToOrder($order, (int)$userId, $promocodes);
                if ($promocodeError !== null) {
                    return $this->error($promocodeError, 422, self::ERROR_INVALID_PROMOCODE);
                }

                $promocodeValidationError = $this->getPromocodeCalculator()->validateAppliedPromocodes($promocodes);
                if ($promocodeValidationError !== null) {
                    return $this->error($promocodeValidationError, 422, self::ERROR_INVALID_PROMOCODE);
                }
            }

            $checkResult = $this->checkInventoryConflicts(
                $order,
                $warehouseId,
                $priceExpectations,
                $requiredQuantitiesByProductId
            );
            if (!empty($checkResult['items'])) {
                return $this->errorWithData(
                    'Prices have changed or items are out of stock',
                    409,
                    self::ERROR_INVENTORY_CONFLICT,
                    ['actual_inventory' => $checkResult]
                );
            }

            $this->cementBasketPrices($basket, $priceExpectations);

            $paySystemId = $this->applyCheckoutPayment($order);

            // После доставки и оплаты коллекция свойств содержит related-поля (ADDRESS и т.п.)
            $propertyCollection = $order->getPropertyCollection();
            if (method_exists($propertyCollection, 'refreshRelated')) {
                $propertyCollection->refreshRelated();
            }
            $this->setOrderProperties($order, $customer, $delivery, $externalOrderId, $paymentMethod, $kitOrderId);

            // Сохраняем заказ
            $result = $this->saveYcpOrder($order);
            
            if (!$result->isSuccess()) {
                $errors = $result->getErrorMessages();
                return $this->error('Failed to create order: ' . implode(', ', $errors), 500);
            }
            
            // Проверяем warnings и логируем их (не показываем в ответе)
            $warnings = $result->getWarnings();
            if (!empty($warnings)) {
                $warningMessages = [];
                foreach ($warnings as $warning) {
                    $warningMessages[] = $warning->getMessage();
                }
                // error_log('Order created with warnings: ' . implode(', ', $warningMessages));
            }
            
            // Создаем или обновляем профиль покупателя
            $this->saveBuyerProfile($order, $customer, $delivery);
            
            // Записываем в историю заказа о создании через API
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction(
                    'ORDER',
                    $order->getId(),
                    'ORDER_COMMENTED',
                    $order->getId(),
                    $order,
                    ['COMMENTS' => 'Заказ создан через YCP API. Внешний ID: ' . $externalOrderId]
                );
            }
            
            // Проверяем и корректируем сумму платежа после сохранения
            if ($paySystemId > 0) {
                $paymentCollection = $order->getPaymentCollection();
                $paymentsSum = 0;
                foreach ($paymentCollection as $payment) {
                    $paymentSystemId = (int)$payment->getField('PAY_SYSTEM_ID');
                    // Учитываем только платежи с PAY_SYSTEM_ID > 0 (не системные)
                    if ($paymentSystemId > 0) {
                        $paymentsSum += $payment->getSum();
                    }
                }
                
                $orderPrice = $order->getPrice();
                if (abs($paymentsSum - $orderPrice) > 0.01) {
                    // Корректируем сумму платежа, если не совпадает
                    foreach ($paymentCollection as $payment) {
                        if ((int)$payment->getField('PAY_SYSTEM_ID') === $paySystemId) {
                            $payment->setField('SUM', $orderPrice);
                            $payment->save();
                            break;
                        }
                    }
                }
            }

            $orderNumber = (string) $order->getField('ACCOUNT_NUMBER');
            if ($orderNumber === '') {
                $orderNumber = (string) $order->getId();
            }

            return $this->response([
                'order_id' => $externalOrderId,
                'internal_order_id' => $order->getId(),
                'order_number' => $orderNumber,
                'status' => 'created'
            ], 201);

        } catch (\Exception $e) {
            return $this->error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Снимает отмену с существующего заказа и отвечает как при успешном создании (201).
     *
     * @param int $internalOrderId
     * @param string $externalOrderId order_id из запроса (станет XML_ID при расхождении)
     * @return \Yandex\Market\Checkout\ApiResponse|\Bitrix\Main\HttpResponse|mixed
     */
    private function restoreCanceledOrderAsCreated($internalOrderId, $externalOrderId)
    {
        // Страховка на случай нового вызывающего кода: метод меняет состояние заказа,
        // поэтому чужие (не-YCP) заказы он не трогает ни при каких условиях.
        if (!$this->isYandexCheckoutOrder((int)$internalOrderId)) {
            return $this->error('Order already exists', 409, self::ERROR_CONFLICT);
        }

        $order = Order::load($internalOrderId);
        if (!$order) {
            return $this->error('Failed to load order', 500);
        }

        if ((string)$order->getField('CANCELED') !== 'Y') {
            return $this->errorWithData(
                'Order already exists',
                409,
                self::ERROR_CONFLICT,
                ['order_id' => (string)$order->getField('XML_ID')]
            );
        }

        $order->setField('CANCELED', 'N');
        $order->setField('DATE_CANCELED', null);
        $order->setField('REASON_CANCELED', false);
        $order->setField('EMP_CANCELED_ID', false);

        $statusOnCancel = Option::get('yandex.market', 'STATUS_ON_CANCEL', 'C');
        if ((string)$order->getField('STATUS_ID') === (string)$statusOnCancel) {
            $order->setField('STATUS_ID', 'N');
        }

        if ((string)$order->getField('XML_ID') !== (string)$externalOrderId) {
            $order->setField('XML_ID', $externalOrderId);
        }

        // Не дублируем статусный sync в YCP — это ответ на create со стороны YCP
        $result = $this->saveYcpOrder($order, true);

        if (!$result->isSuccess()) {
            $errors = $result->getErrorMessages();
            return $this->error('Failed to restore order: ' . implode(', ', $errors), 500);
        }

        if (class_exists('\Bitrix\Sale\OrderHistory')) {
            \Bitrix\Sale\OrderHistory::addAction(
                'ORDER',
                $order->getId(),
                'ORDER_COMMENTED',
                $order->getId(),
                $order,
                ['COMMENTS' => 'Отмена снята через YCP API (повторное создание). Внешний ID: ' . $externalOrderId]
            );
        }

        $orderNumber = (string)$order->getField('ACCOUNT_NUMBER');
        if ($orderNumber === '') {
            $orderNumber = (string)$order->getId();
        }

        return $this->response([
            'order_id' => $externalOrderId,
            'internal_order_id' => $order->getId(),
            'order_number' => $orderNumber,
            'status' => 'created'
        ], 201);
    }
    
    private function handleMarkOrderPlaced($orderId = null)
    {
        try {
            // Подключаем необходимые модули
            if (!Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            // Валидация orderId
            if (empty($orderId)) {
                return $this->error('Order ID is required', 400);
            }

            // Ищем заказ по XML_ID
            $orderData = Order::getList([
                'filter' => ['XML_ID' => $orderId],
                'select' => ['ID', 'STATUS_ID'],
                'limit' => 1
            ])->fetch();

            if (!$orderData || !$this->isYandexCheckoutOrder((int)$orderData['ID'])) {
                return $this->error('Order not found', 404);
            }

            // Проверяем, не отменен ли заказ
            if ($orderData['STATUS_ID'] === 'C') {
                return $this->error('Cannot mark as placed - order is already cancelled', 409);
            }

            // Загружаем заказ
            $order = Order::load($orderData['ID']);
            if (!$order) {
                return $this->error('Failed to load order', 500);
            }

            // Тело запроса: order_number → свойство YANDEX_ORDER_NUM
            $placedBody = [];
            $rawPlacedInput = file_get_contents('php://input');
            if ($rawPlacedInput !== '' && $rawPlacedInput !== false) {
                try {
                    $decodedPlaced = Json::decode($rawPlacedInput);
                    $placedBody = is_array($decodedPlaced) ? $decodedPlaced : [];
                } catch (\Exception $e) {
                    $placedBody = [];
                }
            }
            if (array_key_exists('order_number', $placedBody)) {
                $orderNum = $placedBody['order_number'];
                if (is_int($orderNum) || (is_string($orderNum) && $orderNum !== '' && ctype_digit($orderNum))) {
                    $this->setOrderPropertyValue($order, 'YANDEX_ORDER_NUM', (string)(int)$orderNum);
                } elseif (is_float($orderNum) && floor($orderNum) == $orderNum) {
                    $this->setOrderPropertyValue($order, 'YANDEX_ORDER_NUM', (string)(int)$orderNum);
                }
            }

            // Получаем метод оплаты: сначала из запроса, затем из свойств заказа
            $paymentMethod = $this->request->get('payment_method') ?? null;
            if (empty($paymentMethod)) {
                $paymentMethod = $this->getOrderPropertyValue($order, 'PAYMENT_METHOD');
            }

            // Определяем human-readable детали оплаты из online_payment_method / payment_method
            $onlinePaymentMethod = array_key_exists('online_payment_method', $placedBody)
                ? $placedBody['online_payment_method']
                : null;
            $paymentMethodHuman = $this->mapOnlinePaymentMethodToHuman($onlinePaymentMethod, $paymentMethod);
            
            // Если payment_method передан в запросе, обновляем свойство заказа
            if ($this->request->get('payment_method') !== null) {
                $propertyCollection = $order->getPropertyCollection();
                $paymentMethodProperty = null;
                
                // Ищем свойство PAYMENT_METHOD
                foreach ($propertyCollection as $property) {
                    if ($property->getField('CODE') === 'PAYMENT_METHOD') {
                        $paymentMethodProperty = $property;
                        break;
                    }
                }
                
                if ($paymentMethodProperty) {
                    $paymentMethodProperty->setValue($paymentMethod);
                }
            }
            
            // Помечаем заказ как оплаченный только если это не оплата при доставке
            if ($paymentMethod !== 'on_delivery') {
                $paymentCollection = $order->getPaymentCollection();
                $hasPayment = false;
                
                foreach ($paymentCollection as $payment) {
                    $paymentSystemId = (int)$payment->getField('PAY_SYSTEM_ID');
                    // Пропускаем системные платежи (PAY_SYSTEM_ID = 0 или пустой)
                    if ($paymentSystemId <= 0) {
                        continue;
                    }
                    $hasPayment = true;
                    if (!$payment->isPaid()) {
                        $payment->setPaid('Y');
                    }
                }

                // Если платежей нет, создаем платеж
                if (!$hasPayment) {
                    $paySystemId = Option::get('yandex.market', 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
                    if ($paySystemId) {
                        $payment = $paymentCollection->createItem();
                        $payment->setFields([
                            'PAY_SYSTEM_ID' => $paySystemId,
                            'PAY_SYSTEM_NAME' => 'YCP',
                            'SUM' => $order->getPrice(),
                            'CURRENCY' => 'RUB'
                        ]);
                        $payment->setPaid('Y');
                    }
                }
            }

            // Записываем детали оплаты в свойство и в PAY_SYSTEM_NAME платежей
            if ($paymentMethodHuman !== '') {
                $this->setOrderPropertyValue($order, 'PAYMENT_METHOD_DETAIL', $paymentMethodHuman);

                foreach ($order->getPaymentCollection() as $payment) {
                    if ((int)$payment->getField('PAY_SYSTEM_ID') <= 0) {
                        continue;
                    }
                    $payment->setField('PAY_SYSTEM_NAME', 'YCP · ' . $paymentMethodHuman);
                }
            }

            // Устанавливаем статус заказа из настроек модуля
            $statusOnPlaced = Option::get('yandex.market', 'STATUS_ON_PLACED', 'P');
            if (!empty($statusOnPlaced)) {
                $order->setField('STATUS_ID', $statusOnPlaced);
            }

            // Сохраняем изменения
            $result = $this->saveYcpOrder($order);

            if ($result->isSuccess()) {
                // Определяем, был ли установлен флаг оплаты
                $isPaid = ($paymentMethod !== 'on_delivery');
                
                return $this->response([
                    'order_id' => $orderId,
                    'internal_order_id' => $orderData['ID'],
                    'status' => 'placed',
                    'paid' => $isPaid
                ]);
            } else {
                $errors = $result->getErrorMessages();
                return $this->error('Failed to update order: ' . implode(', ', $errors), 500);
            }

        } catch (\Exception $e) {
            return $this->error('Failed to mark order as placed: ' . $e->getMessage(), 500);
        }
    }
    
    private function handleCancelOrder($orderId = null)
    {
        try {
            // Подключаем необходимые модули
            if (!Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            // Валидация orderId
            if (empty($orderId)) {
                return $this->error('Order ID is required', 400);
            }

            // Ищем заказ по XML_ID (order_id)
            $orderData = Order::getList([
                'filter' => ['XML_ID' => $orderId],
                'select' => ['ID', 'STATUS_ID', 'CANCELED'],
                'limit' => 1
            ])->fetch();

            if (!$orderData || !$this->isYandexCheckoutOrder((int)$orderData['ID'])) {
                return $this->error('Order not found', 404);
            }

            // Проверяем, не отменен ли уже заказ
            if ($orderData['CANCELED'] === 'Y') {
                // Заказ уже отменен, возвращаем успешный ответ
                return $this->response([
                    'order_id' => $orderId,
                    'internal_order_id' => $orderData['ID'],
                    'status' => 'cancelled',
                    'message' => 'Order is already cancelled'
                ]);
            }

            // Загружаем заказ
            $order = Order::load($orderData['ID']);
            if (!$order) {
                return $this->error('Failed to load order', 500);
            }

            // Устанавливаем признак отмены
            $order->setField('CANCELED', 'Y');
            $order->setField('DATE_CANCELED', new \Bitrix\Main\Type\DateTime());
            
            // Устанавливаем статус заказа из настроек модуля
            $statusOnCancel = Option::get('yandex.market', 'STATUS_ON_CANCEL', 'C');
            if (!empty($statusOnCancel)) {
                $order->setField('STATUS_ID', $statusOnCancel);
            }

            // Сохраняем изменения (отмена уже пришла из YCP — не дублируем cancel в API)
            $result = $this->saveYcpOrder($order, true);

            if ($result->isSuccess()) {
                // Записываем в историю заказа об отмене через API
                if (class_exists('\Bitrix\Sale\OrderHistory')) {
                    \Bitrix\Sale\OrderHistory::addAction(
                        'ORDER',
                        $order->getId(),
                        'ORDER_COMMENTED',
                        $order->getId(),
                        $order,
                        ['COMMENTS' => 'Заказ отменен через YCP API. Внешний ID: ' . $orderId]
                    );
                }
                
                return $this->response([
                    'order_id' => $orderId,
                    'internal_order_id' => $orderData['ID'],
                    'status' => 'cancelled'
                ]);
            } else {
                $errors = $result->getErrorMessages();
                return $this->error('Failed to cancel order: ' . implode(', ', $errors), 500);
            }

        } catch (\Exception $e) {
            return $this->error('Failed to cancel order: ' . $e->getMessage(), 500);
        }
    }
    
    private function handleMarkOrderDelivered($orderId = null)
    {
        try {
            // Подключаем необходимые модули
            if (!Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            // Валидация orderId
            if (empty($orderId)) {
                return $this->error('Order ID is required', 400);
            }

            // Получаем данные из тела запроса
            $input = file_get_contents('php://input');
            $requestData = Json::decode($input);

            // Валидация запроса
            if (empty($requestData['purchased_items'])) {
                return $this->error('Invalid request format: purchased_items is required', 400);
            }

            // Ищем заказ по XML_ID
            $orderData = Order::getList([
                'filter' => ['XML_ID' => $orderId],
                'select' => ['ID', 'STATUS_ID', 'CANCELED'],
                'limit' => 1
            ])->fetch();

            if (!$orderData || !$this->isYandexCheckoutOrder((int)$orderData['ID'])) {
                return $this->error('Order not found', 404);
            }

            // Проверяем, не отменен ли заказ
            if ($orderData['CANCELED'] === 'Y') {
                return $this->error('Cannot mark as delivered - order is cancelled', 409);
            }

            // Загружаем заказ
            $order = Order::load($orderData['ID']);
            if (!$order) {
                return $this->error('Failed to load order', 500);
            }

            // Проверяем, не отгружен ли уже заказ
            $shipmentCollection = $order->getShipmentCollection();
            foreach ($shipmentCollection as $shipment) {
                if (!$shipment->isSystem() && $shipment->getField('DEDUCTED') === 'Y') {
                    //return $this->error('Order already delivered', 409);
                    return $this->response([
                        'order_id' => $orderId,
                        'internal_order_id' => $orderData['ID'],
                        'status' => 'delivered',
                        'message' => 'Order already delivered'
                    ]);
                }
            }

            // Создаем карту товаров из запроса (внутренний ID => quantity)
            $purchasedItemsMap = [];
            foreach ($requestData['purchased_items'] as $purchasedItem) {
                $internalId = ProductIdResolver::resolveToInternalId($purchasedItem['id']);
                if ($internalId !== null) {
                    $q = isset($purchasedItem['quantity']) && (string) $purchasedItem['quantity'] !== '' ? intval($purchasedItem['quantity']) : 1;
                    $q = $q > 0 ? $q : 1;
                    $purchasedItemsMap[$internalId] = ($purchasedItemsMap[$internalId] ?? 0) + $q;
                }
            }

            // При запросе delivered товар уже отгружен - проверка остатков не требуется

            // Обновляем корзину заказа - приводим к товарам из payload.
            // Несколько ценовых строк одного продукта сохраняются в исходном порядке.
            // Резервирование снимается автоматически при изменении корзины.
            $basket = $order->getBasket();

            $basketItemsByProductId = [];
            foreach ($basket as $basketItem) {
                $productId = (int)$basketItem->getProductId();
                $basketItemsByProductId[$productId][] = $basketItem;
            }

            foreach ($basketItemsByProductId as $productId => $basketItems) {
                $currentQuantities = [];
                foreach ($basketItems as $basketItem) {
                    $currentQuantities[] = (int)$basketItem->getQuantity();
                }

                $allocation = $this->getBasketItemsGrouper()->allocateQuantityAcrossRows(
                    $currentQuantities,
                    (int)($purchasedItemsMap[$productId] ?? 0)
                );

                foreach ($basketItems as $index => $basketItem) {
                    $newQuantity = $allocation['quantities'][$index];
                    if ($newQuantity <= 0) {
                        $basketItem->delete();
                    } elseif ($newQuantity !== (int)$basketItem->getQuantity()) {
                        $basketItem->setField('QUANTITY', $newQuantity);
                    }
                }

                unset($purchasedItemsMap[$productId]);
                if ($allocation['excess'] > 0) {
                    $purchasedItemsMap[$productId] = $allocation['excess'];
                }
            }

            // Добавляем товары, отсутствующие в заказе, и excess quantity.
            foreach ($purchasedItemsMap as $productId => $quantity) {
                if ($quantity > 0) {
                    // Получаем информацию о товаре
                    $product = \Bitrix\Iblock\ElementTable::getById($productId)->fetch();
                    if (!$product) {
                        continue; // Товар не найден
                    }
                    
                    // Получаем цену товара
                    \Bitrix\Main\Loader::includeModule('catalog');
                    $optimalPrice = \CCatalogProduct::GetOptimalPrice(
                        $productId,
                        1, // Используем 1 для получения цены
                        [],
                        'N',
                        [],
                        \Bitrix\Main\Context::getCurrent()->getSite()
                    );
                    
                    $price = 0;
                    if ($optimalPrice && isset($optimalPrice['RESULT_PRICE'])) {
                        $price = floatval($optimalPrice['RESULT_PRICE']['DISCOUNT_PRICE']);
                    }
                    
                    // Создаем товар в корзине
                    $basketItem = $basket->createItem('catalog', $productId);
                    $fields = [
                        'QUANTITY' => $quantity,
                        'CURRENCY' => 'RUB',
                        'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                        'CUSTOM_PRICE' => 'Y',
                        'PRICE' => $price,
                        'BASE_PRICE' => $price,
                        'NAME' => $product['NAME']
                    ];
                    
                    // Совместимость с Bitrix 18.5: CatalogProvider может отсутствовать
                    if (class_exists('\\Bitrix\\Catalog\\Product\\CatalogProvider')) {
                        $fields['PRODUCT_PROVIDER_CLASS'] = '\\Bitrix\\Catalog\\Product\\CatalogProvider';
                    }
                    
                    $setFieldsResult = $basketItem->setFields($fields);
                    
                    // Если не удалось добавить товар - удаляем его из корзины
                    if (!$setFieldsResult->isSuccess()) {
                        $basketItem->delete();
                    }
                }
            }

            // Получаем отгрузки и склад заказа (для складского учёта при списании)
            $shipmentCollection = $order->getShipmentCollection();
            $warehouseId = (int)$order->getField('STORE_ID');
            if ($warehouseId <= 0) {
                $warehouseId = (int)$this->getOrderPropertyValue($order, 'STORE_ID');
            }
            $isStoreControl = $this->isStoreControlEnabled();
            $storeControlForShipment = $isStoreControl && !$this->useGeneralStockOnly();

            // Отмечаем отгрузки как доставленные
            foreach ($shipmentCollection as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }

                if ($storeControlForShipment && $warehouseId > 0) {
                    $shipment->setStoreId($warehouseId);
                }

                // Устанавливаем признак отгрузки
                $shipment->setField('DEDUCTED', 'Y');
                
                // Устанавливаем дату отгрузки
                if (!empty($requestData['delivered_at'])) {
                    try {
                        $deliveredDate = new \Bitrix\Main\Type\DateTime($requestData['delivered_at']);
                        $shipment->setField('DATE_DEDUCTED', $deliveredDate);
                    } catch (\Exception $e) {
                        // Если не удалось распарсить дату, используем текущую
                        $shipment->setField('DATE_DEDUCTED', new \Bitrix\Main\Type\DateTime());
                    }
                } else {
                    $shipment->setField('DATE_DEDUCTED', new \Bitrix\Main\Type\DateTime());
                }
                
                // Устанавливаем признак доставки
                $shipment->setField('STATUS_ID', 'DF'); // Доставлен

                // Синхронизируем отгрузку с обновленной корзиной
                $shipmentItemCollection = $shipment->getShipmentItemCollection();
                
                // Удаляем все текущие позиции из отгрузки
                foreach ($shipmentItemCollection as $shipmentItem) {
                    if ($shipmentItem->getBasketCode() !== null) {
                        $shipmentItem->delete();
                    }
                }
                
                // Добавляем актуальные позиции из корзины (только не удаленные).
                // Новые basket rows ещё не имеют ID, но уже должны попасть в shipment.
                foreach ($basket as $basketItem) {
                    if ($basketItem->getQuantity() > 0) {
                        $shipmentItem = $shipmentItemCollection->createItem($basketItem);
                        if ($shipmentItem) {
                            $shipmentItem->setQuantity($basketItem->getQuantity());
                            // При включённом складском учёте задаём склад для списания (как при создании заказа)
                            if ($storeControlForShipment && $warehouseId > 0 && method_exists($shipmentItem, 'getShipmentItemStoreCollection')) {
                                $shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
                                $storeItem = $shipmentItemStoreCollection->createItem($basketItem);
                                $storeItem->setField('STORE_ID', $warehouseId);
                                $storeItem->setField('QUANTITY', (float)$basketItem->getQuantity());
                            }
                        }
                    }
                }
            }

            // Устанавливаем статус заказа из настроек модуля
            $statusOnDelivered = Option::get('yandex.market', 'STATUS_ON_DELIVERED', 'F');
            if (!empty($statusOnDelivered)) {
                $order->setField('STATUS_ID', $statusOnDelivered);
            }

            // Всегда помечаем заказ как оплаченный при доставке
            $paymentCollection = $order->getPaymentCollection();
            $hasPayment = false;
            
            foreach ($paymentCollection as $payment) {
                $paymentSystemId = (int)$payment->getField('PAY_SYSTEM_ID');
                // Пропускаем системные платежи (PAY_SYSTEM_ID = 0 или пустой)
                if ($paymentSystemId <= 0) {
                    continue;
                }
                $hasPayment = true;
                if (!$payment->isPaid()) {
                    $payment->setPaid('Y');
                }
            }

            // Если платежей нет, создаем платеж
            if (!$hasPayment) {
                $paySystemId = Option::get('yandex.market', 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
                if ($paySystemId) {
                    $payment = $paymentCollection->createItem();
                    $payment->setFields([
                        'PAY_SYSTEM_ID' => $paySystemId,
                        'PAY_SYSTEM_NAME' => 'YCP',
                        'SUM' => $order->getPrice(),
                        'CURRENCY' => 'RUB'
                    ]);
                    $payment->setPaid('Y');
                }
            }

            // Комментарий к заказу больше не используется, данные в свойствах заказа

            // Сохраняем изменения (delivered пришёл из YCP — не дублируем complete в API)
            $result = $this->saveYcpOrder($order, true);

            if ($result->isSuccess()) {
                // Записываем в историю заказа об изменении статуса на "доставлен" через API
                if (class_exists('\Bitrix\Sale\OrderHistory')) {
                    \Bitrix\Sale\OrderHistory::addAction(
                        'ORDER',
                        $order->getId(),
                        'ORDER_COMMENTED',
                        $order->getId(),
                        $order,
                        ['COMMENTS' => 'Заказ отмечен как доставленный через YCP API. Внешний ID: ' . $orderId]
                    );
                }
                
                return $this->response([
                    'order_id' => $orderId,
                    'internal_order_id' => $orderData['ID'],
                    'status' => 'delivered'
                ]);
            } else {
                $errors = $result->getErrorMessages();
                return $this->error('Failed to mark order as delivered: ' . implode(', ', $errors), 500);
            }

        } catch (\Exception $e) {
            return $this->error('Failed to mark order as delivered: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Внутренний ID склада для заказа: в режиме общего остатка в API склад «1» (виртуальный); в b_catalog_store запись с ID 1 может отсутствовать.
     */
    private function resolveInternalStoreIdForOrder($warehouseId)
    {
        $wid = (int) $warehouseId;
        if ($this->useGeneralStockOnly()) {
            if ($wid === 1) {
                $row = StoreTable::getById(1)->fetch();

                return $row ? 1 : 0;
            }

            return $wid;
        }

        return $wid;
    }

    /**
     * Конфликты при режиме «только общий остаток»: остаток из ProductTable, в ответе виртуальный склад id=1.
     */
    private function checkInventoryConflictsUsingGeneralStockOnly(
        Order $order,
        array $priceExpectations,
        array $requiredQuantitiesByProductId
    ): array {
        $conflicts = [];
        $basket = $order->getBasket();
        if (!$basket) {
            return [];
        }

        $quantityTraceEnabled = Option::get('catalog', 'default_quantity_trace', 'N') === 'Y';
        $gwId = $this->getGeneralWarehouseForApi()['id'];
        $basketPosition = 0;
        foreach ($basket as $basketItem) {
            $expectedPrices = $priceExpectations[$basketPosition] ?? null;
            $basketPosition++;

            $productId = (int)$basketItem->getProductId();
            if ($productId <= 0) {
                continue;
            }

            $requestedQuantity = $requiredQuantitiesByProductId[$productId];

            $product = ProductTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['ID', 'QUANTITY'],
            ])->fetch();

            $availableQuantity = $product ? (float) $product['QUANTITY'] : 0;
            $basketPrices = $this->getBasketPriceValidator()->getBasketItemPrices($basketItem);

            $hasConflict = false;
            $sellWithoutStockCheck = Option::get('yandex.market', 'SELL_WITHOUT_STOCK_CHECK', 'N') === 'Y';
            if ($quantityTraceEnabled && !$sellWithoutStockCheck && $availableQuantity < $requestedQuantity) {
                $hasConflict = true;
            }

            $hasConflict = $hasConflict || $this->getBasketPriceValidator()->hasBasketPriceConflict($basketItem, $expectedPrices);

            if ($hasConflict) {
                $conflicts[] = [
                    'id' => ProductIdResolver::getExternalId($productId),
                    'warehouses' => [
                        [
                            'id' => $gwId,
                            'available_quantity' => $availableQuantity,
                            'regular_price' => $basketPrices['regular_price'],
                            'final_price' => $basketPrices['final_price'],
                        ],
                    ],
                ];
            }
        }

        return $conflicts ? ['items' => $conflicts] : [];
    }

    /**
     * Проверить конфликты наличия и цен: остатки + basket vs priceExpectations из items запроса.
     */
    private function checkInventoryConflicts(
        Order $order,
        $warehouseId,
        array $priceExpectations,
        array $requiredQuantitiesByProductId
    ): array {
        if ($this->useGeneralStockOnly()) {
            return $this->checkInventoryConflictsUsingGeneralStockOnly(
                $order,
                $priceExpectations,
                $requiredQuantitiesByProductId
            );
        }

        $basket = $order->getBasket();
        if (!$basket) {
            return [];
        }

        $conflicts = [];
        $quantityTraceEnabled = Option::get('catalog', 'default_quantity_trace', 'N') === 'Y';
        $isStoreControl = Configuration::useStoreControl();
        // Складской учёт выключен — остаток берём общий (ProductTable.QUANTITY)
        if (!$isStoreControl) {
            $basketPosition = 0;
            foreach ($basket as $basketItem) {
                $expectedPrices = $priceExpectations[$basketPosition] ?? null;
                $basketPosition++;

                $productId = (int)$basketItem->getProductId();
                if ($productId <= 0) {
                    continue;
                }

                $requestedQuantity = $requiredQuantitiesByProductId[$productId];

                $product = ProductTable::getList([
                    'filter' => ['ID' => $productId],
                    'select' => ['ID', 'QUANTITY']
                ])->fetch();

                $availableQuantity = $product ? (float)$product['QUANTITY'] : 0;
                $basketPrices = $this->getBasketPriceValidator()->getBasketItemPrices($basketItem);

                $hasConflict = false;
                $sellWithoutStockCheck = Option::get('yandex.market', 'SELL_WITHOUT_STOCK_CHECK', 'N') === 'Y';
                if ($quantityTraceEnabled && !$sellWithoutStockCheck && $availableQuantity < $requestedQuantity) {
                    $hasConflict = true;
                }

                $hasConflict = $hasConflict || $this->getBasketPriceValidator()->hasBasketPriceConflict($basketItem, $expectedPrices);

                if ($hasConflict) {
                    // Проверяем наличие складов и остатков по складам
                    $hasWarehouses = $this->hasWarehouses();
                    $hasStock = $this->hasWarehouseStock($productId);
                    
                    // Если общий остаток > 0, но складов нет или остатков по складам нет - используем виртуальный склад
                    if ($availableQuantity > 0 && (!$hasWarehouses || !$hasStock)) {
                        $virtualWarehouse = $this->getVirtualWarehouse();
                        $warehouseIdForResponse = $virtualWarehouse['id'];
                    } else {
                        // Получаем первый активный склад
                        $firstWarehouse = StoreTable::getList([
                            'filter' => ['ACTIVE' => 'Y'],
                            'select' => ['ID', 'XML_ID'],
                            'order' => ['ID' => 'ASC'],
                            'limit' => 1
                        ])->fetch();
                        
                        $warehouseIdForResponse = '1'; // По умолчанию виртуальный склад
                        if ($firstWarehouse) {
                            $warehouseIdForResponse = (string)$firstWarehouse['ID'];
                        }
                    }

                    $conflicts[] = [
                        'id' => ProductIdResolver::getExternalId($productId),
                        'warehouses' => [
                            [
                                'id' => $warehouseIdForResponse,
                                'available_quantity' => $availableQuantity,
                                'regular_price' => $basketPrices['regular_price'],
                                'final_price' => $basketPrices['final_price']
                            ]
                        ]
                    ];
                }
            }

            return $conflicts ? ['items' => $conflicts] : [];
        }

        // Складской учёт включен - работаем со складами
        // Находим склад по XML_ID или ID
        $store = \Bitrix\Catalog\StoreTable::getList([
            'filter' => [
                //'LOGIC' => 'OR',
                //['XML_ID' => $warehouseId],
                'ID' => $warehouseId
            ],
            'select' => ['ID', 'XML_ID'],
            'limit' => 1
        ])->fetch();

        if (!$store) {
            foreach ($basket as $basketItem) {
                $productId = (int)$basketItem->getProductId();
                if ($productId <= 0) {
                    continue;
                }
                // Получаем общий остаток товара
                $product = ProductTable::getList([
                    'filter' => ['ID' => $productId],
                    'select' => ['ID', 'QUANTITY', 'QUANTITY_RESERVED']
                ])->fetch();

                $totalQuantity = $product ? (float)$product['QUANTITY'] : 0;
                // Не вычитаем резервы - показываем общее количество (как в checkBasket)
                $availableQuantity = $totalQuantity;
                
                // Проверяем наличие складов и остатков по складам
                $hasWarehouses = $this->hasWarehouses();
                $hasStock = $this->hasWarehouseStock($productId);
                
                // Если общий остаток > 0, но складов нет или остатков по складам нет - используем виртуальный склад
                if ($availableQuantity > 0 && (!$hasWarehouses || !$hasStock)) {
                    $virtualWarehouse = $this->getVirtualWarehouse();
                    $conflicts[] = [
                        'id' => ProductIdResolver::getExternalId($productId),
                        'warehouses' => [
                            [
                                'id' => $virtualWarehouse['id'],
                                'available_quantity' => $availableQuantity,
                                'regular_price' => 0,
                                'final_price' => 0
                            ]
                        ]
                    ];
                } else {
                    $conflicts[] = [
                        'id' => ProductIdResolver::getExternalId($productId),
                        'warehouses' => []
                    ];
                }
            }

            return ['items' => $conflicts];
        }

        $storeId = $store['ID'];
        $storeXmlId = (string) $store['ID'];

        $basketPosition = 0;
        foreach ($basket as $basketItem) {
            $expectedPrices = $priceExpectations[$basketPosition] ?? null;
            $basketPosition++;

            $productId = (int)$basketItem->getProductId();
            if ($productId <= 0) {
                continue;
            }

            $requestedQuantity = $requiredQuantitiesByProductId[$productId];

            // Получаем общий остаток товара
            $product = ProductTable::getList([
                'filter' => ['ID' => $productId],
                'select' => ['ID', 'QUANTITY', 'QUANTITY_RESERVED']
            ])->fetch();

            $totalQuantity = $product ? (float)$product['QUANTITY'] : 0;
            // Не вычитаем резервы - показываем общее количество (как в checkBasket)
            $totalAvailableQuantity = $totalQuantity;

            // Проверяем наличие складов в системе и остатков по складам (как в checkBasket)
            $hasWarehouses = $this->hasWarehouses();
            $hasStock = $this->hasWarehouseStock($productId);
            
            // Если общий остаток > 0, но складов нет или остатков по складам нет - используем виртуальный склад
            if ($totalAvailableQuantity > 0 && (!$hasWarehouses || !$hasStock)) {
                $availableQuantity = $totalAvailableQuantity;
                $virtualWarehouse = $this->getVirtualWarehouse();
                $storeXmlId = $virtualWarehouse['id'];
            } else {
                // Проверяем наличие на складе (используем внутренний ID склада)
                $storeProduct = StoreProductTable::getList([
                    'filter' => [
                        'PRODUCT_ID' => $productId,
                        'STORE_ID' => $storeId
                    ],
                    'select' => ['AMOUNT']
                ])->fetch();

                $availableQuantity = $storeProduct ? (int)$storeProduct['AMOUNT'] : 0;
            }

            $basketPrices = $this->getBasketPriceValidator()->getBasketItemPrices($basketItem);

            $hasConflict = false;
            $sellWithoutStockCheck = Option::get('yandex.market', 'SELL_WITHOUT_STOCK_CHECK', 'N') === 'Y';
            if ($quantityTraceEnabled && !$sellWithoutStockCheck && $availableQuantity < $requestedQuantity) {
                $hasConflict = true;
            }

            $hasConflict = $hasConflict || $this->getBasketPriceValidator()->hasBasketPriceConflict($basketItem, $expectedPrices);

            if ($hasConflict) {
                $conflicts[] = [
                    'id' => ProductIdResolver::getExternalId($productId),
                    'warehouses' => [
                        [
                            'id' => $storeXmlId,
                            'available_quantity' => $availableQuantity,
                            'regular_price' => $basketPrices['regular_price'],
                            'final_price' => $basketPrices['final_price']
                        ]
                    ]
                ];
            }
        }

        return $conflicts ? ['items' => $conflicts] : [];
    }

    private function getPromocodeCalculator(): PromocodeCalculator
    {
        if ($this->promocodeCalculator === null) {
            $this->promocodeCalculator = new PromocodeCalculator();
        }

        return $this->promocodeCalculator;
    }

    private function getBasketPriceValidator(): BasketPriceValidator
    {
        if ($this->basketPriceValidator === null) {
            $this->basketPriceValidator = new BasketPriceValidator();
        }

        return $this->basketPriceValidator;
    }

    private function getBasketItemsGrouper(): BasketItemsGrouper
    {
        if ($this->basketItemsGrouper === null) {
            $this->basketItemsGrouper = new BasketItemsGrouper();
        }

        return $this->basketItemsGrouper;
    }

    /**
     * Назначить службу доставки и позиции отгрузки.
     * Учитывает профиль доставки: если в настройках выбран родитель с профилями — берём первый профиль.
     */
    private function applyCheckoutShipment(Order $order, Basket $basket, array $delivery, bool $storeControlForShipment, int $internalStoreId): void
    {
        $deliveryFields = $this->resolveCheckoutDeliveryFields();
        if ($deliveryFields === null) {
            return;
        }

        $deliveryId = (int)$deliveryFields['ID'];
        $deliveryName = (string)($deliveryFields['NAME'] ?? 'YCP');
        if ($deliveryName === '') {
            $deliveryName = 'YCP';
        }

        $deliveryPrice = $this->resolveCheckoutDeliveryPrice($delivery);

        $shipmentCollection = $order->getShipmentCollection();
        $deliveryService = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId);

        if ($deliveryService) {
            $shipment = $shipmentCollection->createItem($deliveryService);
        } else {
            $shipment = $shipmentCollection->createItem();
            $shipment->setField('DELIVERY_ID', $deliveryId);
            $shipment->setField('DELIVERY_NAME', $deliveryName);
        }

        $shipment->setFields([
            'CUSTOM_PRICE_DELIVERY' => 'Y',
            'BASE_PRICE_DELIVERY' => $deliveryPrice,
            'PRICE_DELIVERY' => $deliveryPrice,
            'CURRENCY' => $order->getCurrency() ?: 'RUB',
        ]);

        if ($storeControlForShipment && $internalStoreId > 0) {
            $shipment->setStoreId($internalStoreId);
        }

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($basket as $basketItem) {
            $shipmentItem = $shipmentItemCollection->createItem($basketItem);

            if ($storeControlForShipment && $internalStoreId > 0 && method_exists($shipmentItem, 'getShipmentItemStoreCollection')) {
                $shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();
                $storeItem = $shipmentItemStoreCollection->createItem($basketItem);
                $storeItem->setField('STORE_ID', $internalStoreId);
                $storeItem->setField('QUANTITY', (float)$basketItem->getQuantity());
            }

            $shipmentItem->setQuantity($basketItem->getQuantity());
        }
    }

    /**
     * @return string|null текст ошибки или null, если цена допустима
     */
    private function validateCheckoutDeliveryPrice(array $delivery): ?string
    {
        foreach (['price', 'cost'] as $field) {
            if (!array_key_exists($field, $delivery) || $delivery[$field] === null || $delivery[$field] === '') {
                continue;
            }
            if (!is_numeric($delivery[$field])) {
                return 'Invalid delivery.price format';
            }
            if ((float)$delivery[$field] < 0) {
                return 'delivery.price must be >= 0';
            }
        }

        return null;
    }

    private function resolveCheckoutDeliveryPrice(array $delivery): float
    {
        if (isset($delivery['price']) && is_numeric($delivery['price'])) {
            return max(0.0, (float)$delivery['price']);
        }
        if (isset($delivery['cost']) && is_numeric($delivery['cost'])) {
            return max(0.0, (float)$delivery['cost']);
        }

        return 0.0;
    }

    /**
     * Резолв службы/профиля доставки из YANDEX_KIT_DELIVERY_ID.
     * Если выбран родитель, у которого есть профили — возвращает первый профиль.
     *
     * @return array|null поля службы доставки
     */
    private function resolveCheckoutDeliveryFields(): ?array
    {
        $deliveryId = (int)Option::get('yandex.market', 'YANDEX_KIT_DELIVERY_ID', 0);
        if ($deliveryId <= 0) {
            return null;
        }

        $fields = \Bitrix\Sale\Delivery\Services\Manager::getById($deliveryId);
        if (!$fields || empty($fields['ID'])) {
            return null;
        }

        // Уже профиль — используем как есть
        if ((int)($fields['PARENT_ID'] ?? 0) > 0) {
            return $fields;
        }

        $className = (string)($fields['CLASS_NAME'] ?? '');
        $canHasProfiles = (
            $className !== ''
            && class_exists($className)
            && method_exists($className, 'canHasProfiles')
            && $className::canHasProfiles()
        );

        if (!$canHasProfiles) {
            return $fields;
        }

        // Родитель с профилями: для отгрузки нужен конкретный профиль
        // (getByParentId смотрит только ACTIVE=Y, поэтому читаем Table напрямую)
        $profile = \Bitrix\Sale\Delivery\Services\Table::getList([
            'filter' => ['=PARENT_ID' => $deliveryId],
            'select' => ['ID'],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        if (!$profile) {
            return $fields;
        }

        $profileFields = \Bitrix\Sale\Delivery\Services\Manager::getById((int)$profile['ID']);

        return $profileFields ?: $fields;
    }

    /**
     * Назначить служебную платежную систему. Возвращает ID или 0.
     */
    private function applyCheckoutPayment(Order $order): int
    {
        $paySystemId = (int)Option::get('yandex.market', 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
        if ($paySystemId <= 0) {
            return 0;
        }

        $paymentCollection = $order->getPaymentCollection();

        $paySystem = \Bitrix\Sale\PaySystem\Manager::getById($paySystemId);
        $paySystemName = 'YCP';
        if ($paySystem !== false && isset($paySystem['NAME'])) {
            $paySystemName = $paySystem['NAME'];
        }

        $paySystemService = \Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId);

        if ($paySystemService) {
            $payment = $paymentCollection->createItem($paySystemService);
        } else {
            $payment = $paymentCollection->createItem();

            if (method_exists($payment, 'setFieldsNoDemand')) {
                $payment->setFieldsNoDemand([
                    'PAY_SYSTEM_ID' => $paySystemId,
                    'PAY_SYSTEM_NAME' => $paySystemName,
                ]);
            } else {
                $payment->setFields([
                    'PAY_SYSTEM_ID' => $paySystemId,
                    'PAY_SYSTEM_NAME' => $paySystemName,
                ]);
            }
        }

        $payment->setField('SUM', $order->getPrice());
        $payment->setField('CURRENCY', $order->getCurrency() ?: 'RUB');
        $payment->setPaid('N');

        return $paySystemId;
    }

    /**
     * Установить свойства заказа (покупатель и доставка).
     * Вызывать после назначения доставки и оплаты, иначе related-свойства (ADDRESS) не попадут в коллекцию.
     */
    private function setOrderProperties($order, $customer, $delivery, $externalOrderId = null, $paymentMethod = null, $kitOrderId = null)
    {
        $propertyCollection = $order->getPropertyCollection();

        $address = $delivery['address'] ?? [];
        $isPickup = ($delivery['type'] === 'pickup_point');

        // Извлекаем end_date и end_time из новой структуры delivery_date_interval
        $endDate = null;
        $endTime = null;
        $timeZone = null;

        $deliveryInterval = $delivery['delivery_date_interval'] ?? [];
        if (!empty($deliveryInterval['end_interval'])) {
            $endInterval = $deliveryInterval['end_interval'];
            $endDate = $endInterval['date'] ?? null;
            $endTime = $endInterval['time'] ?? null;
        }
        $timeZone = $deliveryInterval['time_zone'] ?? null;

        $deliveryDate = $this->formatDeliveryDateFromEndDateTime($endDate, $endTime, $timeZone);

        // Маппинг данных на свойства заказа
        $propertyMapping = [
            // Внешний ID заказа
            'EXTERNAL_ORDER_ID' => $externalOrderId ?? '',
            'ORDER_ID' => $externalOrderId ?? '',
            'YANDEX_ORDER_ID' => $kitOrderId ?? '',

            // Данные покупателя
            'FIO' => $customer['full_name'] ?? '',
            'NAME' => $customer['full_name'] ?? '',
            'PHONE' => $customer['phone'] ?? '',
            'EMAIL' => $customer['email'] ?? '',

            // Данные доставки
            'DELIVERY_TYPE' => $delivery['service_display_name'] ?? '',
            'DELIVERY_SERVICE' => strtoupper($delivery['service'] ?? ''),
            'DELIVERY_DATE' => $deliveryDate,

            // Тип оплаты
            'PAYMENT_METHOD' => $paymentMethod ?? '',

            // Адрес доставки / ПВЗ
            'ADDRESS' => $address['address'] ?? '',
            'CITY' => $address['city'] ?? '',
            'STREET' => $address['street'] ?? '',
            'BUILDING' => $address['building'] ?? '',
            'APARTMENT' => $address['apartment'] ?? '',
            'ENTRANCE' => $address['entrance'] ?? '',
            'FLOOR' => $address['floor'] ?? '',
            'INTERCOM' => $address['intercom'] ?? '',
            'PICKUP_POINT_ID' => $address['pickup_point_id'] ?? '',
            'ZIP' => $address['zip'] ?? '',
        ];

        // Устанавливаем значения свойств через CODE.
        // CITY пишем даже пустой строкой — иначе останется DEFAULT_VALUE свойства.
        $allowEmptyCodes = ['CITY' => true];

        foreach ($propertyMapping as $code => $value) {
            if ($value === null) {
                continue;
            }
            if ($value === '' && !isset($allowEmptyCodes[$code])) {
                continue;
            }

            try {
                $propItem = $this->findOrCreateOrderPropertyItem($order, $propertyCollection, (string)$code);
                if ($propItem) {
                    $propItem->setValue($value);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
    
    private function findOrCreateOrderPropertyItem(Order $order, $propertyCollection, string $code)
    {
        if (method_exists($propertyCollection, 'getItemByOrderPropertyCode')) {
            $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
            if ($propItem) {
                return $propItem;
            }
        } else {
            $properties = $propertyCollection->getArray();
            if (isset($properties['properties'])) {
                foreach ($properties['properties'] as $property) {
                    if (isset($property['CODE']) && $property['CODE'] === $code) {
                        $propItem = $propertyCollection->getItemByOrderPropertyId($property['ID']);
                        if ($propItem) {
                            return $propItem;
                        }
                        break;
                    }
                }
            }
        }

        $personTypeId = (int)$order->getPersonTypeId();
        if ($personTypeId <= 0 || !class_exists('\Bitrix\Sale\Internals\OrderPropsTable')) {
            return null;
        }

        $filter = [
            '=PERSON_TYPE_ID' => $personTypeId,
            '=CODE' => $code,
        ];

        try {
            $propertyRow = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                'filter' => $filter + ['=ENTITY_REGISTRY_TYPE' => Registry::ENTITY_ORDER],
                'select' => ['ID', 'NAME', 'CODE', 'TYPE', 'MULTIPLE'],
                'limit' => 1,
            ])->fetch();
        } catch (\Exception $e) {
            $propertyRow = false;
        }

        if (!$propertyRow) {
            $propertyRow = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                'filter' => $filter,
                'select' => ['ID', 'NAME', 'CODE', 'TYPE', 'MULTIPLE'],
                'limit' => 1,
            ])->fetch();
        }

        if (!$propertyRow || !method_exists($propertyCollection, 'createItem')) {
            return null;
        }

        $existing = $propertyCollection->getItemByOrderPropertyId((int)$propertyRow['ID']);
        if ($existing) {
            return $existing;
        }

        return $propertyCollection->createItem($propertyRow);
    }

    /**
     * Получить значение свойства заказа по коду
     * 
     * @param Order $order Заказ
     * @param string $code Код свойства
     * @return string|null Значение свойства или null
     */
    private function getOrderPropertyValue($order, $code)
    {
        try {
            $propertyCollection = $order->getPropertyCollection();
            
            // Совместимость с Bitrix 18.5: метод getItemByOrderPropertyCode может отсутствовать
            if (method_exists($propertyCollection, 'getItemByOrderPropertyCode')) {
                // Новая версия - используем метод по коду
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $value = $propItem->getValue();
                    return !empty($value) ? $value : null;
                }
            } else {
                // Старая версия - ищем через массив свойств
                foreach ($propertyCollection as $property) {
                    $propertyCode = $property->getField('CODE');
                    if ($propertyCode === $code) {
                        $value = $property->getValue();
                        return !empty($value) ? $value : null;
                    }
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки
        }
        
        return null;
    }

    /**
     * Установить значение свойства заказа по коду (без сохранения заказа).
     */
    private function setOrderPropertyValue($order, $code, $value)
    {
        try {
            $propertyCollection = $order->getPropertyCollection();
            if (method_exists($propertyCollection, 'getItemByOrderPropertyCode')) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if ($propItem) {
                    $propItem->setValue($value);
                }
            } else {
                $properties = $propertyCollection->getArray();
                if (isset($properties['properties'])) {
                    foreach ($properties['properties'] as $property) {
                        if (isset($property['CODE']) && $property['CODE'] === $code) {
                            $propItem = $propertyCollection->getItemByOrderPropertyId($property['ID']);
                            if ($propItem) {
                                $propItem->setValue($value);
                            }
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // игнорируем
        }
    }

    /**
     * Возвращает CUSTOM_PRICE=Y и фиксирует цены из priceExpectations (items запроса).
     */
    private function cementBasketPrices(Basket $basket, array $expectedPrices): void
    {
        $basketPosition = 0;
        foreach ($basket as $basketItem) {
            $expected = $expectedPrices[$basketPosition] ?? null;
            $basketPosition++;

            $productId = (int)$basketItem->getProductId();
            if ($productId <= 0) {
                continue;
            }

            if ($expected === null || $this->getBasketPriceValidator()->hasBasketPriceConflict($basketItem, $expected)) {
                continue;
            }

            $regularPrice = (float)$expected['regular_price'];
            $finalPrice = (float)$expected['final_price'];
            $discountPrice = max(0.0, $regularPrice - $finalPrice);

            $basketItem->setField('BASE_PRICE', $regularPrice);
            $basketItem->setField('DISCOUNT_PRICE', $discountPrice);
            $basketItem->setField('PRICE', $finalPrice);
            $basketItem->setField('CUSTOM_PRICE', 'Y');
        }
    }

    private function getActualItemPrices($productId)
    {
        Loader::includeModule('catalog');

        $optimalPrice = \CCatalogProduct::GetOptimalPrice(
            (int)$productId,
            1,
            [],
            'N',
            [],
            Context::getCurrent()->getSite()
        );

        if ($optimalPrice && isset($optimalPrice['RESULT_PRICE'])) {
            return [
                'regular_price' => (float)$optimalPrice['RESULT_PRICE']['BASE_PRICE'],
                'final_price' => (float)$optimalPrice['RESULT_PRICE']['DISCOUNT_PRICE'],
            ];
        }

        return [
            'regular_price' => 0.0,
            'final_price' => 0.0,
        ];
    }

    private function isYandexCheckoutOrder($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return false;
        }

        $propertyRow = OrderPropsValueTable::getList([
            'filter' => [
                '=ORDER_ID' => $orderId,
                '=ENTITY_TYPE' => Registry::ENTITY_ORDER,
                '=PROPERTY.CODE' => ['YANDEX_ORDER_ID', 'EXTERNAL_ORDER_ID'],
            ],
            'select' => ['ORDER_ID'],
            'limit' => 1,
        ])->fetch();

        if ($propertyRow) {
            return true;
        }

        $order = Order::load($orderId);
        if (!$order) {
            return false;
        }

        $deliveryFields = $this->resolveCheckoutDeliveryFields();
        if ($deliveryFields !== null) {
            $configuredDeliveryId = (int)Option::get($this->moduleId, 'YANDEX_KIT_DELIVERY_ID', 0);
            $resolvedDeliveryId = (int)$deliveryFields['ID'];
            $matchedDeliveryIds = array_filter([$configuredDeliveryId, $resolvedDeliveryId]);

            foreach ($order->getShipmentCollection() as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }
                if (in_array((int)$shipment->getField('DELIVERY_ID'), $matchedDeliveryIds, true)) {
                    return true;
                }
            }
        }

        $paySystemId = (int) Option::get($this->moduleId, 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
        if ($paySystemId > 0) {
            foreach ($order->getPaymentCollection() as $payment) {
                if ((int)$payment->getField('PAY_SYSTEM_ID') === $paySystemId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Вернуть человекочитаемое название метода оплаты.
     * $onlinePaymentMethod — значение из body (card/sbp/split/split_sbp/null).
     * $paymentMethodFromUrl — значение query-параметра payment_method (online/on_delivery).
     * Возвращает пустую строку, если метод не удалось определить.
     */
    private function mapOnlinePaymentMethodToHuman($onlinePaymentMethod, $paymentMethodFromUrl): string
    {
        if ($paymentMethodFromUrl === 'on_delivery') {
            return 'Оплата при получении';
        }
        
        if ($paymentMethodFromUrl === 'online') {
            $map = [
                'card'      => 'Карта',
                'sbp'       => 'СБП',
                'split'     => 'Сплит',
                'split_sbp' => 'Сплит (СБП)',
            ];

            if (is_string($onlinePaymentMethod) && isset($map[$onlinePaymentMethod])) {
                return $map[$onlinePaymentMethod];
            }
        }

        return is_string($paymentMethodFromUrl) ? $paymentMethodFromUrl : '';
    }

    /**
     * Зарезервировать товары на складе
     */
    private function reserveProducts($orderId, $items, $warehouseId)
    {
        // Получаем заказ
        $order = Order::load($orderId);
        if (!$order) {
            return;
        }

        // Получаем корзину заказа
        $basket = $order->getBasket();
        if (!$basket) {
            return;
        }

        // Резервируем каждый товар
        foreach ($basket as $basketItem) {
            $productId = $basketItem->getProductId();
            $quantity = $basketItem->getQuantity();

            // Используем \CCatalogStoreBarCode для резервирования
            // Или просто помечаем в системе, что товары зарезервированы
            // В Bitrix это происходит автоматически при определенных статусах заказа
        }

        // Сохраняем информацию о складе в свойствах заказа
        $propertyCollection = $order->getPropertyCollection();
        $properties = $propertyCollection->getArray();

        foreach ($properties['properties'] as $property) {
            if ($property['CODE'] === 'STORE_ID' || strpos(strtolower($property['CODE']), 'warehouse') !== false) {
                $prop = $propertyCollection->getItemByOrderPropertyId($property['ID']);
                if ($prop) {
                    $prop->setValue($warehouseId);
                    break;
                }
            }
        }

        $this->saveYcpOrder($order);
    }

    /**
     * Сохранение YCP-заказа с отключением писем при SEND_ORDER_EMAILS=N.
     *
     * @param bool $suppressYcpSync не слать cancel/complete обратно в YCP при смене статуса
     */
    private function saveYcpOrder(Order $order, bool $suppressYcpSync = false)
    {
        if ($suppressYcpSync) {
            CheckoutHandlers::suppressYcpStatusSync(true);
        }

        CheckoutHandlers::applyMailGateBeforeSave($order);
        $result = $order->save();

        if ($suppressYcpSync) {
            CheckoutHandlers::suppressYcpStatusSync(false);
        }

        return $result;
    }

    /**
     * Ищет пользователя по данным покупателя
     * Поиск выполняется в следующем порядке:
     * 1. По email
     * 2. По телефону через UserPhoneAuthTable
     * 3. По PERSONAL_PHONE (резервный вариант)
     * 
     * @param array $customer Данные покупателя
     * @return int|false ID пользователя или false, если не найден
     */
    private function findUser($customer)
    {
        // Подключаем модуль main для работы с пользователями
        if (!Loader::includeModule('main')) {
            return false;
        }

        // 1. Ищем по email
        if (isset($customer['email']) && !empty($customer['email'])) {
            // Совместимость с Bitrix 18.5: параметры должны быть переменными, а не литералами
            $by = 'ID';
            $order = 'ASC';
            $arFilter = ['=EMAIL' => $customer['email']];
            $arSelect = ['FIELDS' => ['ID']];
            $res = \CUser::GetList($by, $order, $arFilter, $arSelect);

            if ($user = $res->Fetch()) {
                return intval($user['ID']);
            }
        }

        // 2. Ищем по телефону через UserPhoneAuthTable
        if (isset($customer['phone']) && !empty($customer['phone'])) {
            // Нормализуем телефон через Bitrix API
            $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($customer['phone']);
            
            if (!empty($phone)) {
                // Ищем через UserPhoneAuthTable
                $phoneAuthTable = \Bitrix\Main\UserPhoneAuthTable::getList([
                    'filter' => ['PHONE_NUMBER' => $phone],
                    'select' => ['USER_ID'],
                    'limit' => 1
                ]);
                
                if ($item = $phoneAuthTable->fetch()) {
                    return intval($item['USER_ID']);
                }
            }
        }

        // 3. Ищем по PERSONAL_PHONE (резервный вариант)
        if (isset($customer['phone']) && !empty($customer['phone'])) {
            // Нормализуем телефон через Bitrix API
            $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($customer['phone']);
            
            if (!empty($phone)) {
                // Ищем по нормализованному телефону
                // Совместимость с Bitrix 18.5: параметры должны быть переменными, а не литералами
                $by = 'ID';
                $order = 'ASC';
                $arFilter = ['PERSONAL_PHONE' => $phone];
                $arSelect = ['FIELDS' => ['ID']];
                $res = \CUser::GetList($by, $order, $arFilter, $arSelect);

                if ($user = $res->Fetch()) {
                    return intval($user['ID']);
                }
                
                // Также пробуем найти без + в начале (на случай, если в БД хранится без +)
                $phoneWithoutPlus = ltrim($phone, '+');
                if ($phoneWithoutPlus !== $phone) {
                    // Совместимость с Bitrix 18.5: параметры должны быть переменными, а не литералами
                    $by = 'ID';
                    $order = 'ASC';
                    $arFilter = ['PERSONAL_PHONE' => $phoneWithoutPlus];
                    $arSelect = ['FIELDS' => ['ID']];
                    $res = \CUser::GetList($by, $order, $arFilter, $arSelect);

                    if ($user = $res->Fetch()) {
                        return intval($user['ID']);
                    }
                }
            }
        }

        return false;
    }


    /**
     * Получает тип плательщика для сайта из контекста
     * 
     * @param string $siteId ID сайта
     * @return int|null ID типа плательщика или null, если не найден
     */
    private function getPersonTypeIdBySite($siteId)
    {
        if (empty($siteId)) {
            return null;
        }

        if (!Loader::includeModule('sale')) {
            return null;
        }

        // Получаем первый активный тип плательщика для сайта, отсортированный по SORT
        $personType = \Bitrix\Sale\PersonType::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
                '=PERSON_TYPE_SITE.SITE_ID' => $siteId,
                '=ENTITY_REGISTRY_TYPE' => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
            ],
            'order' => [
                'SORT' => 'ASC',
                'ID' => 'ASC'
            ],
            'limit' => 1,
            'select' => ['ID']
        ])->fetch();

        return $personType ? intval($personType['ID']) : null;
    }

    /**
     * Создает нового пользователя в Битрикс
     * 
     * @param array $customer Данные покупателя
     * @param string $externalOrderId Внешний ID заказа
     * @return array Массив с результатом: ['success' => bool, 'userId' => int|null, 'error' => string|null]
     */
    private function createUser($customer, $externalOrderId)
    {
        global $USER;
        
        // Подключаем модуль main для работы с пользователями
        if (!Loader::includeModule('main')) {
            return ['success' => false, 'userId' => null, 'error' => 'Main module not available'];
        }

        $cUser = new \CUser();
        
        // Генерируем уникальный логин на основе внешнего ID заказа
        $login = $this->generateUserLogin($externalOrderId);

        // Email на случай, если покупатель его не передал (Битрикс валидирует формат)
        $fallbackEmail = $login . '@' . Option::get('main', 'server_name', 'localhost');

        // Генерируем случайный пароль
        $password = $this->generatePassword();
        
        // Извлекаем имя и фамилию из полного имени
        $fullName = isset($customer['full_name']) ? $customer['full_name'] : '';
        $nameParts = explode(' ', $fullName, 2);
        $firstName = isset($nameParts[0]) ? $nameParts[0] : 'Покупатель';
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        
        // Подготавливаем данные для создания пользователя
        $arFields = [
            'LOGIN' => $login,
            'EMAIL' => isset($customer['email']) && !empty($customer['email']) ? $customer['email'] : $fallbackEmail,
            'NAME' => $firstName,
            'LAST_NAME' => $lastName,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'ACTIVE' => 'Y',
            'PERSONAL_PHONE' => isset($customer['phone']) && !empty($customer['phone']) ? \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($customer['phone']) : '',
            'PHONE_NUMBER' => isset($customer['phone']) && !empty($customer['phone']) ? \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($customer['phone']) : '',
            'XML_ID' => 'yastore_' . $externalOrderId, // Связываем с внешним заказом
        ];

        // Создаем пользователя
        $userId = $cUser->Add($arFields);
        
        if (!$userId) {
            // Получаем текст ошибки
            $error = $cUser->LAST_ERROR;
            return ['success' => false, 'userId' => null, 'error' => $error ?: 'Failed to create user'];
        }

        return ['success' => true, 'userId' => intval($userId), 'error' => null];
    }

    /**
     * Генерирует уникальный логин длиной 22 символа
     *
     * Полный внешний ID заказа сохраняется в XML_ID пользователя,
     * поэтому в логине достаточно короткого хеша от него.
     *
     * @param string $externalOrderId Внешний ID заказа
     * @return string Сгенерированный логин
     */
    private function generateUserLogin($externalOrderId)
    {
        // ya_ (3) + 12 hex (12) + _ (1) + 6 символов base36 от времени = 22 символа
        $hash = substr(md5((string)$externalOrderId), 0, 12);

        // 3 попытки с временной меткой, дальше - случайный суффикс
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $login = 'ya_' . $hash . '_' . base_convert((string)(time() + $attempt), 10, 36);

            if (!$this->isLoginTaken($login)) {
                return $login;
            }
        }

        return 'ya_' . $hash . '_' . bin2hex(random_bytes(3));
    }

    /**
     * Проверяет, занят ли логин
     *
     * @param string $login Проверяемый логин
     * @return bool
     */
    private function isLoginTaken($login)
    {
        // Совместимость с Bitrix 18.5: параметры должны быть переменными, а не литералами
        $by = 'ID';
        $order = 'ASC';
        $arFilter = ['=LOGIN' => $login];
        $arSelect = ['FIELDS' => ['ID']];
        $res = \CUser::GetList($by, $order, $arFilter, $arSelect);

        return (bool)$res->Fetch();
    }

    /**
     * Генерирует случайный пароль
     *
     * @param int $length Длина пароля
     * @return string Сгенерированный пароль
     */
    private function generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $charsLength = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength - 1)];
        }
        
        return $password;
    }

    /**
     * Создает или обновляет профиль покупателя
     * 
     * @param Order $order Заказ
     * @param array $customer Данные покупателя
     * @param array $delivery Данные доставки
     * @return void
     */
    private function saveBuyerProfile($order, $customer, $delivery)
    {
        try {
            if (!Loader::includeModule('sale')) {
                // error_log('[saveBuyerProfile] Module sale not loaded');
                return;
            }

            $userId = $order->getUserId();
            $personTypeId = $order->getPersonTypeId();

            // error_log('[saveBuyerProfile] Start: userId=' . $userId . ', personTypeId=' . $personTypeId);

            if (!$userId || !$personTypeId) {
                // error_log('[saveBuyerProfile] Missing userId or personTypeId');
                return;
            }

            // Ищем существующий профиль покупателя
            $existingProfileId = $this->findBuyerProfile($userId, $personTypeId);
            // error_log('[saveBuyerProfile] Existing profile ID: ' . $existingProfileId);
            
            // Формируем имя профиля
            $profileName = $this->generateProfileName($customer, $delivery);
            // error_log('[saveBuyerProfile] Profile name: ' . $profileName);
            
            // Получаем свойства заказа в формате [PROPERTY_ID => VALUE]
            $orderProps = $this->getOrderPropertiesForProfile($order, $personTypeId);
            // error_log('[saveBuyerProfile] Order properties count: ' . count($orderProps));
            // error_log('[saveBuyerProfile] Order properties: ' . json_encode($orderProps, JSON_UNESCAPED_UNICODE));
            
            if (empty($orderProps)) {
                // error_log('[saveBuyerProfile] No properties to save (empty orderProps)');
                return; // Нет свойств для сохранения
            }

            // Сохраняем профиль
            $errors = [];
            $profileId = \CSaleOrderUserProps::DoSaveUserProfile(
                $userId,
                $existingProfileId,
                $profileName,
                $personTypeId,
                $orderProps,
                $errors
            );

            // error_log('[saveBuyerProfile] DoSaveUserProfile result: ' . ($profileId !== false ? $profileId : 'false'));

            if ($profileId === false && !empty($errors)) {
                // Логируем ошибки, но не прерываем выполнение
                // error_log('[saveBuyerProfile] Failed to save buyer profile: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
            } elseif ($profileId !== false) {
                // error_log('[saveBuyerProfile] Profile saved successfully with ID: ' . $profileId);
            }

        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            // error_log('[saveBuyerProfile] Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Ищет существующий профиль покупателя
     * 
     * @param int $userId ID пользователя
     * @param int $personTypeId ID типа плательщика
     * @return int ID профиля или 0, если не найден
     */
    private function findBuyerProfile($userId, $personTypeId)
    {
        try {
            if (!Loader::includeModule('sale')) {
                return 0;
            }

            // Ищем профиль через API Битрикс
            $profile = \CSaleOrderUserProps::GetList(
                ['DATE_UPDATE' => 'DESC'],
                [
                    'USER_ID' => $userId,
                    'PERSON_TYPE_ID' => $personTypeId
                ],
                false,
                false,
                ['ID']
            )->Fetch();

            return $profile ? (int)$profile['ID'] : 0;

        } catch (\Exception $e) {
            // error_log('Error finding buyer profile: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Генерирует имя профиля на основе данных покупателя и доставки
     * 
     * @param array $customer Данные покупателя
     * @param array $delivery Данные доставки
     * @return string Имя профиля
     */
    private function generateProfileName($customer, $delivery)
    {
        $nameParts = [];
        
        // Добавляем имя покупателя
        if (!empty($customer['full_name'])) {
            $nameParts[] = $customer['full_name'];
        }
        
        // Добавляем адрес или ПВЗ
        $addressRaw = $delivery['address'] ?? null;
        $address = is_array($addressRaw) ? $addressRaw : [];
        if (is_string($addressRaw) && trim($addressRaw) !== '') {
            $nameParts[] = trim($addressRaw);
        } elseif (!empty($address['address'])) {
            $nameParts[] = $address['address'];
        } elseif (!empty($address['pickup_point_id'])) {
            $nameParts[] = 'ПВЗ ' . $address['pickup_point_id'];
        }
        
        // Если ничего не найдено, используем дату
        if (empty($nameParts)) {
            $nameParts[] = date('d.m.Y H:i');
        }
        
        return implode(', ', $nameParts);
    }

    /**
     * Получает свойства заказа в формате для сохранения профиля [PROPERTY_ID => VALUE]
     * 
     * @param Order $order Заказ
     * @param int $personTypeId ID типа плательщика
     * @return array Массив [PROPERTY_ID => VALUE]
     */
    private function getOrderPropertiesForProfile($order, $personTypeId)
    {
        $result = [];
        
        try {
            $propertyCollection = $order->getPropertyCollection();
            $totalProperties = 0;
            $propertiesWithValues = 0;
            $propertiesWithUserProps = 0;
            
            // Получаем все свойства заказа
            foreach ($propertyCollection as $property) {
                $totalProperties++;
                $propertyId = $property->getPropertyId();
                $value = $property->getValue();
                $propertyCode = $property->getField('CODE');
                
                // Пропускаем пустые значения
                if (empty($value) && $value !== '0') {
                    continue;
                }
                
                $propertiesWithValues++;
                
                // Проверяем, что свойство должно сохраняться в профиле (USER_PROPS = Y)
                $propertyData = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                    'filter' => [
                        'ID' => $propertyId,
                        'PERSON_TYPE_ID' => $personTypeId,
                        'ACTIVE' => 'Y',
                        'USER_PROPS' => 'Y'
                    ],
                    'select' => ['ID', 'CODE', 'NAME', 'USER_PROPS'],
                    'limit' => 1
                ])->fetch();
                
                if ($propertyData) {
                    $propertiesWithUserProps++;
                    $result[$propertyId] = $value;
                    // error_log("[getOrderPropertiesForProfile] Property added: ID={$propertyId}, CODE={$propertyCode}, VALUE=" . (is_array($value) ? json_encode($value) : $value));
                } else {
                    // Проверяем, почему свойство не подходит
                    $checkProperty = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                        'filter' => [
                            'ID' => $propertyId,
                            'PERSON_TYPE_ID' => $personTypeId
                        ],
                        'select' => ['ID', 'CODE', 'NAME', 'ACTIVE', 'USER_PROPS'],
                        'limit' => 1
                    ])->fetch();
                    
                    if ($checkProperty) {
                        // error_log("[getOrderPropertiesForProfile] Property skipped: ID={$propertyId}, CODE={$propertyCode}, ACTIVE={$checkProperty['ACTIVE']}, USER_PROPS={$checkProperty['USER_PROPS']}");
                    } else {
                        // error_log("[getOrderPropertiesForProfile] Property not found: ID={$propertyId}, CODE={$propertyCode}");
                    }
                }
            }
            
            // error_log("[getOrderPropertiesForProfile] Summary: total={$totalProperties}, with_values={$propertiesWithValues}, with_user_props={$propertiesWithUserProps}, result_count=" . count($result));
            
        } catch (\Exception $e) {
            // error_log('[getOrderPropertiesForProfile] Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
        
        return $result;
    }

    /**
     * Преобразует end_date и end_time в человекочитаемый вид с учетом временной зоны.
     * Если time_zone указана, применяет смещение от UTC.
     *
     * @param string|null $endDate Дата в формате YYYY-MM-DD
     * @param string|null $endTime Время в формате HH:MM:SS
     * @param int|null $timeZone Смещение от UTC (например, 3 = UTC+3)
     * @return string Отформатированная дата, например "16 апреля 14:00:00"
     */
    private function formatDeliveryDateFromEndDateTime($endDate, $endTime, $timeZone = null)
    {
        if (empty($endDate)) {
            return '';
        }

        try {
            // Создаем DateTime из даты и времени
            $dateTimeString = (string)$endDate;
            if (!empty($endTime)) {
                $dateTimeString .= ' ' . (string)$endTime;
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString);
            } else {
                $dateTime = \DateTime::createFromFormat('Y-m-d', $dateTimeString);
            }

            if (!$dateTime) {
                return '';
            }

            // Применяем смещение временной зоны если указана
            if ($timeZone !== null) {
                $timeZone = (int)$timeZone;
                // Получаем текущий UTC offset сайта в часах
                $siteTimezone = new \DateTimeZone(date_default_timezone_get());
                $siteOffset = $siteTimezone->getOffset($dateTime) / 3600; // конвертируем в часы

                // Вычисляем разницу между переданной зоной и зоной сайта
                $hoursDiff = $timeZone - $siteOffset;

                // Применяем смещение
                if ($hoursDiff != 0) {
                    $dateTime->modify(($hoursDiff > 0 ? '+' : '') . $hoursDiff . ' hours');
                }
            }

            $months = [
                1 => 'января',
                2 => 'февраля',
                3 => 'марта',
                4 => 'апреля',
                5 => 'мая',
                6 => 'июня',
                7 => 'июля',
                8 => 'августа',
                9 => 'сентября',
                10 => 'октября',
                11 => 'ноября',
                12 => 'декабря',
            ];

            $day = (int)$dateTime->format('j');
            $month = $months[(int)$dateTime->format('n')] ?? '';
            $time = $dateTime->format('H:i:s');

            $formatted = trim($day . ' ' . $month . ' ' . $time);
            return !empty($formatted) ? $formatted : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
