<?php
namespace Yandex\Market\Checkout;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Yandex\Market\Checkout\Handlers\ApplyPromocodesHandler;
use Yandex\Market\Checkout\Handlers\CheckBasketHandler;
use Yandex\Market\Checkout\Handlers\WarehousesHandler;
use Yandex\Market\Checkout\Handlers\OrdersHandler;
use Yandex\Market\Checkout\Handlers\DeliveryHandler;
use Yandex\Market\Checkout\Handlers\SettingsHandler;
use Yandex\Market\Checkout\Handlers\SetAuthKeyHandler;
use Yandex\Market\Checkout\Handlers\EndpointsHandler;

class Api
{
    private $request;
    private $moduleId = 'yandex.market';

    public function __construct()
    {
        Loader::includeModule('yandex.market');
        $this->request = Application::getInstance()->getContext()->getRequest();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Очищает буфер вывода перед выдачей JSON.
     *
     * Ядро и сторонние модули могут напечатать в буфер посторонний HTML
     * (например, <p><font class="errortext">Access denied</font></p>),
     * из-за чего ответ API перестаёт быть валидным JSON.
     *
     * @return void
     */
    public static function resetOutputBuffer()
    {
        global $APPLICATION;
        
        while (ob_get_level() > 1) {
            if (@ob_end_clean() === false) {
                break;
            }
        }

        if (is_object($APPLICATION) && method_exists($APPLICATION, 'RestartBuffer')) {
            $APPLICATION->RestartBuffer();
        } elseif (ob_get_level() > 0) {
            ob_clean();
        }
    }

    public function handleRequest()
    {
        $this->resolveResponse()->send();
    }

    /**
     * Выполняет обработчик запроса и возвращает его ответ
     *
     * @return ApiResponse
     */
    private function resolveResponse()
    {
        try {
            // Авторизация — до роутинга: иначе разница между 405 (метод есть, глагол не тот)
            // и 404 (метода нет) позволяет анониму перебором составить карту API,
            // включая не объявленные в ApiRouter методы вроде setAuthKey.
            if (!$this->validateJwt()) {
                throw new \RuntimeException('Unauthorized', 401);
            }

            $method = $this->request->get('method');
            if ($method === null || $method === '') {
                $method = ApiRouter::resolveInternalMethod();
            }
            $action = $this->request->get('action');
            $orderId = $this->request->get('orderId');

            $handler = $this->getHandler($method, $action);

            if (!$handler) {
                throw new \RuntimeException('Unknown method', 404);
            }

            if ($method === 'orders' && $this->request->getRequestMethod() === 'POST' && ($action === null || $action === '')) {
                ignore_user_abort(true);
            }

            $response = $handler->handle($orderId);

            if (!$response instanceof ApiResponse) {
                return ApiResponse::error('Handler returned no response');
            }

            return $response;

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function getHandler($method, $action)
    {
        switch ($method) {
            case 'checkBasket':
                if ($this->request->getRequestMethod() === 'POST') {
                    return new CheckBasketHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'applyPromocodes':
                if ($this->request->getRequestMethod() === 'POST') {
                    return new ApplyPromocodesHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'warehouses':
                if ($this->request->getRequestMethod() === 'GET') {
                    return new WarehousesHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'orders':
                if ($this->request->getRequestMethod() === 'POST') {
                    return new OrdersHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'orderDelivery':
                if ($this->request->getRequestMethod() === 'POST') {
                    return new DeliveryHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'settings':
                if ($this->request->getRequestMethod() === 'GET') {
                    return new SettingsHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'endpoints':
                if ($this->request->getRequestMethod() === 'GET') {
                    return new EndpointsHandler();
                }
                throw new \Exception('Method not allowed', 405);

            case 'setAuthKey':
                if ($this->request->getRequestMethod() === 'POST') {
                    return new SetAuthKeyHandler();
                }
                throw new \Exception('Method not allowed', 405);

            default:
                return null;
        }
    }

    /**
     * Возвращает значение заголовка Authorization из запроса.
     */
    public static function getAuthorizationHeader()
    {
        if (function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            $auth = $headers['authorization'] ?? '';
            $auth = trim($auth, " !@#$%^&*()_+-=[]{}|\\:;\"'<>,.?/~`");
            if ($auth !== '') {
                return $auth;
            }
        }
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (!empty($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }
        return '';
    }

    private function validateJwt()
    {
        $authHeader = self::getAuthorizationHeader();

        if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
            return false;
        }

        $token = substr($authHeader, 7);
        $validToken = Option::get($this->moduleId, 'JWT_TOKEN', '');

        if (empty($validToken)) {
            return false;
        }
        return is_string($token) && hash_equals($validToken, $token);
    }
}
