<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Sale\Notify;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Shipment;
use Yandex\Market\Checkout\Services\PromocodeCalculator;

require_once __DIR__ . '/ButtonBootstrapClient.php';


class Handlers
{
    static $MODULE_ID = "yandex.market";

    /** @var bool Отключение Notify включено этим обработчиком в текущем save(). */
    private static $mailGateActive = false;

    /**
     * События Sale, перед которыми нужно отключить Notify для YCP-заказов.
     *
     * @return string[]
     */
    public static function getMailGateEventNames(): array
    {
        return [
            'OnSaleOrderBeforeSaved',
            'OnSaleOrderSaved',
            'OnSaleOrderCanceled',
            'OnSaleOrderPaid',
            'OnSaleStatusOrderChange',
            'OnShipmentAllowDelivery',
            'OnSaleOrderPaidSendMail',
            'OnSaleOrderStatusChangeSendEmail',
            'OnSaleOrderCancelSendEmail',
            'OnSaleShipmentStatusChangeSendEmail',
            'onSaleOrderStatusAllowPaySendEmail',
        ];
    }

    /**
     * Ранний обработчик (sort=1) на событиях Sale.
     * Отключает письма модуля Sale только для заказов YCP.
     */
    public static function onSaleOrderSavedMailGate($event)
    {
        if (!Loader::includeModule('sale')) {
            return;
        }

        $order = self::getOrderFromNotifyEvent($event);
        if (!$order instanceof Order) {
            return;
        }

        self::applyMailGate($order);
    }

    /**
     * Явный вызов перед Order::save() в YCP API (дублирует event-handlers).
     */
    public static function applyMailGateBeforeSave(Order $order): void
    {
        if (!Loader::includeModule('sale')) {
            return;
        }

        self::applyMailGate($order);
    }

    /**
     * @deprecated Снятие устаревшей регистрации OnSaleOrderSaved:999.
     * @noinspection PhpUnused
     */
    public static function onSaleOrderSavedMailGateReset($event)
    {
    }

    /** @var bool */
    private static $shutdownRegistered = false;

    /** @var bool Не слать cancel/complete в YCP при смене статуса из checkout API */
    private static $ycpApiStatusSyncSuppressed = false;

    /**
     * Подавляет обратную синхронизацию статуса в YCP (cancel/complete) на время save().
     * Вызывать перед Order::save() в OrdersHandler при action=cancel|delivered.
     */
    public static function suppressYcpStatusSync(bool $suppress = true): void
    {
        self::$ycpApiStatusSyncSuppressed = $suppress;
    }

