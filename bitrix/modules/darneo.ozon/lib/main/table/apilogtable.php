<?php

namespace Darneo\Ozon\Main\Table;

use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type;
use Darneo\Ozon\EventHandlers;

class ApiLogTable extends Data\DataManager
{
    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                ]
            ),
            new Fields\StringField(
                'CLIENT_ID',
                [
                    'required' => true,
                ]
            ),
            new Fields\StringField(
                'KEY',
                [
                    'required' => true,
                ]
            ),
            new Fields\StringField(
                'URL',
                [
                    'required' => true,
                ]
            ),
            new Fields\StringField(
                'METHOD_TRACKER',
                [
                    'required' => false,
                ]
            ),
            new Fields\TextField(
                'DATA_SEND',
                [
                    'required' => false,
                ]
            ),
            new Fields\TextField(
                'DATA_RECEIVED',
                [
                    'required' => false,
                ]
            ),
        ];
    }

    public static function clearTable(): void
    {
        global $DB;

        $strSql = 'TRUNCATE TABLE ' . self::getTableName();
        $DB->Query($strSql);
    }

    public static function getTableName(): string
    {
        return 'darneo_ozon_main_api_log';
    }

    public static function getTotalRecordsCount(): int
    {
        global $DB;

        $strSql = 'SELECT COUNT(*) AS total FROM ' . self::getTableName();
        $result = $DB->Query($strSql);
        if ($row = $result->Fetch()) {
            return $row['total'] ?: 0;
        }

        return 0;
    }
}
