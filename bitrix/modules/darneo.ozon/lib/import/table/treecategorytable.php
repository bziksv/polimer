<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class TreeCategoryTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_tree_category_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_tree_category';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_tree_category_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\StringField('CATEGORY_ID', ['primary' => true]),
            new Fields\StringField('CATEGORY_NAME'),
            new Fields\StringField('CATEGORY_PARENT_ID'),
            new Fields\BooleanField('DISABLED'),
        ];
    }
}