    private static function applyMailGate(Order $order): void
    {
        if (!self::isYandexKitOrder($order)) {
            return;
        }

        if (Option::get(self::$MODULE_ID, 'SEND_ORDER_EMAILS', 'N') === 'Y') {
            return;
        }

        Notify::setNotifyDisable(true);
        self::$mailGateActive = true;

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'resetMailGateOnShutdown']);
        }
    }

    /**
     * Сброс Notify в конце запроса.
     * OnSaleOrderSaved:999 нельзя — Bitrix вызывает его до callDelayedEvents().
     */
    public static function resetMailGateOnShutdown(): void
    {
        if (!self::$mailGateActive) {
            return;
        }

        Notify::setNotifyDisable(false);
        self::$mailGateActive = false;
        self::$shutdownRegistered = false;
    }

    private static function getOrderFromNotifyEvent($event): ?Order
    {
        $entity = null;
        if ($event instanceof Event) {
            $entity = $event->getParameter('ENTITY');
        } elseif (is_array($event) && isset($event['ENTITY'])) {
            $entity = $event['ENTITY'];
        }

        if ($entity instanceof Order) {
            return $entity;
        }
        if ($entity instanceof Shipment) {
            $order = $entity->getParentOrder();

            return $order instanceof Order ? $order : null;
        }
        if ($entity instanceof Payment) {
            try {
                $order = $entity->getOrder();
            } catch (\Throwable $e) {
                return null;
            }

            return $order instanceof Order ? $order : null;
        }

        return null;
    }

    private static function isYandexKitOrder(Order $order): bool
    {
        $deliveryId = (int) Option::get(self::$MODULE_ID, 'YANDEX_KIT_DELIVERY_ID', 0);
        if ($deliveryId > 0) {
            foreach ($order->getShipmentCollection() as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }
                if ((int) $shipment->getField('DELIVERY_ID') === $deliveryId) {
                    return true;
                }
            }
        }

        $paySystemId = (int) Option::get(self::$MODULE_ID, 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
        if ($paySystemId > 0) {
            foreach ($order->getPaymentCollection() as $payment) {
                if ((int) $payment->getPaymentSystemId() === $paySystemId) {
                    return true;
                }
            }
        }

        if (self::getOrderPropertyStringByCode($order, 'YANDEX_ORDER_ID') !== null) {
            return true;
        }

        if (self::getOrderPropertyStringByCode($order, 'EXTERNAL_ORDER_ID') !== null) {
            return true;
        }

        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }
            $deliveryName = (string) $shipment->getField('DELIVERY_NAME');
            if ($deliveryName === 'YCP' || stripos($deliveryName, 'YCP') !== false) {
                return true;
            }
        }

        $yastoreUserId = (int) Option::get(self::$MODULE_ID, 'YASTORE_USER_ID', 0);
        if ($yastoreUserId > 0 && (int) $order->getUserId() === $yastoreUserId) {
            return true;
        }

        return false;
    }

    /**
     * YCP-заказ для синхронизации статуса (cancel/complete).
     */
    private static function isYandexKitOrderForStatusSync(Order $order): bool
    {
        $deliveryId = (int) Option::get(self::$MODULE_ID, 'YANDEX_KIT_DELIVERY_ID', 0);
        if ($deliveryId > 0) {
            foreach ($order->getShipmentCollection() as $shipment) {
                if ($shipment->isSystem()) {
                    continue;
                }
                if ((int) $shipment->getField('DELIVERY_ID') === $deliveryId) {
                    return true;
                }
            }
        }

        $paySystemId = (int) Option::get(self::$MODULE_ID, 'YANDEX_KIT_PAY_SYSTEM_ID', 0);
        if ($paySystemId > 0) {
            foreach ($order->getPaymentCollection() as $payment) {
                if ((int) $payment->getPaymentSystemId() === $paySystemId) {
                    return true;
                }
            }
        }

        if (self::getOrderPropertyStringByCode($order, 'YANDEX_ORDER_ID') !== null) {
            return true;
        }

        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }
            $deliveryName = (string) $shipment->getField('DELIVERY_NAME');
            if ($deliveryName === 'YCP' || stripos($deliveryName, 'YCP') !== false) {
                return true;
            }
        }

        $yastoreUserId = (int) Option::get(self::$MODULE_ID, 'YASTORE_USER_ID', 0);
        if ($yastoreUserId > 0 && (int) $order->getUserId() === $yastoreUserId) {
            return true;
        }

        return false;
    }

    private static function getOrderPropertyStringByCode(Order $order, string $code): ?string
    {
        try {
            $propertyCollection = $order->getPropertyCollection();
            if (!method_exists($propertyCollection, 'getItemByOrderPropertyCode')) {
                return null;
            }
            $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
            if (!$propItem) {
                return null;
            }
            $value = $propItem->getValue();
            if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                return null;
            }

            return is_array($value) ? (string) reset($value) : (string) $value;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Подключение JS/CSS кнопки «Купить в 1 клик» на странице корзины.
     *
     * Промо (только корзина, enableBasketButton): server-side __buttonBootstrap + CHECKOUT_PROMO_SCRIPT_URL,
     * YAKIT_BUTTON_CSS (кнопка + .yastore-checkout-promo), mount в #yastore-checkout-promo (см. ButtonBootstrapClient и script.js).
     * Нужны YA_PAY_MERCHANT_ID (вкладка «Бейджи и виджеты Я.Пэй»), деплой UMD на S3, whitelist merchant в Pay Plus.
     */
    public static function appendYandexCheckoutJs()
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $requestPage = $request->getRequestedPage();

        $basketPath = trim((string) Option::get(self::$MODULE_ID, 'YAKIT_BASKET_PAGE_PATH', '/personal/cart/'));
        if ($basketPath === '') {
            $basketPath = '/personal/cart/';
        }
        $basketPathNorm = trim(trim($basketPath), '/');
        $showButton = Option::get(self::$MODULE_ID, 'SHOW_BUTTON', 'N');
        // Временно отключено: функционал кнопки в карточке товара.
        // $showProductButton = Option::get(self::$MODULE_ID, 'SHOW_PRODUCT_BUTTON', 'N');
        $showProductButton = 'N';

        $onBasketPage = ($basketPathNorm !== '' && (strpos($requestPage, $basketPathNorm) !== false));
        $enableBasketButton = ($showButton === 'Y' && $onBasketPage);
        $enableProductButton = ($showProductButton === 'Y');

        if (!$enableBasketButton && !$enableProductButton) {
            return;
        }

        $buttonAnchor = Option::get(self::$MODULE_ID, 'BUTTON_ANCHOR', '.basket-checkout-section-inner');
        $hideOriginalBasketButton = Option::get(self::$MODULE_ID, 'YAKIT_HIDE_ORIGINAL_BASKET_BUTTON', 'N');
        $originalBasketButtonSelector = (string) Option::get(self::$MODULE_ID, 'YAKIT_ORIGINAL_BASKET_BUTTON_SELECTOR', '.basket-btn-checkout');
        $buttonInsertAfter = Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_INSERT_AFTER', '');
        // Опции checkout-button-sdk
        $buttonTheme = (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_THEME', 'gradient');
        $buttonColor = trim((string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_COLOR', ''));
        // Тема "custom" только в админке = свой цвет: SDK темы 'custom' не знает, поэтому шлём
        // базовую тему + customColor, чтобы сдк не кидал ворнинги (SDK применяет customColor поверх темы).
        if ($buttonTheme === 'custom') {
            $buttonTheme = 'gradient';
        } else {
            $buttonColor = '';
        }
        $buttonWidth = (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_WIDTH', 'max');
        $buttonHeight = (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_HEIGHT', '44');
        $buttonRadius = (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_RADIUS', '12');
        $buttonCaption = (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_CAPTION', 'checkout');
        $productButtonAnchor = (string) Option::get(self::$MODULE_ID, 'PRODUCT_BUTTON_ANCHOR', '');
        $productButtonInsertAfter = (string) Option::get(self::$MODULE_ID, 'PRODUCT_BUTTON_INSERT_AFTER', '');
        $productButtonCss = trim((string) Option::get(self::$MODULE_ID, 'PRODUCT_BUTTON_CSS', ''));
        if ($productButtonCss === '') {
            $productButtonCss = self::getDefaultProductButtonCss();
        }
        $productButtonText = (string) Option::get(self::$MODULE_ID, 'PRODUCT_BUTTON_TEXT', 'Быстрое оформление');
        $productIdSelector = (string) Option::get(self::$MODULE_ID, 'PRODUCT_ID_SELECTOR', '[data-product-id], [name=PRODUCT_ID], [name=PRODUCT_ID_INPUT]');

        // Промо тянет сам SDK в браузере, на мультивалютных промо не показываем
        // без merchantId SDK за промо не ходит вовсе.
        $merchantIdJs = '';
        if ($enableBasketButton && self::isRubBasketSite()) {
            $merchantIdJs = self::escapeJsString(trim((string) Option::get(self::$MODULE_ID, 'YA_PAY_MERCHANT_ID', '')));
        }

        $totalAmountJs = '';
        if ($enableBasketButton && $merchantIdJs !== '') {
            $totalAmount = ButtonBootstrapClient::basketTotalAmount();
            if ($totalAmount !== null) {
                $totalAmountJs = self::escapeJsString($totalAmount);
            }
        }
        // CSS-класс(ы) контейнера кнопки чтобы можно указать кнопочную ячейку темы 
        $wrapClassJs = self::escapeJsString(str_replace(['<', '>'], '', (string) Option::get(self::$MODULE_ID, 'YAKIT_BUTTON_WRAP_CLASS', '')));

        Asset::getInstance()->addString(
            "<script>\n" .
            "var YAKIT_BASKET_BUTTON_ENABLED = '" . ($enableBasketButton ? 'Y' : 'N') . "';\n" .
            "var BUTTON_ANCHOR = '" . self::escapeJsString($buttonAnchor) . "';\n" .
            "var YAKIT_HIDE_ORIGINAL_BASKET_BUTTON = '" . ($hideOriginalBasketButton === 'Y' ? 'Y' : 'N') . "';\n" .
            "var YAKIT_ORIGINAL_BASKET_BUTTON_SELECTOR = '" . self::escapeJsString($originalBasketButtonSelector) . "';\n" .
            "var YAKIT_BUTTON_INSERT_AFTER = '" . self::escapeJsString($buttonInsertAfter) . "';\n" .
            "var YAKIT_BUTTON_THEME = '" . self::escapeJsString($buttonTheme) . "';\n" .
            "var YAKIT_BUTTON_COLOR = '" . self::escapeJsString($buttonColor) . "';\n" .
            "var YAKIT_BUTTON_WIDTH = '" . self::escapeJsString($buttonWidth) . "';\n" .
            "var YAKIT_BUTTON_HEIGHT = '" . self::escapeJsString($buttonHeight) . "';\n" .
            "var YAKIT_BUTTON_RADIUS = '" . self::escapeJsString($buttonRadius) . "';\n" .
            "var YAKIT_BUTTON_CAPTION = '" . self::escapeJsString($buttonCaption) . "';\n" .
            "var YAKIT_BUTTON_WRAP_CLASS = '" . $wrapClassJs . "';\n" .
            "var YAKIT_SDK_URL = '" . self::escapeJsString(self::getSdkBundleUrl()) . "';\n" .
            "var YAKIT_MERCHANT_ID = '" . $merchantIdJs . "';\n" .
            "var YAKIT_TOTAL_AMOUNT = '" . $totalAmountJs . "';\n" .
            "var YAKIT_PRODUCT_BUTTON_ENABLED = '" . ($enableProductButton ? 'Y' : 'N') . "';\n" .
            "var YAKIT_PRODUCT_BUTTON_ANCHOR = '" . self::escapeJsString($productButtonAnchor) . "';\n" .
            "var YAKIT_PRODUCT_BUTTON_INSERT_AFTER = '" . self::escapeJsString($productButtonInsertAfter) . "';\n" .
            "var YAKIT_PRODUCT_BUTTON_CSS = '" . self::escapeJsString(str_replace('</style>', '', $productButtonCss)) . "';\n" .
            "var YAKIT_PRODUCT_BUTTON_TEXT = '" . self::escapeJsString($productButtonText) . "';\n" .
            "var YAKIT_PRODUCT_ID_SELECTOR = '" . self::escapeJsString($productIdSelector) . "';\n" .
            "</script>"
        );
        Asset::getInstance()->addJs('/bitrix/js/yastore.checkout/script.js');
    }

    /**
     * URL IIFE-бандла checkout-button-sdk (глобал YaCheckout).
     */
    private static function getSdkBundleUrl(): string
    {
        return 'https://pay.yandex.ru/static/checkout-button/v1/checkout-button-sdk.js';
    }

    private static function isRubBasketSite(): bool
    {
        if (!Loader::includeModule('sale')) {
            return false;
        }
        $siteId = \Bitrix\Main\Context::getCurrent()->getSite();
        $currency = \Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency($siteId);

        return $currency === '' || $currency === 'RUB';
    }

    /**
     * Экранирование значения для подстановки в JS-строку внутри инлайнового <script>.
     *
     * Кроме кавычек и переводов строки обязательно нейтрализовать последовательность '</':
     * HTML-парсер закрывает инлайновый скрипт на литеральном '</script' даже внутри
     * JS-строки в кавычках, поэтому без этой замены значение опции вида
     * '.foo</script><img src=x onerror=...>' даёт XSS на публичной странице корзины.
     * Ровно это делает CUtil::JSEscape() ядра ('</' -> '<\/').
     */
    private static function escapeJsString($value)
    {
        $value = (string)$value;

        if (class_exists('\CUtil')) {
            return \CUtil::JSEscape($value);
        }

        return str_replace(
            ["\\", "'", "\"", "\r", "\n", "</"],
            ["\\\\", "\\'", "\\\"", "\\r", "\\n", "<\\/"],
            $value
        );
    }

    /**
     * Дефолт CSS кнопки и промо-блока (.yastore-checkout-promo). Синхронизирован с getYastoreCheckoutButtonCssDefault().
     */
    private static function getDefaultButtonCss()
    {
        return "@font-face {\n"
        . "    font-family: 'YS Text';\n"
        . "    src: url('https://yastatic.net/s3/home/fonts/ys/4/text-medium.woff2') format('woff2');\n"
        . "    font-weight: 500 700;\n"
        . "    font-style: normal;\n"
        . "    font-display: swap;\n"
        . "}\n"
        . "@font-face {\n"
        . "    font-family: 'YS Text';\n"
        . "    src: url('https://yastatic.net/s3/home/fonts/ys/4/text-regular.woff2') format('woff2');\n"
        . "    font-weight: 400;\n"
        . "    font-style: normal;\n"
        . "    font-display: swap;\n"
        . "}\n"
        . "#yastore-checkout-button {\n"
            . "    border: none;\n"
            . "    outline: none;\n"
            . "    border-radius: 18px;\n"
            . "    background-color: #170023;\n"
            . "    color: #ffffff;\n"
            . "    padding: 0;\n"
            . "    margin: 12px 0 0 10px;\n"
            . "    width: 100%;\n"
            . "    min-height: 54px;\n"
            . "    font-family: 'YS Text', 'Helvetica Neue', Arial, sans-serif;\n"
            . "    font-weight: 500;\n"
            . "    line-height: 16px;\n"
            . "    display: flex;\n"
            . "    flex-direction: column;\n"
            . "    align-items: center;\n"
            . "    justify-content: center;\n"
            . "    text-decoration: none;\n"
            . "    transition: transform .12s ease-out, filter .12s ease-out;\n"
            . "    cursor: pointer;\n"
            . "    box-sizing: border-box;\n"
            . "}\n"
            . "#yastore-checkout-button:hover,\n"
            . "#yastore-checkout-button:focus,\n"
            . "#yastore-checkout-button:visited {\n"
            . "    color: #ffffff;\n"
            . "    text-decoration: none;\n"
            . "}\n"
            . "#yastore-checkout-button *,\n"
            . "#yastore-checkout-button *:hover {\n"
            . "    text-decoration: none;\n"
            . "    color: inherit;\n"
            . "}\n"
            . ".yastore-checkout-button__main {\n"
            . "    display: flex;\n"
            . "    gap: 6px;\n"
            . "    align-items: center;\n"
            . "    justify-content: center;\n"
            . "    width: 100%;\n"
            . "    padding: 0 16px;\n"
            . "    font-size: 16px;\n"
            . "    line-height: 20px;\n"
            . "    box-sizing: border-box;\n"
            . "}\n"
            . "#yastore-checkout-button:hover {\n"
            . "    filter: brightness(1.08);\n"
            . "}\n"
            . "#yastore-checkout-button:active {\n"
            . "    transform: scale(.97);\n"
            . "    filter: brightness(0.9);\n"
            . "}\n"
            . "\n"
            . "/* Промо: как .pay-button__additional в sdk-payment-method-2 */\n"
            . ".yastore-checkout-promo,\n"
            . ".yastore-checkout-promo .pay-button__additional {\n"
            . "    width: 100%;\n"
            . "    padding: 0 16px;\n"
            . "    margin-top: 0;\n"
            . "    font-size: 12px;\n"
            . "    font-weight: 400;\n"
            . "    line-height: 14px;\n"
            . "    opacity: 0.8;\n"
            . "    text-align: center;\n"
            . "    white-space: nowrap;\n"
            . "    text-decoration: none;\n"
            . "    font-variant-numeric: lining-nums proportional-nums;\n"
            . "}\n"
            . ".yastore-checkout-promo:empty {\n"
            . "    display: none;\n"
            . "}\n"
            . ".yastore-checkout-promo .pay-button__cashback-amount {\n"
            . "    font-size: 10px;\n"
            . "    font-weight: 400;\n"
            . "}\n"
            . ".yastore-checkout-promo .pay-button__discount-amount {\n"
            . "    font-size: 10px;\n"
            . "    font-style: italic;\n"
            . "    font-weight: 400;\n"
            . "}\n"
            . ".yastore-checkout-promo svg {\n"
            . "    display: inline-block;\n"
            . "    margin-right: 1px;\n"
            . "    margin-bottom: -1px;\n"
            . "    vertical-align: baseline;\n"
            . "    flex-shrink: 0;\n"
            . "}\n"
            . ".yastore-checkout-promo svg.pay-label-monochrome {\n"
            . "    vertical-align: text-bottom;\n"
            . "}";
    }

    private static function getDefaultProductButtonCss()
    {
        return "#yastore-checkout-product-button {\n"
            . "    border: none;\n"
            . "    outline: none;\n"
            . "    border-radius: 12px;\n"
            . "    background-color: rgb(255, 99, 41);\n"
            . "    color: #ffffff;\n"
            . "    padding: 0 12px;\n"
            . "    margin: 12px 0 0 10px;\n"
            . "    height: 42px;\n"
            . "    width: 100%;\n"
            . "    font-weight: 600;\n"
            . "    font-family: inherit;\n"
            . "    line-height: 16px;\n"
            . "    display: flex;\n"
            . "    gap: 6px;\n"
            . "    align-items: center;\n"
            . "    justify-content: center;\n"
            . "    transition: transform .12s ease-out, filter .12s ease-out;\n"
            . "}\n"
            . "#yastore-checkout-product-button:hover {\n"
            . "    background: linear-gradient(245deg, rgba(255, 99, 41, 0) 85%, #FFC002 109%), linear-gradient(77deg, rgb(255, 192, 2, .5) .5%, rgba(255, 99, 41, .18) 24%), radial-gradient(57% 134.79% at 0% 0%, #FF27F5 0%, #FF6329 100%);\n"
            . "}\n"
            . "#yastore-checkout-product-button:active {\n"
            . "    transform: scale(.97);\n"
            . "    filter: brightness(0.9);\n"
            . "}";
    }

    /**
     * @deprecated Автоотмена/завершение перенесены в onSaleStatusOrderChange (без дублей).
     * @noinspection PhpUnused
     */
    public static function onSaleOrderSaved($eventOrOrder)
    {
    }

    /**
     * Обработчик события OnSaleStatusOrderChange.
     */
    public static function onSaleStatusOrderChange($eventOrOrder)
    {
        try {
            $order = self::resolveOrderFromSaleEvent($eventOrOrder);
            if (!$order instanceof Order || $order->getId() <= 0) {
                return;
            }

            try {
                self::maybeReleasePromocodeForCancelledKitOrder($order);
            } catch (\Exception $e) {
                \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
            }

            if (!self::isYandexKitOrderForStatusSync($order)) {
                return;
            }

            $orderId = $order->getId();
            $currentStatus = $order->getField('STATUS_ID');

            $autoCancelEnabled = Option::get(self::$MODULE_ID, 'AUTO_CANCEL_ON_STATUS_CHANGE', 'N');
            if ($autoCancelEnabled === 'Y') {
                $autoCancelStatus = Option::get(self::$MODULE_ID, 'AUTO_CANCEL_STATUS', 'C');

                if ($currentStatus === $autoCancelStatus) {
                    if (class_exists('\Bitrix\Sale\OrderHistory')) {
                        \Bitrix\Sale\OrderHistory::addAction(
                            'ORDER',
                            $orderId,
                            'ORDER_COMMENTED',
                            $orderId,
                            $order,
                            ['COMMENTS' => 'Статус заказа изменен на статус отмены (' . $currentStatus . '). Инициирована автоматическая отмена через YCP API.']
                        );
                    }

                    self::sendCancelRequest($orderId, $order);
                }
            }

            $autoCompleteEnabled = Option::get(self::$MODULE_ID, 'AUTO_COMPLETE_ON_STATUS_CHANGE', 'N');
            if ($autoCompleteEnabled === 'Y') {
                $autoCompleteStatus = Option::get(self::$MODULE_ID, 'AUTO_COMPLETE_STATUS', 'F');

                if ($currentStatus === $autoCompleteStatus) {
                    if (class_exists('\Bitrix\Sale\OrderHistory')) {
                        \Bitrix\Sale\OrderHistory::addAction(
                            'ORDER',
                            $orderId,
                            'ORDER_COMMENTED',
                            $orderId,
                            $order,
                            ['COMMENTS' => 'Статус заказа изменен на статус завершения (' . $currentStatus . '). Инициировано автоматическое завершение через YCP API.']
                        );
                    }

                    self::sendCompleteRequest($orderId, $order);
                }
            }

        } catch (\Exception $e) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
        }
    }

    private static function resolveOrderFromSaleEvent($eventOrOrder): ?Order
    {
        if ($eventOrOrder instanceof \Bitrix\Main\Event) {
            $parameters = $eventOrOrder->getParameters();
            $order = $parameters['ENTITY'] ?? null;
        } elseif ($eventOrOrder instanceof Order) {
            $order = $eventOrOrder;
        } else {
            return null;
        }

        return $order instanceof Order ? $order : null;
    }

    private static function maybeReleasePromocodeForCancelledKitOrder(Order $order): void
    {
        if (!self::hasKitExternalOrderId($order)) {
            return;
        }

        if ($order->getField('CANCELED') !== 'Y' || $order->isPaid()) {
            return;
        }

        (new PromocodeCalculator())->releaseUsage($order);
    }

    private static function hasKitExternalOrderId(Order $order): bool
    {
        $externalOrderId = self::getExternalOrderId($order);

        return $externalOrderId !== null && $externalOrderId !== '';
    }

    private static function sendCancelRequest($orderId, Order $order)
    {
        if (self::$ycpApiStatusSyncSuppressed || !self::isYandexKitOrderForStatusSync($order)) {
            return;
        }

        $credentials = Option::get(self::$MODULE_ID, 'YANDEX_KIT_CREDENTIALS', '');

        if (empty($credentials)) {
            $storeId = Option::get(self::$MODULE_ID, 'YANDEX_KIT_STORE_ID', '');
            $apiToken = Option::get(self::$MODULE_ID, 'YANDEX_KIT_API_TOKEN', '');
        } else {
            $parts = explode('#', $credentials, 2);
            if (count($parts) === 2) {
                $storeId = trim($parts[0]);
                $apiToken = trim($parts[1]);
            } else {
                $storeId = '';
                $apiToken = '';
            }
        }

        if (empty($apiToken) || empty($storeId)) {
            return;
        }

        $externalOrderId = self::getExternalOrderId($order);

        if (empty($externalOrderId)) {
            return;
        }

        $apiUrl = rtrim(Option::get(self::$MODULE_ID, 'YANDEX_KIT_API_URL', 'https://integration.yastore.yandex.net/'), '/');
        $url = "{$apiUrl}/api/public/v1/orders/{$externalOrderId}/cancel";

        $headers = [
            'authorization: Bearer ' . $apiToken,
            'yandex-kit-store-id: ' . $storeId,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog(
                new \Exception("Curl error for order cancel: {$error}")
            );
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Ошибка отправки отмены заказа в YCP API: ' . $error]);
            }
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog(
                new \Exception("Failed to cancel order {$externalOrderId}. HTTP code: {$httpCode}, Response: {$response}")
            );
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Ошибка отправки отмены заказа в YCP API. HTTP код: ' . $httpCode . ', Внешний ID: ' . $externalOrderId]);
            }
        } else {
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Отмена заказа успешно отправлена в YCP API. Внешний ID: ' . $externalOrderId]);
            }
        }
    }

    private static function sendCompleteRequest($orderId, Order $order)
    {
        if (self::$ycpApiStatusSyncSuppressed || !self::isYandexKitOrderForStatusSync($order)) {
            return;
        }

        $credentials = Option::get(self::$MODULE_ID, 'YANDEX_KIT_CREDENTIALS', '');

        if (empty($credentials)) {
            $storeId = Option::get(self::$MODULE_ID, 'YANDEX_KIT_STORE_ID', '');
            $apiToken = Option::get(self::$MODULE_ID, 'YANDEX_KIT_API_TOKEN', '');
        } else {
            $parts = explode('#', $credentials, 2);
            if (count($parts) === 2) {
                $storeId = trim($parts[0]);
                $apiToken = trim($parts[1]);
            } else {
                $storeId = '';
                $apiToken = '';
            }
        }

        if (empty($apiToken) || empty($storeId)) {
            return;
        }

        $externalOrderId = self::getExternalOrderId($order);

        if (empty($externalOrderId)) {
            return;
        }

        $apiUrl = rtrim(Option::get(self::$MODULE_ID, 'YANDEX_KIT_API_URL', 'https://integration.yastore.yandex.net/'), '/');
        $url = "{$apiUrl}/api/public/v1/orders/{$externalOrderId}/delivery/total_complete/self_pick_up";

        $headers = [
            'authorization: Bearer ' . $apiToken,
            'yandex-kit-store-id: ' . $storeId,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog(
                new \Exception("Curl error for order complete: {$error}")
            );
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Ошибка отправки завершения заказа в YCP API: ' . $error]);
            }
        } elseif ($httpCode < 200 || $httpCode >= 300) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog(
                new \Exception("Failed to complete order {$externalOrderId}. HTTP code: {$httpCode}, Response: {$response}")
            );
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Ошибка отправки завершения заказа в YCP API. HTTP код: ' . $httpCode . ', Внешний ID: ' . $externalOrderId]);
            }
        } else {
            if (class_exists('\Bitrix\Sale\OrderHistory')) {
                \Bitrix\Sale\OrderHistory::addAction('ORDER', $orderId, 'ORDER_COMMENTED', $orderId, $order,
                    ['COMMENTS' => 'Завершение заказа успешно отправлено в YCP API. Внешний ID: ' . $externalOrderId]);
            }
        }
    }

    private static function getExternalOrderId(Order $order)
    {
        try {
            $propertyCollection = $order->getPropertyCollection();

            foreach ($propertyCollection as $property) {
                $code = $property->getField('CODE');
                if ($code === 'YANDEX_ORDER_ID') {
                    $value = $property->getValue();
                    if (!empty($value)) {
                        return $value;
                    }
                }
            }

            foreach ($propertyCollection as $property) {
                $code = $property->getField('CODE');
                if ($code === 'EXTERNAL_ORDER_ID') {
                    $value = $property->getValue();
                    if (!empty($value)) {
                        return $value;
                    }
                }
            }

        } catch (\Exception $e) {
            \Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($e);
        }

        return null;
    }
}
