<?php
namespace Yandex\Market\Checkout\Services;

use Bitrix\Sale\Basket;
use Yandex\Market\Checkout\ProductIdResolver;

class BasketPriceValidator
{
    private const PRICE_EPSILON = 0.01;

    /**
     * @return array<int, array{id: string, regular_price: float, final_price: float}>|null
     */
    public function capturePriceExpectationsFromItems(array $items): ?array
    {
        $expectations = [];

        foreach ($items as $item) {
            if (empty($item['id'])) {
                return null;
            }

            $requestedPrices = $this->getRequestedItemPrices($item);
            if ($requestedPrices === null) {
                return null;
            }

            $expectations[] = [
                'id' => (string)$item['id'],
                'regular_price' => $requestedPrices['regular_price'],
                'final_price' => $requestedPrices['final_price'],
            ];
        }

        return $expectations;
    }

    /**
     * @return array{items: array<int, array{id: string, regular_price: float, final_price: float}>}
     */
    public function findPriceConflicts(Basket $basket, array $priceExpectations): array
    {
        $conflicts = [];

        $position = 0;
        foreach ($basket as $basketItem) {
            $expected = $priceExpectations[$position] ?? null;
            $position++;

            if (!$this->hasBasketPriceConflict($basketItem, $expected)) {
                continue;
            }

            $productId = (int)$basketItem->getProductId();
            if ($productId <= 0) {
                continue;
            }

            $basketPrices = $this->getBasketItemPrices($basketItem);
            $conflicts[] = [
                'id' => ProductIdResolver::getExternalId($productId),
                'regular_price' => $basketPrices['regular_price'],
                'final_price' => $basketPrices['final_price'],
            ];
        }

        return ['items' => $conflicts];
    }

    public function hasBasketItemCountMismatch($basket, array $priceExpectations): bool
    {
        $basketItemCount = 0;
        foreach ($basket as $basketItem) {
            $basketItemCount++;
        }

        return $basketItemCount !== count($priceExpectations);
    }

    public function hasBasketPriceConflict($basketItem, ?array $expected): bool
    {
        $productId = (int)$basketItem->getProductId();
        if ($productId <= 0) {
            return false;
        }

        if ($expected === null || empty($expected['id'])) {
            return true;
        }

        $expectedProductId = ProductIdResolver::resolveToInternalId($expected['id']);
        if ($expectedProductId === null || (int)$expectedProductId !== $productId) {
            return true;
        }

        $basketPrices = $this->getBasketItemPrices($basketItem);

        if ($this->isPriceDifferent($basketPrices['regular_price'], (float)$expected['regular_price'])) {
            return true;
        }

        if ($this->isPriceDifferent($basketPrices['final_price'], (float)$expected['final_price'])) {
            return true;
        }

        return false;
    }

    /**
     * @return array{regular_price: float, final_price: float}
     */
    public function getBasketItemPrices($basketItem): array
    {
        return [
            'regular_price' => (float)$basketItem->getBasePrice(),
            'final_price' => (float)$basketItem->getPrice(),
        ];
    }

    /**
     * @return array{regular_price: float, final_price: float}|null
     */
    private function getRequestedItemPrices(array $item): ?array
    {
        if (!isset($item['price']) || !is_numeric($item['price'])) {
            return null;
        }

        if (!isset($item['final_price']) || !is_numeric($item['final_price'])) {
            return null;
        }

        $regularPrice = (float)$item['price'];
        $finalPrice = (float)$item['final_price'];

        if ($finalPrice > $regularPrice + self::PRICE_EPSILON) {
            return null;
        }

        return [
            'regular_price' => $regularPrice,
            'final_price' => $finalPrice,
        ];
    }

    private function isPriceDifferent($actualPrice, $requestedPrice): bool
    {
        return $this->isPriceDifferentLegacy($actualPrice, $requestedPrice);
        
        /**
         * TODO
         * Возможность проверки отличия цены на PRICE_EPSILON временно заблокирована, 
         * так как гейт Яндекса округляет цены до целого числа
         */
        //return abs((float)$actualPrice - (float)$requestedPrice) > self::PRICE_EPSILON;
    }
    
    private function isPriceDifferentLegacy(float $actualPrice, float $requestedPrice): bool {
        return ceil($actualPrice) !== ceil($requestedPrice);
    }
}
