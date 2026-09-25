<?php
namespace Yandex\Market\Checkout\Services;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\OrderCouponsTable;
use Bitrix\Sale\Order;
use Yandex\Market\Checkout\ProductIdResolver;
use Yandex\Market\Checkout\Services\BasketPriceValidator;

class PromocodeCalculator
{
    /**
     * Validate-only: собирает корзину, проверяет актуальность цен, применяет промокоды через Sale.
     *
     * @param array<int, array{id: string, regular_price: float, final_price: float}>|null $priceExpectations
     * @return array{
     *     items: array|null,
     *     error: string|null,
     *     price_conflicts: array|null,
     *     promocode_validation: array{invalid_promocodes: string[], reason: string}|null
     * }
     */
    public function calculateApplyPrices(
        array $items,
        array $promocodes,
        int $userId,
        string $siteId,
        ?array $priceExpectations = null
    ): array {
        if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            return ['items' => null, 'error' => 'Required modules not available', 'price_conflicts' => null, 'promocode_validation' => null];
        }

        $normalizedPromocodes = $this->normalizePromocodeSlugs($promocodes);
        if ($normalizedPromocodes === null) {
            return ['items' => null, 'error' => 'Invalid promocode format', 'price_conflicts' => null, 'promocode_validation' => null];
        }

        $order = Order::create($siteId, $userId);
        $basket = Basket::create($siteId);
        $basketError = $this->buildBasketFromRequestItems($basket, $items, $siteId);
        if ($basketError !== null) {
            return ['items' => null, 'error' => $basketError, 'price_conflicts' => null, 'promocode_validation' => null];
        }

        if ($priceExpectations !== null) {
            $priceValidator = new BasketPriceValidator();
            if ($priceValidator->hasBasketItemCountMismatch($basket, $priceExpectations)) {
                return ['items' => null, 'error' => 'Basket item count mismatch', 'price_conflicts' => null, 'promocode_validation' => null];
            }

            $priceConflicts = $priceValidator->findPriceConflicts($basket, $priceExpectations);
            if (!empty($priceConflicts['items'])) {
                return ['items' => null, 'error' => null, 'price_conflicts' => $priceConflicts, 'promocode_validation' => null];
            }
        }

        $order->setBasket($basket);

        $applyError = $this->applyToOrder($order, $userId, $normalizedPromocodes);
        if ($applyError !== null) {
            return ['items' => null, 'error' => $applyError, 'price_conflicts' => null, 'promocode_validation' => null];
        }

        $invalidPromocodes = $this->findInvalidPromocodes($normalizedPromocodes);
        if ($invalidPromocodes !== null) {
            return ['items' => null, 'error' => null, 'price_conflicts' => null, 'promocode_validation' => $invalidPromocodes];
        }

