<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Catalog\StoreTable;
use Bitrix\Catalog\StoreProductTable;
use Yandex\Market\Checkout\Api;
use Yandex\Market\Checkout\ApiResponse;

abstract class BaseHandler
{
    const ERROR_INVALID_INPUT = 'INVALID_INPUT';
    const ERROR_UNAUTHORIZED = 'UNAUTHORIZED';
    const ERROR_NOT_FOUND = 'NOT_FOUND';
    const ERROR_CONFLICT = 'CONFLICT';
    const ERROR_INTERNAL = 'INTERNAL_ERROR';
    const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    const ERROR_INVENTORY_CONFLICT = 'INVENTORY_CONFLICT';
    const ERROR_INVALID_PROMOCODE = 'INVALID_PROMOCODE';

    /** Причины отказа apply promocodes. */
    const PROMOCODE_REASON_NOT_FOUND = 'NOT_FOUND';
    const PROMOCODE_REASON_NOT_APPLICABLE = 'NOT_APPLICABLE';
    const PROMOCODE_REASON_NOT_AVAILABLE = 'NOT_AVAILABLE';

    protected $request;
    protected $moduleId = 'yandex.market';

    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest();
    }

    /**
     * Обрабатывает запрос и возвращает ответ.
     *
     * Обработчик ничего не печатает: вывод выполняет Api::handleRequest().
     *
     * @param string|null $orderId
     * @return ApiResponse
     */
    abstract public function handle($orderId = null);

    /**
     * Успешный ответ
     *
     * @param mixed $data Тело ответа
     * @param int $httpCode HTTP-код ответа
     * @return ApiResponse
     */
    protected function response($data, $httpCode = 200)
    {
        return ApiResponse::create($data, $httpCode);
    }

    /**
     * Ответ с ошибкой
     *
     * @param string $message Текст ошибки
     * @param int $httpCode HTTP-код ответа
     * @param string|null $code Код ошибки модуля (одна из констант ERROR_*)
     * @return ApiResponse
     */
    protected function error($message, $httpCode = 500, $code = null)
    {
        return ApiResponse::error($message, $httpCode);
    }

    /**
     * Ответ с ошибкой и дополнительными полями
     *
     * @param string $message Текст ошибки
     * @param int $httpCode HTTP-код ответа
     * @param string|null $code Код ошибки модуля (одна из констант ERROR_*)
     * @param array $extra Дополнительные поля тела ответа
     * @return ApiResponse
     */
    protected function errorWithData($message, $httpCode = 500, $code = null, array $extra = [])
    {
        return ApiResponse::error($message, $httpCode, $extra);
    }

    /**
     * Ответ 401 Unauthorized
     *
     * @return ApiResponse
     */
    protected function unauthorized()
    {
        return $this->error('Unauthorized', 401, self::ERROR_UNAUTHORIZED);
    }

    /**
     * Ответ 422 с причиной отказа
     *
     * @param string $reason Причина отказа
     * @return ApiResponse
     */
    protected function unprocessableEntity(string $reason)
    {
        return $this->response(['reason' => $reason], 422);
    }

    /**
     * Ответ 422 со списком непринятых промокодов
     *
     * @param string[] $invalidPromocodes
     * @param string $reason Причина отказа
     * @return ApiResponse
     */
    protected function promocodeValidationError(array $invalidPromocodes, string $reason)
    {
        return $this->response([
            'invalid_promocodes' => array_values($invalidPromocodes),
            'reason' => $reason,
        ], 422);
    }

    protected function authorizeRequest()
    {
        $authHeader = Api::getAuthorizationHeader();

        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return false;
        }

        $token = trim(substr($authHeader, 7));
        $validToken = trim((string) Option::get($this->moduleId, 'JWT_TOKEN', ''));

        if ($token === '' || $validToken === '') {
            return false;
        }

        return hash_equals($validToken, $token);
    }

    /**
     * Проверяет авторизацию запроса
     *
     * @return ApiResponse|null Ответ 401, если запрос не авторизован, иначе null
     */
    protected function checkAuthorization()
    {
        return $this->authorizeRequest() ? null : $this->unauthorized();
    }

    protected function useGeneralStockOnly()
    {
        return Option::get($this->moduleId, 'USE_GENERAL_STOCK_ONLY', 'N') === 'Y';
    }

    protected function getGeneralWarehouseForApi()
    {
        return $this->getVirtualWarehouse();
    }

    protected function getVirtualWarehouse()
    {
        return [
            'id' => '1',
            'name' => 'Виртуальный склад',
        ];
    }

    protected function hasWarehouseStock($productId)
    {
        if (!Loader::includeModule('catalog')) {
            return false;
        }

        $storeProduct = StoreProductTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productId,
                '>AMOUNT' => 0
            ],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        return (bool)$storeProduct;
    }

    protected function hasWarehouses()
    {
        if (!Loader::includeModule('catalog')) {
            return false;
        }

        $warehouse = StoreTable::getList([
            'filter' => ['ACTIVE' => 'Y'],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        return (bool)$warehouse;
    }

    protected function isStoreControlEnabled()
    {
        if (!Loader::includeModule('sale')) {
            return false;
        }

        if (method_exists('Bitrix\Sale\Configuration', 'useStoreControl')) {
            return \Bitrix\Sale\Configuration::useStoreControl();
        }

        return \Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control', 'N') === 'Y';
    }
}
