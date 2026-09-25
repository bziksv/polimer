<?php
namespace Yandex\Market\Checkout\Controller;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Order;
use Yandex\Market\Checkout\ProductIdResolver;
use Yandex\Market\Checkout\ProductImageHelper;

class Checkout extends Controller
{
    private static $MODULE_ID = "yandex.market";
    private static $CHECKOUT_URL_BASE = "https://checkout.yastore.yandex.ru";

    private $siteId;
    private $currencyCode;
    private $userId;

    public function __construct()
    {
        parent::__construct();
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        $this->siteId = Application::getInstance()->getContext()->getSite();
        $this->currencyCode = Sale\Internals\SiteCurrencyTable::getSiteCurrency($this->siteId);
        $this->userId = Option::get(self::$MODULE_ID, 'YASTORE_USER_ID', '');
    }

    public function configureActions()
    {
        return [
            'basketToCheckout' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'productToCheckout' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'basketItems' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
        ];
    }

    /**
     * Отдаёт текущую корзину в формате checkout-button-sdk (getCart): { items: [...] }.
     * Используется новой кнопкой на SDK для построения URL редиректа на YCP GET /express
     */
    public function basketItemsAction()
    {
        $basket = Basket::loadItemsForFUser(Sale\Fuser::getId(), $this->siteId);
        if ($basket->isEmpty()) {
            $this->addError(new Error('Empty basket', 500));
            return null;
        }

        $expressData = $this->buildExpressData($basket);
        if (empty($expressData['items'])) {
            $this->addError(new Error('No valid basket items', 500));
            return null;
        }

        return ['items' => $expressData['items']];
    }

    /**
     * Строит URL для редиректа на YCP GET /express (host, metric_client_id, data).
     */
    public function basketToCheckoutAction($metricaClientId)
    {
        $basket = Basket::loadItemsForFUser(Sale\Fuser::getId(), $this->siteId);
        if ($basket->isEmpty()) {
            $this->addError(new Error('Empty basket', 500));
            return null;
        }

        $expressData = $this->buildExpressData($basket);
        if (empty($expressData['items'])) {
            $this->addError(new Error('No valid basket items', 500));
            return null;
        }

        return $this->buildExpressRedirectResponse($expressData, $metricaClientId);
    }

    /**
     * Добавляет товар в корзину текущего пользователя и возвращает URL редиректа в YCP checkout.
     */
    public function productToCheckoutAction($productId, $quantity = 1, $metricaClientId = null)
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            $this->addError(new Error('Invalid product id', 400));
            return null;
        }

        $quantity = (float)$quantity;
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $basket = Basket::loadItemsForFUser(Sale\Fuser::getId(), $this->siteId);
        if (!$this->upsertBasketItem($basket, $productId, $quantity)) {
            return null;
        }

        $expressData = $this->buildExpressData($basket);
        if (empty($expressData['items'])) {
            $this->addError(new Error('No valid basket items', 500));
            return null;
        }

        return $this->buildExpressRedirectResponse($expressData, $metricaClientId);
    }

    private function upsertBasketItem(Basket $basket, $productId, $quantity)
    {
        $basketItem = $basket->getExistsItem('catalog', $productId);
        if ($basketItem) {
            $newQuantity = (float)$basketItem->getQuantity() + $quantity;
            $setResult = $basketItem->setField('QUANTITY', $newQuantity);
            if (!$setResult->isSuccess()) {
                $this->addError(new Error('Failed to update product quantity in basket', 500));
                return false;
            }
        } else {
            $basketItem = $basket->createItem('catalog', $productId);
            $setResult = $basketItem->setFields([
                'QUANTITY' => $quantity,
                'CURRENCY' => $this->currencyCode,
                'LID' => $this->siteId,
                'PRODUCT_PROVIDER_CLASS' => '\\CCatalogProductProvider',
            ]);
            if (!$setResult->isSuccess()) {
                $this->addError(new Error('Failed to add product to basket', 500));
                return false;
            }
        }

        $saveResult = $basket->save();
        if (!$saveResult->isSuccess()) {
            $this->addError(new Error('Failed to save basket', 500));
            return false;
        }

        return true;
    }

    private function buildExpressRedirectResponse(array $expressData, $metricaClientId = null)
    {
        $expressUrl = 'https://checkout.kit.yandex.ru/express';

        $request = Application::getInstance()->getContext()->getRequest();
        $host = $request->getHttpHost() ?: $request->getServer()->get('HTTP_HOST');

        $dataJson = json_encode($expressData, JSON_UNESCAPED_UNICODE);
        $params = [
            'host' => $host,
            'data' => base64_encode($dataJson),
        ];
        if ($metricaClientId !== null && $metricaClientId !== '') {
            $params['metric_client_id'] = $metricaClientId;
        }

        $redirectUrl = $expressUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return [
            'status' => 'success',
            'url' => $redirectUrl,
        ];
    }

    private const EXPRESS_INITIATOR_BITRIX = 'bitrix';

    private function buildExpressData(Basket $basket)
    {
        $discounts = $this->getDiscounts($basket);
        $items = [];

        foreach ($basket as $basketItem) {
            $productId = $basketItem->getProductId();
            $basketCode = $basketItem->getBasketCode();
            $quantity = (int) $basketItem->getQuantity();
            $basePrice = (float) $basketItem->getBasePrice();
            $finalPrice = (float) $basketItem->getFinalPrice();

            if (isset($discounts['PRICES']['BASKET'][$basketCode]['PRICE'])) {
                $finalPrice = (float) $discounts['PRICES']['BASKET'][$basketCode]['PRICE'];
            }
            if ((int) $finalPrice == 0 && (int) $basePrice == 0) {
                $resultPrice = $this->getResultPrice($productId);
                if ($resultPrice) {
                    $basePrice = (float) $resultPrice['BASE_PRICE'];
                    $finalPrice = (float) $resultPrice['DISCOUNT_PRICE'];
                }
            }

            $items[] = [
                'id' => ProductIdResolver::getExternalId($productId),
                'quantity' => $quantity,
                'price' => $basePrice,
                'final_price' => $finalPrice,
            ];
        }

        return [
            'items' => $items,
            'initiator' => self::EXPRESS_INITIATOR_BITRIX,
        ];
    }

    private function getDiscounts($basket)
    {
        $discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
        $discounts->calculate();
        $arBasketDiscounts = $discounts->getApplyResult(true);
        return $arBasketDiscounts;
    }

    private function getImageUrl($itemData, $productId)
    {
        $pid = (int)$productId;
        if ($pid <= 0) {
            return '';
        }
        $iblockId = isset($itemData['IBLOCK_ID']) ? (int)$itemData['IBLOCK_ID'] : null;
        $url = ProductImageHelper::resolveUrl($pid, $iblockId, is_array($itemData) ? $itemData : []);

        return $url !== null && $url !== '' ? $url : '';
    }

    private function createOrder($basket)
    {
        $order = Order::create($this->siteId, $this->userId);
        $order->setPersonTypeId(1);
        $order->setBasket($basket);
        $order->setField('CURRENCY', $this->currencyCode);

        $order->doFinalAction(true);

        return $order;
    }

    private function getResultPrice($productId)
    {
        $arPrice = \CCatalogProduct::GetOptimalPrice($productId, 1, [], 'N');

        if ($arPrice) {
            return $arPrice['RESULT_PRICE'];
        } else {
            $this->addError(new Error("No price for productId=$productId", 400));
            return null;
        }
    }
}