        return ['items' => $this->extractBasketItemPrices($basket), 'error' => null, 'price_conflicts' => null, 'promocode_validation' => null];
    }

    /**
     * @return string[]|null
     */
    public function normalizePromocodeSlugs(array $promocodes): ?array
    {
        $normalized = [];

        foreach ($promocodes as $promocode) {
            if (!is_string($promocode) && !is_numeric($promocode)) {
                return null;
            }

            $slug = trim((string)$promocode);
            if ($slug === '') {
                return null;
            }

            $normalized[] = $slug;
        }

        return $normalized;
    }

    /**
     * @return string|null
     */
    public function initPromocodeManagerForUser(int $userId): ?string
    {
        DiscountCouponsManager::reInit(
            DiscountCouponsManager::MODE_MANAGER,
            ['userId' => $userId],
            false
        );
        DiscountCouponsManager::clear(true);

        if (!DiscountCouponsManager::isSuccess()) {
            return 'Failed to initialize promocode manager: ' . implode(', ', DiscountCouponsManager::getErrors());
        }

        return null;
    }

    /**
     * Добавляет промокоды и пересчитывает скидки (validate-only, без сохранения заказа).
     *
     * @param string[] $promocodes
     * @return string|null
     */
    public function applyToOrder(Order $order, int $userId, array $promocodes): ?string
    {
        if (empty($promocodes)) {
            return null;
        }

        $initError = $this->initPromocodeManagerForUser($userId);
        if ($initError !== null) {
            return $initError;
        }

        $normalizedPromocodes = $this->normalizePromocodeSlugs($promocodes);
        if ($normalizedPromocodes === null) {
            return 'Invalid promocode format';
        }

        foreach ($normalizedPromocodes as $slug) {
            DiscountCouponsManager::add($slug);
        }

        $basket = $order->getBasket();
        if (!$basket) {
            return 'Failed to apply promocodes: basket is empty';
        }

        $this->setBasketCustomPriceFlag($basket, false);

        if (method_exists($order, 'isStartField')) {
            $order->isStartField(true);
        }

        $wasMathActionOnly = method_exists($order, 'isMathActionOnly') && $order->isMathActionOnly();
        if (method_exists($order, 'setMathActionOnly')) {
            $order->setMathActionOnly(true);
        }

        try {
            if (method_exists($order, 'doFinalAction')) {
                try {
                    $order->doFinalAction(true);
                } catch (\Throwable $e) {
                    $order->doFinalAction();
                }
            }
            elseif (method_exists($order, 'getDiscount')) {
                $discount = $order->getDiscount();
                if ($discount) {
                    $discount->calculate();
                }
            } elseif (class_exists('\Bitrix\Sale\Discount')) {
                $discount = \Bitrix\Sale\Discount::load($order);
                if ($discount) {
                    $discount->calculate();
                }
            }
        } finally {
            if (method_exists($order, 'setMathActionOnly')) {
                $order->setMathActionOnly($wasMathActionOnly);
            }
            if (method_exists($order, 'isStartField')) {
                $order->isStartField(false);
            }
        }

        return null;
    }

    /**
     * @param string[] $promocodes
     * @return string|null
     */
    public function validateAppliedPromocodes(array $promocodes): ?string
    {
        if (empty($promocodes)) {
            return null;
        }

        $normalizedPromocodes = $this->normalizePromocodeSlugs($promocodes);
        if ($normalizedPromocodes === null) {
            return 'Invalid promocode format';
        }

        return $this->formatInvalidPromocodesError($normalizedPromocodes);
    }

    /**
     * @param string[] $requestedSlugs
     * @return array{invalid_promocodes: string[], reason: string}|null
     */
    public function findInvalidPromocodes(array $requestedSlugs): ?array
    {
        $couponsInOrder = $this->getCouponsInOrder();
        if (!is_array($couponsInOrder)) {
            return [
                'invalid_promocodes' => $requestedSlugs,
                'reason' => 'INTERNAL_ERROR',
            ];
        }

        $appliedCouponsUpper = array_change_key_case($couponsInOrder, CASE_UPPER);
        $invalidPromocodes = [];
        $reason = null;

        foreach ($requestedSlugs as $slug) {
            $slug = trim((string)$slug);
            if ($slug === '') {
                continue;
            }

            $failureReason = $this->resolvePromocodeFailureReason($slug, $appliedCouponsUpper);
            if ($failureReason === null) {
                continue;
            }

            $invalidPromocodes[] = $slug;
            if ($reason === null) {
                $reason = $failureReason;
            }
        }

        if (empty($invalidPromocodes)) {
            return null;
        }

        return [
            'invalid_promocodes' => $invalidPromocodes,
            'reason' => $reason ?? 'NOT_APPLICABLE',
        ];
    }

    /**
     * @param string[] $promocodes
     * @return string|null
     */
    private function formatInvalidPromocodesError(array $promocodes): ?string
    {
        $invalid = $this->findInvalidPromocodes($promocodes);
        if ($invalid === null) {
            return null;
        }

        $code = $invalid['invalid_promocodes'][0] ?? '';
        return 'Failed to apply promocode: ' . $code;
    }

    /**
     * @param array<string, array> $appliedCouponsUpper
     */
    private function resolvePromocodeFailureReason(string $slug, array $appliedCouponsUpper): ?string
    {
        $slugUpper = mb_strtoupper(trim($slug));
        if ($slugUpper === '') {
            return 'NOT_FOUND';
        }

        if (!isset($appliedCouponsUpper[$slugUpper])) {
            return 'NOT_FOUND';
        }

        $couponInfo = $appliedCouponsUpper[$slugUpper];
        $status = (int)($couponInfo['STATUS'] ?? 0);

        if ($status === DiscountCouponsManager::STATUS_APPLYED) {
            return null;
        }

        if ($status === DiscountCouponsManager::STATUS_NOT_FOUND) {
            return 'NOT_FOUND';
        }

        if ($status === DiscountCouponsManager::STATUS_NOT_APPLYED) {
            return 'NOT_APPLICABLE';
        }

        if ($status === DiscountCouponsManager::STATUS_FREEZE) {
            return $this->mapCheckCodeToReason((int)($couponInfo['CHECK_CODE'] ?? 0));
        }

        if ($status === DiscountCouponsManager::STATUS_ENTERED) {
            return 'NOT_APPLICABLE';
        }

        return 'NOT_APPLICABLE';
    }

    private function mapCheckCodeToReason(int $checkCode): string
    {
        if (($checkCode & DiscountCouponsManager::COUPON_CHECK_ALREADY_MAX_USED) !== 0) {
            return 'USAGE_LIMIT_EXCEEDED';
        }

        if (
            ($checkCode & DiscountCouponsManager::COUPON_CHECK_RANGE_ACTIVE_TO) !== 0
            || ($checkCode & DiscountCouponsManager::COUPON_CHECK_RANGE_ACTIVE_TO_DISCOUNT) !== 0
        ) {
            return 'EXPIRED';
        }

        if (
            ($checkCode & DiscountCouponsManager::COUPON_CHECK_RANGE_ACTIVE_FROM) !== 0
            || ($checkCode & DiscountCouponsManager::COUPON_CHECK_RANGE_ACTIVE_FROM_DISCOUNT) !== 0
        ) {
            return 'NOT_STARTED';
        }

        if (
            ($checkCode & DiscountCouponsManager::COUPON_CHECK_NO_ACTIVE) !== 0
            || ($checkCode & DiscountCouponsManager::COUPON_CHECK_NO_ACTIVE_DISCOUNT) !== 0
        ) {
            return 'INACTIVE';
        }

        if (($checkCode & DiscountCouponsManager::COUPON_CHECK_NOT_FOUND) !== 0) {
            return 'NOT_FOUND';
        }

        if (($checkCode & DiscountCouponsManager::COUPON_CHECK_BAD_USER_ID) !== 0) {
            return 'NOT_APPLICABLE';
        }

        return 'NOT_AVAILABLE';
    }

    /**
     * Откат использования промокодов при отмене заказа до оплаты.
     *
     * Detach и decrement — в одной транзакции; идемпотентность через b_sale_order_coupons.
     * ID купонов — через ORM-фильтр @ID (без конкатенации IN (...)).
     */
    public function releaseUsage(Order $order): void
    {
        if (!Loader::includeModule('sale')) {
            return;
        }

        if ($order->isPaid()) {
            return;
        }

        $orderId = (int) $order->getId();
        if ($orderId <= 0) {
            return;
        }

        $conn = Application::getConnection();

        $conn->startTransaction();
        try {
            $orderCoupons = OrderCouponsTable::getList([
                'filter' => ['=ORDER_ID' => $orderId],
                'select' => ['COUPON_ID'],
            ])->fetchAll();

            if ($orderCoupons === []) {
                $conn->commitTransaction();
                return;
            }

            $couponIds = [];
            foreach ($orderCoupons as $orderCoupon) {
                $couponId = (int) $orderCoupon['COUPON_ID'];
                if ($couponId > 0) {
                    $couponIds[$couponId] = $couponId;
                }
            }

            Collection::normalizeArrayValuesByInt($couponIds, true);
            $couponIdList = array_values($couponIds);

            if ($couponIdList === []) {
                $conn->commitTransaction();
                return;
            }

            OrderCouponsTable::clearByOrder($orderId);

            $decrementIterator = DiscountCouponTable::getList([
                'filter' => [
                    '@ID' => $couponIdList,
                    '>USE_COUNT' => 0,
                ],
                'select' => ['ID'],
            ]);
            while ($coupon = $decrementIterator->fetch()) {
                DiscountCouponTable::update((int) $coupon['ID'], [
                    'USE_COUNT' => new SqlExpression('?# - 1', 'USE_COUNT'),
                ]);
            }

            $reactivateIterator = DiscountCouponTable::getList([
                'filter' => ['@ID' => $couponIdList],
                'select' => ['ID', 'USE_COUNT', 'MAX_USE'],
            ]);
            while ($coupon = $reactivateIterator->fetch()) {
                $maxUse = (int) $coupon['MAX_USE'];
                $useCount = (int) $coupon['USE_COUNT'];
                if ($maxUse <= 0 || $useCount < $maxUse) {
                    DiscountCouponTable::update((int) $coupon['ID'], ['ACTIVE' => 'Y']);
                }
            }

            $conn->commitTransaction();
        } catch (\Throwable $e) {
            $conn->rollbackTransaction();
            Application::getInstance()->getExceptionHandler()->writeToLog($e);
        }
    }

    /**
     * @return string|null
     */
    public function buildBasketFromRequestItems(BasketBase $basket, array $items, string $siteId): ?string
    {
        foreach ($items as $item) {
            $productId = ProductIdResolver::resolveToInternalId($item['id']);
            if ($productId === null) {
                return 'Product not found: ' . $item['id'];
            }

            $quantity = isset($item['quantity']) && (string)$item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            if ($quantity < 1) {
                $quantity = 1;
            }

            $product = \Bitrix\Iblock\ElementTable::getById($productId)->fetch();
            if (!$product) {
                return 'Product not found: ' . $item['id'];
            }

            $basketItem = $basket->createItem('catalog', $productId);
            $fields = [
                'QUANTITY' => $quantity,
                'CURRENCY' => 'RUB',
                'LID' => $siteId,
                'NAME' => $product['NAME'],
            ];

            if (class_exists('\\Bitrix\\Catalog\\Product\\CatalogProvider')) {
                $fields['PRODUCT_PROVIDER_CLASS'] = '\\Bitrix\\Catalog\\Product\\CatalogProvider';
            }

            $basketItem->setFields($fields);
        }

        $refreshResult = $basket->refresh();
        if (!$refreshResult->isSuccess()) {
            return 'Failed to refresh basket: ' . implode(', ', $refreshResult->getErrorMessages());
        }

        return null;
    }

    /**
     * @return array<int, array{id: string, final_price: float, regular_price: float, quantity: int}>
     */
    public function extractBasketItemPrices(Basket $basket): array
    {
        $items = [];

        foreach ($basket as $basketItem) {
            $productId = (int)$basketItem->getProductId();
            if ($productId <= 0) {
                continue;
            }

            $externalId = ProductIdResolver::getExternalId($productId);
            if ($externalId === '') {
                continue;
            }

            $items[] = [
                'id' => $externalId,
                'regular_price' => (float)$basketItem->getBasePrice(),
                'final_price' => (float)$basketItem->getPrice(),
                'quantity' => (int)$basketItem->getQuantity(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, array>
     */
    private function getCouponsInOrder(): array
    {
        $coupons = DiscountCouponsManager::get(true);
        return is_array($coupons) ? $coupons : [];
    }

    private function setBasketCustomPriceFlag(Basket $basket, bool $isCustom): void
    {
        $flag = $isCustom ? 'Y' : 'N';

        foreach ($basket as $basketItem) {
            if ($basketItem->getField('CUSTOM_PRICE') !== $flag) {
                $basketItem->setField('CUSTOM_PRICE', $flag);
            }
        }
    }
}
