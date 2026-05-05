<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class TreeTypeTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_tree_type_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_tree_type';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_tree_type_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('TYPE_ID', ['primary' => true]),
            new Fields\StringField('TYPE_NAME'),
            new Fields\StringField('TREE_SECTION_ID'),
            new Fields\BooleanField('DISABLED'),
        ];
    }
}
