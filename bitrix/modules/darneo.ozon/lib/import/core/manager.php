<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\ConnectionPropValueTable;
use Darneo\Ozon\Import\Table\PropertyGroupTable;
use Darneo\Ozon\Import\Table\PropertyListTable;
use Darneo\Ozon\Import\Table\PropertyValueTable;
use Darneo\Ozon\Import\Table\TreeBindCategoryTypeTable;
use Darneo\Ozon\Import\Table\TreeCategoryTable;
use Darneo\Ozon\Import\Table\TreeTypeTable;

class Manager extends Base
{
    public function __construct()
    {
        $tables = [
            ConnectionPropCategoryTable::class,
            ConnectionPropValueTable::class,
            PropertyGroupTable::class,
            PropertyListTable::class,
            PropertyValueTable::class,
            TreeCategoryTable::class,
            TreeTypeTable::class,
            TreeBindCategoryTypeTable::class,
        ];

        $tableConnectionAll = $this->getTablePrefixAll();
        $typeIds = $this->getConnectionSectionTree();

        foreach ($tableConnectionAll as $tableConnectionId) {
            // TODO есть вероятность затереть связи другого ключа (если используются несколько)
            //if (!in_array($tableConnectionId, $typeIds, true)) {
            ConnectionPropValueTable::deleteTable($tableConnectionId);
            //}
        }

        $connection = Application::getConnection();
        /** @var DataManager $table */
        foreach ($tables as $table) {
            if ($connection->isTableExists($table::getTableName())) {
                $connection->dropTable($table::getTableName());
            }
            $table::getEntity()->createDbTable();
        }
    }

    private function getTablePrefixAll(): array
    {
        $rows = [];

        $tableFindName = 'darneo_ozon_data_connection_prop_value_';

        global $DB;
        $strSql = "SHOW TABLES LIKE '" . $tableFindName . "%'";
        $errMess = '';
        $res = $DB->Query($strSql, false, $errMess . __LINE__);
        while ($row = $res->Fetch()) {
            foreach ($row as $tableName) {
                $tablePrefixId = str_replace($tableFindName, '', $tableName);
                $rows[] = (string)$tablePrefixId;
            }
        }

        return $rows;
    }

    private function getConnectionSectionTree(): array
    {
        $rows = [];

        $result = ConnectionSectionTreeTable::getList(
            [
                'select' => ['TYPE_ID'],
            ]
        );
        while ($row = $result->fetch()) {
            $rows[] = (string)$row['TYPE_ID'];
        }

        return $rows;
    }

    public function start(): void
    {
        (new Category())->start();
        (new Attribute())->start();
        (new AttributeValue())->start();
    }
}
