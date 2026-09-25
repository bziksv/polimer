<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Yandex\Market\Checkout\Services\BasketItemsGrouper;
use Yandex\Market\Checkout\Services\BasketPriceValidator;
use Yandex\Market\Checkout\Services\PromocodeCalculator;

class ApplyPromocodesHandler extends BaseHandler
{
    public function handle($orderId = null)
    {
        if ($unauthorized = $this->checkAuthorization()) {
            return $unauthorized;
        }

        try {
            if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
                return $this->error('Required modules not available', 500);
            }

            $input = file_get_contents('php://input');
            $requestData = Json::decode($input);

            if (empty($requestData['items']) || !is_array($requestData['items'])) {
                return $this->error('Invalid request format: items array is required', 400);
            }

            if (!array_key_exists('promocodes', $requestData) || !is_array($requestData['promocodes'])) {
                return $this->error('Invalid request format: promocodes array is required', 400);
            }

            if (empty($requestData['promocodes'])) {
                return $this->error('Invalid request format: promocodes must not be empty', 400);
            }

            $userId = (int)Option::get($this->moduleId, 'YASTORE_USER_ID', 0);
            if ($userId <= 0) {
                return $this->error('Service user not configured', 500);
            }

            $originalItems = array_values($requestData['items']);
            $grouper = new BasketItemsGrouper();
            $grouping = $grouper->group($originalItems);
            if ($grouping === null) {
                return $this->error('Invalid item price format', 400, self::ERROR_INVALID_INPUT);
            }

            $groupedItems = $grouping['items'];
            $priceValidator = new BasketPriceValidator();
            $priceExpectations = $priceValidator->capturePriceExpectationsFromItems($groupedItems);
            if ($priceExpectations === null) {
                return $this->error('Invalid item price format', 400, self::ERROR_INVALID_INPUT);
            }

            $calculator = new PromocodeCalculator();
            $siteId = Context::getCurrent()->getSite();
            $result = $calculator->calculateApplyPrices(
                $groupedItems,
                $requestData['promocodes'],
                $userId,
                $siteId,
                $priceExpectations
            );

            if (!empty($result['price_conflicts']['items'])) {
                return $this->errorWithData(
                    'Prices have changed',
                    409,
                    self::ERROR_INVENTORY_CONFLICT,
                    ['actual_inventory' => $result['price_conflicts']]
                );
            }

            if ($result['promocode_validation'] !== null) {
                return $this->promocodeValidationError(
                    $result['promocode_validation']['invalid_promocodes'],
                    $result['promocode_validation']['reason']
                );
            }

            if ($result['error'] !== null) {
                $error = $result['error'];
                if (strpos($error, 'Product not found') === 0) {
                    return $this->error($error, 404, self::ERROR_PRODUCT_NOT_FOUND);
                }

                if ($error === 'Invalid promocode format') {
                    return $this->error('Invalid request format: invalid promocode format', 400);
                }

                return $this->error($error, 500);
            }

            if (count($result['items']) !== count($groupedItems)) {
                return $this->error('Failed to apply promocodes: item count mismatch', 500);
            }

            $responseItems = $grouper->expandFinalPrices(
                $originalItems,
                $grouping['group_index_by_item_index'],
                $result['items']
            );
            if ($responseItems === null) {
                return $this->error('Failed to apply promocodes: item mapping mismatch', 500);
            }

            return $this->response(['items' => $responseItems]);
        } catch (\Exception $e) {
            return $this->error('Failed to apply promocodes: ' . $e->getMessage(), 500);
        }
    }
}
