<?php

namespace Darneo\Ozon\Import\Product;

use Darneo\Ozon\Import\Table\ProductListTable;

class Manager extends Base
{
    public function __construct()
    {
        $this->reinstallTable();
    }

    private function reinstallTable(): void
    {
        $result = ProductListTable::getList();
        while ($row = $result->fetch()) {
            ProductListTable::delete($row['ID']);
        }
    }

    public function start(): void
    {
        (new Connect())->start();
        (new Product())->start();
    }
}
