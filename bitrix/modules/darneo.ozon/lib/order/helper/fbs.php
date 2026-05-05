<?php

namespace Darneo\Ozon\Order\Helper;

use Bitrix\Main\Localization\Loc;

class Fbs
{
    public static function isStatusNew(string $statusCode): bool
    {
        return in_array($statusCode, ['awaiting_registration', 'awaiting_deliver'], true);
    }

    public static function isStatusError(string $statusCode): bool
    {
        return in_array($statusCode, ['cancelled', 'arbitration', 'client_arbitration', 'not_accepted'], true);
    }

    public static function isStatusFinish(string $statusCode): bool
    {
        return $statusCode === 'delivered';
    }

    public static function getStatusLoc(string $statusCode): string
    {
        return match ($statusCode) {
            'awaiting_registration' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_REGISTRATION'),
            'acceptance_in_progress' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_ACCEPTANCE_IN_PROGRESS'),
            'awaiting_approve' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_APPROVE'),
            'awaiting_packaging' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_PACKAGING'),
            'awaiting_deliver' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_DELIVER'),
            'arbitration' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_ARBITRATION'),
            'client_arbitration' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CLIENT_ARBITRATION'),
            'delivering' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERING'),
            'driver_pickup' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DRIVER_PICKUP'),
            'delivered' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERED'),
            'cancelled' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CANCELLED'),
            'not_accepted' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_NOT_ACCEPTED'),
            'sent_by_seller' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_SENT_BY_SELLER'),
            default => $statusCode,
        };
    }
}