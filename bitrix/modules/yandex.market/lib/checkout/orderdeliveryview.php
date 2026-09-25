<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Order;

/**
 * Отображение данных доставки Яндекса на странице заказа.
 *
 * Данные хранятся в служебных (UTIL='Y') свойствах заказа и рендерятся кастомно:
 *  - в админке — отдельной вкладкой на детальной странице заказа (OnAdminSaleOrderView);
 *  - в личном кабинете покупателя — блоком на странице заказа (OnEndBufferContent).
 *
 * Поля: DELIVERY_STATUS, DELIVERY_DATE, DELIVERY_TRACKING_URL, DELIVERY_BARCODE_URL.
 */
class OrderDeliveryView
{
    const MODULE_ID = 'yandex.market';

    /**
     * Коды свойств доставки и их подписи (порядок = порядок вывода).
     *
     * @return array<string, string>
     */
    private static function getFieldLabels(): array
    {
        return [
            'DELIVERY_STATUS' => 'Статус доставки',
            'DELIVERY_DATE' => 'Дата доставки',
            'DELIVERY_TRACKING_URL' => 'Отслеживание',
            'DELIVERY_BARCODE_URL' => 'Штрихкод',
        ];
    }

    /**
     * Коды свойств, значения которых — ссылки (рендерятся как кликабельные).
     *
     * @return string[]
     */
    private static function getUrlCodes(): array
    {
        return ['DELIVERY_TRACKING_URL', 'DELIVERY_BARCODE_URL'];
    }

