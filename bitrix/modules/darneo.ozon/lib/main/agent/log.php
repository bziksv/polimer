<?php

namespace Darneo\Ozon\Main\Agent;

use Bitrix\Main\Type\DateTime;
use Darneo\Ozon\Export\Table\PriceLogTable;
use Darneo\Ozon\Export\Table\ProductLogTable;
use Darneo\Ozon\Export\Table\StockLogTable;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Main\Table\ApiLogTable;

class Log
{
    public static function delete(): string
    {
        $day = HelperSettings::getLogRetentionDays();
        $dateTo = (new DateTime())->add('-' . $day . ' day');

        self::deleteLogApi($dateTo);
        self::deleteLogProduct($dateTo);
        self::deleteLogPrice($dateTo);
        self::deleteLogStock($dateTo);

        return '\Darneo\Ozon\Main\Agent\Log::delete();';
    }

    protected static function deleteLogApi(DateTime $dateTo): void
    {
        $parameters = [
            'filter' => [
                '<DATE_CREATED' => $dateTo
            ],
            'select' => ['ID'],
        ];
        $result = ApiLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            ApiLogTable::delete($row['ID']);
        }
    }

    protected static function deleteLogProduct(DateTime $dateTo): void
    {
        $parameters = [
            'filter' => [
                '<DATE_CREATED' => $dateTo
            ],
            'select' => ['ID'],
        ];
        $result = ProductLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            ProductLogTable::delete($row['ID']);
        }
    }

    protected static function deleteLogPrice(DateTime $dateTo): void
    {
        $parameters = [
            'filter' => [
                '<DATE_CREATED' => $dateTo
            ],
            'select' => ['ID'],
        ];
        $result = PriceLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            PriceLogTable::delete($row['ID']);
        }
    }

    protected static function deleteLogStock(DateTime $dateTo): void
    {
        $parameters = [
            'filter' => [
                '<DATE_CREATED' => $dateTo
            ],
            'select' => ['ID'],
        ];
        $result = StockLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            StockLogTable::delete($row['ID']);
        }
    }
}
