<?php

namespace Darneo\Ozon\Order\Helper;

use Bitrix\Main\Localization\Loc;

class Fbo
{
    public static function isStatusNew(string $statusCode): bool
    {
        return $statusCode === 'awaiting_deliver';
    }

    public static function isStatusError(string $statusCode): bool
    {
        return $statusCode === 'cancelled';
    }

    public static function isStatusFinish(string $statusCode): bool
    {
        return $statusCode === 'delivered';
    }

    public static function getStatusLoc(string $statusCode): string
    {
        return match ($statusCode) {
            'awaiting_packaging' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_PACKAGING'),
            'awaiting_deliver' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_AWAITING_DELIVER'),
            'delivering' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERING'),
            'delivered' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_DELIVERED'),
            'cancelled' => Loc::getMessage('DARNEO_OZON_ORDER_HELPER_FBS_STATUS_CANCELLED'),
            default => $statusCode,
        };
    }
}