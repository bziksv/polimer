<?php
namespace Yandex\Market\Checkout;

/**
 * Допустимые значения события доставки, приходящего в эндпоинт обновления доставки.
 */
class DeliveryEvent
{
    const ORDER_CREATED = 'ORDER_CREATED';
    const HANDED_TO_DELIVERY = 'HANDED_TO_DELIVERY';
    const DELIVERY_STATUS_UPDATED = 'DELIVERY_STATUS_UPDATED';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::ORDER_CREATED,
            self::HANDED_TO_DELIVERY,
            self::DELIVERY_STATUS_UPDATED,
        ];
    }

    public static function isValid($value): bool
    {
        return is_string($value) && in_array($value, self::all(), true);
    }
}
