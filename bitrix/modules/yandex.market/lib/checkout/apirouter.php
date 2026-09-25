<?php

namespace Yandex\Market\Checkout;

use Yandex\Market\Checkout\Handlers\ApplyPromocodesHandler;
use Yandex\Market\Checkout\Handlers\CheckBasketHandler;
use Yandex\Market\Checkout\Handlers\WarehousesHandler;
use Yandex\Market\Checkout\Handlers\OrdersHandler;
use Yandex\Market\Checkout\Handlers\DeliveryHandler;
use Yandex\Market\Checkout\Handlers\SettingsHandler;
use Yandex\Market\Checkout\Handlers\EndpointsHandler;

/**
 * Сопоставление путей YCP (api/v1/...) с внутренними именами методов API (?method=).
 */
class ApiRouter
{
    /**
     * @return string|null внутреннее имя (warehouses, checkBasket, …) или null — использовать query method=
     */
    public static function resolveInternalMethod(): ?string
    {
        $path = self::getPathRelativeToEntry();
        if ($path === '') {
            return null;
        }
        $path = strtolower($path);
        $httpMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        $routes = self::getRoutes();

        return $routes[$httpMethod][$path] ?? null;
    }

    /**
     * Таблица публичных маршрутов модуля.
     * Единый источник правды для роутинга и для эндпоинта со списком эндпоинтов.
     *
     * @return array<string, array<string, string>> [HTTP_METHOD => [path => internalMethod]]
     */
    public static function getRoutes(): array
    {
        return [
            'GET' => [
                'api/v1/warehouses' => 'warehouses',
                'api/v1/settings' => 'settings',
                'api/v1/endpoints' => 'endpoints',
            ],
            'POST' => [
                'api/v1/checkout/basket/check' => 'checkBasket',
                'api/v1/checkout/promocodes/apply' => 'applyPromocodes',
                'api/v1/orders' => 'orders',
                'api/v1/order/delivery' => 'orderDelivery',
            ],
        ];
    }

    /**
     * Плоский список поддерживаемых эндпоинтов в виде строк "METHOD path".
     *
     * @return string[]
     */
    public static function getSupportedEndpoints(): array
    {
        $endpoints = [];
        $handlerMap = self::getHandlerMap();

        foreach (self::getRoutes() as $httpMethod => $paths) {
            foreach ($paths as $path => $internalMethod) {
                $baseEndpoint = $httpMethod . ' /' . ltrim($path, '/');

                if (isset($handlerMap[$internalMethod])) {
                    $handlerClass = $handlerMap[$internalMethod];
                    if (class_exists($handlerClass) && method_exists($handlerClass, 'getSupportedActions')) {
                        $actions = call_user_func([$handlerClass, 'getSupportedActions']);
                        foreach ($actions as $action) {
                            if (empty($action)) {
                                $endpoints[] = $baseEndpoint;
                            } else {
                                $endpoints[] = $baseEndpoint . '/' . $action;
                            }
                        }

                        continue;
                    }
                }

                $endpoints[] = $baseEndpoint;
            }
        }

        sort($endpoints);

        return $endpoints;
    }

    /**
     * Маппинг внутренних методов на классы обработчиков.
     */
    private static function getHandlerMap(): array
    {
        return [
            'warehouses' => WarehousesHandler::class,
            'settings' => SettingsHandler::class,
            'endpoints' => EndpointsHandler::class,
            'checkBasket' => CheckBasketHandler::class,
            'applyPromocodes' => ApplyPromocodesHandler::class,
            'orders' => OrdersHandler::class,
            'orderDelivery' => DeliveryHandler::class,
        ];
    }

    private static function getPathRelativeToEntry(): string
    {
        $uriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uriPath = '/' . trim(str_replace('\\', '/', $uriPath), '/');

        $publicPrefix = '/yastore.checkout/';
        if (stripos($uriPath, $publicPrefix) === 0) {
            $rel = substr($uriPath, strlen(rtrim($publicPrefix, '/')));
            $rel = ltrim($rel, '/');
        } else {
            $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseDir = rtrim(dirname($scriptName), '/');
            if ($baseDir !== '' && strpos($uriPath, $baseDir) === 0) {
                $rel = trim(substr($uriPath, strlen($baseDir)), '/');
            } else {
                $rel = trim($uriPath, '/');
            }
        }

        $rel = (string) preg_replace('#^index\.php/?#i', '', $rel);

        return trim($rel, '/');
    }
}
