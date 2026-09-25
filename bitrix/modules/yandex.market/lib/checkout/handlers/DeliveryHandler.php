<?php

namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Order;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Yandex\Market\Checkout\ApiResponse;
use Yandex\Market\Checkout\Handlers as CheckoutHandlers;
use Yandex\Market\Checkout\DeliveryStatus;

/**
 * Приём данных о доставке от Яндекса с поддержкой 2 действий:
 * - status: обновление статуса доставки
 * - tracking: обновление данных для отслеживания
 *
 * Заказ определяется по query-параметру orderId (XML_ID).
 */
class DeliveryHandler extends BaseHandler
{
    public static function getSupportedActions(): array
    {
        return ['status', 'tracking'];
    }

    /**
     * @param $orderId
     * @return ApiResponse
     */
    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        $action = $this->request->get('action');

        switch ($action) {
            case 'status':
                return $this->handleDeliveryStatus($orderId);

            case 'tracking':
                return $this->handleDeliveryTracking($orderId);

            default:
                return $this->error('Invalid action: expected "status" or "tracking"', 400, self::ERROR_INVALID_INPUT);
        }
    }

    private function handleDeliveryStatus($orderId = null)
    {
        try {
            if (!Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            if (empty($orderId)) {
                return $this->error('Order ID is required', 400, self::ERROR_INVALID_INPUT);
            }

            $input = file_get_contents('php://input');
            try {
                $requestData = Json::decode($input);
            } catch (\Exception $e) {
                return $this->error('Invalid JSON body', 400, self::ERROR_INVALID_INPUT);
            }

            if (!is_array($requestData)) {
                return $this->error('Invalid request format', 400, self::ERROR_INVALID_INPUT);
            }

            $status = $requestData['status'] ?? null;

            if (empty($status)) {
                return $this->error('Field "status" is required', 400, self::ERROR_INVALID_INPUT);
            }

            if (!DeliveryStatus::isValid($status)) {
                return $this->error(
                    'Invalid "status": expected one of ' . implode(', ', DeliveryStatus::all()),
                    400,
                    self::ERROR_INVALID_INPUT
                );
            }

            $orderData = Order::getList([
                'filter' => ['XML_ID' => $orderId],
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();

            if (!$orderData || !$this->isYandexCheckoutOrder((int)$orderData['ID'])) {
                return $this->error('Order not found', 404, self::ERROR_NOT_FOUND);
            }

            $order = Order::load($orderData['ID']);
            if (!$order) {
                return $this->error('Failed to load order', 500);
            }

            $this->setOrderPropertyValue($order, 'DELIVERY_STATUS', (string)DeliveryStatus::toOrderStatus($status));
            $result = $this->saveYcpOrder($order);

            if (!$result->isSuccess()) {
                return $this->error(
                    'Failed to save delivery status: ' . implode(', ', $result->getErrorMessages()),
                    500
                );
            }

            // new \stdClass(), чтобы Json::encode выдал {}, а не []
            return $this->response(new \stdClass(), 200);
        } catch (\Exception $e) {
            return $this->error('Failed to update delivery status: ' . $e->getMessage(), 500);
        }
    }

    private function handleDeliveryTracking($orderId = null)
    {
        try {
            if (!Loader::includeModule('sale')) {
                return $this->error('Required modules not available', 500);
            }

            if (empty($orderId)) {
                return $this->error('Order ID is required', 400, self::ERROR_INVALID_INPUT);
            }

            $input = file_get_contents('php://input');
            try {
                $requestData = Json::decode($input);
            } catch (\Exception $e) {
                return $this->error('Invalid JSON body', 400, self::ERROR_INVALID_INPUT);
            }

            if (!is_array($requestData)) {
                return $this->error('Invalid request format', 400, self::ERROR_INVALID_INPUT);
            }

            $trackingUrl = $requestData['tracking_url'] ?? null;
            $barcodeUrl = $requestData['barcode_url'] ?? null;

            if (empty($trackingUrl) && empty($barcodeUrl)) {
                return $this->error('At least one of "tracking_url" or "barcode_url" is required', 400, self::ERROR_INVALID_INPUT);
            }

            if (!empty($trackingUrl) && !$this->isHttpUrl($trackingUrl)) {
                return $this->error('Invalid "tracking_url": only http/https links are allowed', 400, self::ERROR_INVALID_INPUT);
            }

            if (!empty($barcodeUrl) && !$this->isHttpUrl($barcodeUrl)) {
                return $this->error('Invalid "barcode_url": only http/https links are allowed', 400, self::ERROR_INVALID_INPUT);
            }

            $orderData = Order::getList([
                'filter' => ['XML_ID' => $orderId],
                'select' => ['ID'],
                'limit' => 1,
            ])->fetch();

            if (!$orderData || !$this->isYandexCheckoutOrder((int)$orderData['ID'])) {
                return $this->error('Order not found', 404, self::ERROR_NOT_FOUND);
            }

            $order = Order::load($orderData['ID']);
            if (!$order) {
                return $this->error('Failed to load order', 500);
            }

            if (!empty($trackingUrl)) {
                $this->setOrderPropertyValue($order, 'DELIVERY_TRACKING_URL', (string)$trackingUrl);
            }
            if (!empty($barcodeUrl)) {
                $this->setOrderPropertyValue($order, 'DELIVERY_BARCODE_URL', (string)$barcodeUrl);
            }

            $result = $this->saveYcpOrder($order);

            if (!$result->isSuccess()) {
                return $this->error(
                    'Failed to save tracking data: ' . implode(', ', $result->getErrorMessages()),
                    500
                );
            }

            // new \stdClass(), чтобы Json::encode выдал {}, а не []
            return $this->response(new \stdClass(), 200);
        } catch (\Exception $e) {
            return $this->error('Failed to update delivery tracking: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Проверяет, что значение — абсолютная http/https-ссылка.
     *
     * Значение попадает в свойство заказа и затем рендерится ссылкой в админке
     * и в личном кабинете покупателя, поэтому схему URI нужно ограничить
     * (иначе принимается javascript:-payload).
     *
     * @param mixed $value
     * @return bool
     */
    private function isHttpUrl($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $scheme = parse_url(trim($value), PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
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
                return;
            }

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
        } catch (\Exception $e) {
            // Игнорируем ошибки для несуществующих свойств
        }
    }

    /**
     * Проверяет, что заказ относится к Yandex Checkout (по свойствам или служебным delivery/pay-системам).
     */
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

        $deliveryId = (int)Option::get($this->moduleId, 'YANDEX_KIT_DELIVERY_ID', 0);
        if ($deliveryId > 0) {
            foreach ($order->getShipmentCollection() as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }
                if ((int)$shipment->getField('DELIVERY_ID') === $deliveryId) {
                    return true;
                }
            }
        }

        $paySystemId = (int)Option::get($this->moduleId, 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
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
     * Сохранение YCP-заказа с отключением писем при SEND_ORDER_EMAILS=N.
     */
    private function saveYcpOrder(Order $order)
    {
        CheckoutHandlers::applyMailGateBeforeSave($order);

        return $order->save();
    }
}
