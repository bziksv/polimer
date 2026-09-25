<?php
namespace Yandex\Market\Checkout\Services;

use Yandex\Market\Checkout\ProductIdResolver;

class BasketItemsGrouper
{
    /**
     * @return array{items: array, group_index_by_item_index: int[], required_quantity_by_product_id: array<int, int>}|null
     */
    public function group(array $items): ?array
    {
        $groupedItems = [];
        $indexByKey = [];
        $groupIndexByItemIndex = [];
        $requiredQuantityByProductId = [];

        foreach ($items as $item) {
            if (empty($item['id'])) {
                return null;
            }

            $regularPrice = $this->normalizeMoneyToKopecks($item['price'] ?? null);
            $finalPrice = $this->normalizeMoneyToKopecks($item['final_price'] ?? null);
            if ($regularPrice === null || $finalPrice === null) {
                return null;
            }

            $externalId = (string)$item['id'];
            $internalId = ProductIdResolver::resolveToInternalId($externalId);
            $productIdentity = $internalId !== null
                ? 'pid:' . $internalId
                : 'external:' . $externalId;
            $key = json_encode([$productIdentity, $regularPrice, $finalPrice]);

            $quantity = isset($item['quantity']) && (string)$item['quantity'] !== ''
                ? (int)$item['quantity']
                : 1;
            if ($quantity < 1) {
                $quantity = 1;
            }

            if ($internalId !== null) {
                $requiredQuantityByProductId[$internalId] =
                    ($requiredQuantityByProductId[$internalId] ?? 0) + $quantity;
            }

            if (!isset($indexByKey[$key])) {
                $groupIndex = count($groupedItems);
                $indexByKey[$key] = $groupIndex;
                $groupedItem = $item;
                $groupedItem['quantity'] = 0;
                $groupedItems[] = $groupedItem;
            } else {
                $groupIndex = $indexByKey[$key];
            }

            $groupedItems[$groupIndex]['quantity'] += $quantity;
            $groupIndexByItemIndex[] = $groupIndex;
        }

        return [
            'items' => $groupedItems,
            'group_index_by_item_index' => $groupIndexByItemIndex,
            'required_quantity_by_product_id' => $requiredQuantityByProductId,
        ];
    }

    /**
     * @return array<int, array{id: string, final_price: mixed}>|null
     */
    public function expandFinalPrices(
        array $originalItems,
        array $groupIndexByItemIndex,
        array $groupedItems
    ): ?array {
        $originalItems = array_values($originalItems);
        if (count($originalItems) !== count($groupIndexByItemIndex)) {
            return null;
        }

        $result = [];
        foreach ($originalItems as $index => $originalItem) {
            $groupIndex = $groupIndexByItemIndex[$index] ?? null;
            if (
                $groupIndex === null
                || !isset($groupedItems[$groupIndex])
                || !array_key_exists('final_price', $groupedItems[$groupIndex])
            ) {
                return null;
            }

            $result[] = [
                'id' => (string)($originalItem['id'] ?? ''),
                'final_price' => $groupedItems[$groupIndex]['final_price'],
            ];
        }

        return $result;
    }

    /**
     * @param array<int, int|float|string> $currentQuantities
     * @return array{quantities: int[], excess: int}
     */
    public function allocateQuantityAcrossRows(array $currentQuantities, int $requestedQuantity): array
    {
        $remaining = max(0, $requestedQuantity);
        $allocated = [];

        foreach ($currentQuantities as $currentQuantity) {
            $currentQuantity = max(0, (int)$currentQuantity);
            $rowQuantity = min($currentQuantity, $remaining);
            $allocated[] = $rowQuantity;
            $remaining -= $rowQuantity;
        }

        return [
            'quantities' => $allocated,
            'excess' => $remaining,
        ];
    }

    private function normalizeMoneyToKopecks($value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int)round((float)$value * 100, 0, PHP_ROUND_HALF_UP);
    }
}
