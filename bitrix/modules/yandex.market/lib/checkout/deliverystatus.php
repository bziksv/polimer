<?php

namespace Yandex\Market\Checkout;

/**
 * Допустимые значения delivery.status, приходящие в эндпоинт доставки,
 * и их маппинг на укрупнённые статусы заказа.
 */
class DeliveryStatus
{
    const NEW = 'NEW';
    const IN_PROGRESS = 'IN_PROGRESS';
    const DELIVERED = 'DELIVERED';
    const RETURNED = 'RETURNED';
    const CANCELLED = 'CANCELLED';

    // Укрупнённые статусы заказа, в которые маппятся значения delivery.status.
    const ORDER_CREATED = 'Создана';
    const ORDER_IN_PROGRESS = 'В пути';
    const ORDER_DELIVERED = 'Доставлена';
    const ORDER_RETURNED = 'Возврат';
    const ORDER_CANCELLED = 'Отменена';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::NEW,
            self::IN_PROGRESS,
            self::DELIVERED,
            self::RETURNED,
            self::CANCELLED,
        ];
    }

    public static function isValid($value): bool
    {
        return is_string($value) && in_array($value, self::all(), true);
    }

    /**
     * Маппинг значения delivery.status на укрупнённый статус заказа.
     *
     * @return string|null укрупнённый статус или null, если значение неизвестно
     */
    public static function toOrderStatus($value)
    {
        $map = [
            self::NEW => self::ORDER_CREATED,
            self::IN_PROGRESS => self::ORDER_IN_PROGRESS,
            self::DELIVERED => self::ORDER_DELIVERED,
            self::RETURNED => self::ORDER_RETURNED,
            self::CANCELLED => self::ORDER_CANCELLED,
        ];

        return $map[$value] ?? null;
    }
}
