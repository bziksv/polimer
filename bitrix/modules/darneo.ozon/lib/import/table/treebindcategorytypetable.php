<?php

namespace Darneo\Ozon\Import\Table;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;

class TreeBindCategoryTypeTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_data_tree_bind_' . self::$tablePrefix;
        }
        return 'darneo_ozon_data_tree_bind';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_data_tree_bind_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
            new Fields\StringField('CATEGORY_ID'),
            new Fields\StringField('TYPE_ID'),
            new Fields\Relations\Reference(
                'TYPE',
                TreeTypeTable::class,
                ['=this.TYPE_ID' => 'ref.TYPE_ID'],
                ['join_type' => 'left']
            )
        ];
    }
}