    /**
     * Значение безопасно для подстановки в href.
     *
     * htmlspecialcharsbx() закрывает выход из атрибута, но не ограничивает схему URI,
     * поэтому javascript:-ссылка из внешнего API иначе стала бы кликабельной
     * в админке и в личном кабинете покупателя.
     */
    private static function isSafeUrl(string $value): bool
    {
        $scheme = parse_url(trim($value), PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * HTML значения свойства: ссылка для безопасных URL, иначе экранированный текст.
     */
    private static function renderValueHtml(string $code, string $value): string
    {
        $safe = htmlspecialcharsbx($value);

        if (in_array($code, self::getUrlCodes(), true) && self::isSafeUrl($value)) {
            return '<a href="' . $safe . '" target="_blank" rel="noopener noreferrer">' . $safe . '</a>';
        }

        return $safe;
    }

    /**
     * Значения свойств доставки заказа (только непустые), [CODE => VALUE].
     *
     * @return array<string, string>
     */
    public static function getDeliveryData(Order $order): array
    {
        $result = [];

        foreach (array_keys(self::getFieldLabels()) as $code) {
            $value = self::getOrderPropertyValue($order, $code);
            if ($value !== null && $value !== '') {
                $result[$code] = $value;
            }
        }

        return $result;
    }

    // --- Админка: вкладка на детальной странице заказа ----------------------

    /**
     * Обработчик OnAdminSaleOrderView: добавляет вкладку с данными доставки.
     *
     * @param int $ID ID заказа
     * @return array|null дескриптор вкладки или null, если показывать нечего
     * @noinspection PhpUnused
     */
    public static function OnAdminSaleOrderView($parameters)
    {
        if (!Loader::includeModule('sale')) {
            return null;
        }

        $orderId = self::resolveAdminOrderId($parameters);
        if ($orderId <= 0) {
            return null;
        }

        try {
            $order = Order::load($orderId);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$order) {
            return null;
        }

        $data = self::getDeliveryData($order);
        if (empty($data)) {
            return null;
        }

        $rowsHtml = self::renderAdminRows($data);

        return [
            'TABSET' => 'YANDEX_KIT_DELIVERY',
            'GetTabs' => static function () {
                return [
                    [
                        'DIV' => 'yandex_kit_delivery',
                        'TAB' => 'Доставка Яндекс',
                        'TITLE' => 'Данные доставки Яндекс',
                    ],
                ];
            },
            'ShowTab' => static function () use ($rowsHtml) {
                echo $rowsHtml;
            },
        ];
    }

    private static function renderAdminRows(array $data): string
    {
        $labels = self::getFieldLabels();
        $rows = '';

        foreach ($labels as $code => $label) {
            if (!isset($data[$code])) {
                continue;
            }

            $valueHtml = self::renderValueHtml($code, (string)$data[$code]);

            $rows .= '<tr><td width="40%">' . htmlspecialcharsbx($label) . '</td><td>' . $valueHtml . '</td></tr>';
        }

        return $rows;
    }

    // --- Личный кабинет: блок на странице заказа ----------------------------

    /**
     * Обработчик OnEndBufferContent: внедряет блок доставки на странице заказа в ЛК.
     *
     * @param string $content полный HTML страницы (по ссылке)
     * @noinspection PhpUnused
     */
    public static function onEndBufferContent(&$content)
    {
        try {
            if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
                return;
            }
            if (!is_string($content) || $content === '') {
                return;
            }
            if (!Loader::includeModule('sale')) {
                return;
            }

            $request = Context::getCurrent()->getRequest();
            $page = (string)$request->getRequestedPage();

            $pathOption = trim((string)Option::get(self::MODULE_ID, 'YAKIT_PERSONAL_ORDER_PATH', '/personal/order/'));
            if ($pathOption === '') {
                $pathOption = '/personal/order/';
            }
            $pathNeedle = trim(str_replace('\\', '/', $pathOption), '/');
            if ($pathNeedle === '' || strpos($page, $pathNeedle) === false) {
                return;
            }

            $orderId = self::resolveCustomerOrderId($request, $page);
            if ($orderId <= 0) {
                return;
            }

            $order = Order::load($orderId);
            if (!$order) {
                return;
            }

            global $USER;
            $currentUserId = (is_object($USER) && method_exists($USER, 'GetID')) ? (int)$USER->GetID() : 0;
            if ($currentUserId <= 0 || (int)$order->getUserId() !== $currentUserId) {
                return;
            }

            $data = self::getDeliveryData($order);
            if (empty($data)) {
                return;
            }

            $injection = self::renderCustomerInjection($data);

            if (stripos($content, '</body>') !== false) {
                $content = preg_replace('/<\/body>/i', $injection . '</body>', $content, 1);
            } else {
                $content .= $injection;
            }
        } catch (\Throwable $e) {
            // отображение не должно ломать страницу
        }
    }

    private static function resolveCustomerOrderId($request, string $page): int
    {
        $requestId = (int)$request->get('ID');
        if ($requestId > 0) {
            return $requestId;
        }

        $path = (string)parse_url($page, PHP_URL_PATH);
        if (preg_match_all('/(\d+)/', $path, $matches) && !empty($matches[1])) {
            return (int)end($matches[1]);
        }

        return 0;
    }

    private static function renderCustomerInjection(array $data): string
    {
        $blockHtml = self::renderCustomerBlockHtml($data);
        $anchor = trim((string)Option::get(self::MODULE_ID, 'YAKIT_PERSONAL_ORDER_ANCHOR', ''));

        $css = '.ya-delivery-info{margin:16px 0;padding:16px;border:1px solid #e0e0e0;border-radius:8px}'
            . '.ya-delivery-info__title{font-weight:600;font-size:16px;margin-bottom:10px}'
            . '.ya-delivery-info__row{display:flex;flex-wrap:wrap;gap:8px;margin:6px 0}'
            . '.ya-delivery-info__label{color:#808080;min-width:160px}'
            . '.ya-delivery-info__value{word-break:break-word}';

        $payload = Json::encode([
            'html' => $blockHtml,
            'anchor' => $anchor,
        ]);

        $js = '<script>(function(){'
            . 'var d=' . $payload . ';'
            . 'function insert(){'
            . 'if(document.getElementById("ya-delivery-info-block")){return;}'
            . 'var wrap=document.createElement("div");wrap.id="ya-delivery-info-block";wrap.innerHTML=d.html;'
            . 'var target=null;'
            . 'if(d.anchor){try{target=document.querySelector(d.anchor);}catch(e){target=null;}}'
            . 'if(!target){target=document.querySelector(".sale-order-detail, .order-detail, .personal-order-detail, .sale-paysystem-wrapper, #content, main");}'
            . 'if(target){target.insertBefore(wrap,target.firstChild);}else{document.body.appendChild(wrap);}'
            . '}'
            . 'if(document.readyState!=="loading"){insert();}else{document.addEventListener("DOMContentLoaded",insert);}'
            . '})();</script>';

        return '<style>' . $css . '</style>' . $js;
    }

    private static function renderCustomerBlockHtml(array $data): string
    {
        $labels = self::getFieldLabels();
        $rows = '';

        foreach ($labels as $code => $label) {
            if (!isset($data[$code])) {
                continue;
            }

            $valueHtml = self::renderValueHtml($code, (string)$data[$code]);

            $rows .= '<div class="ya-delivery-info__row">'
                . '<span class="ya-delivery-info__label">' . htmlspecialcharsbx($label) . '</span>'
                . '<span class="ya-delivery-info__value">' . $valueHtml . '</span>'
                . '</div>';
        }

        return '<div class="ya-delivery-info"><div class="ya-delivery-info__title">Доставка</div>' . $rows . '</div>';
    }

    // --- Общее --------------------------------------------------------------

    /**
     * ID заказа из аргументов OnAdminSaleOrderView (Bitrix передаёт ['ID' => ...]).
     */
    private static function resolveAdminOrderId($parameters): int
    {
        if (is_array($parameters)) {
            return (int)($parameters['ID'] ?? 0);
        }

        return (int)$parameters;
    }

    /**
     * Значение свойства заказа по коду (совместимо со старыми версиями Bitrix).
     *
     * @return string|null
     */
    private static function getOrderPropertyValue(Order $order, string $code)
    {
        try {
            $propertyCollection = $order->getPropertyCollection();

            if (method_exists($propertyCollection, 'getItemByOrderPropertyCode')) {
                $propItem = $propertyCollection->getItemByOrderPropertyCode($code);
                if (!$propItem) {
                    return null;
                }
                $value = $propItem->getValue();
            } else {
                $value = null;
                foreach ($propertyCollection as $property) {
                    if ($property->getField('CODE') === $code) {
                        $value = $property->getValue();
                        break;
                    }
                }
            }

            if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                return null;
            }

            return is_array($value) ? (string)reset($value) : (string)$value;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
