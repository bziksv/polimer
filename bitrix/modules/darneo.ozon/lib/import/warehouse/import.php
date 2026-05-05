<?php

namespace Darneo\Ozon\Import\Warehouse;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DataManager;
use Darneo\Ozon\Api\v1\Warehouse;
use Darneo\Ozon\Import\Table\StockTable;

class Import
{
    private array $errors = [];

    public function initData(): bool
    {
        $import = new Warehouse();
        $data = $import->list();
        if ($data['result']) {
            foreach ($data['result'] as $datum) {
                if ($warehouse = StockTable::getById($datum['warehouse_id'])->fetch()) {
                    $result = StockTable::update(
                        $warehouse['ID'],
                        [
                            'NAME' => $datum['name'],
                            'IS_RFBS' => $datum['is_rfbs']
                        ]
                    );
                } else {
                    $result = StockTable::add(
                        [
                            'ID' => $datum['warehouse_id'],
                            'NAME' => $datum['name'],
                            'IS_RFBS' => $datum['is_rfbs']
                        ]
                    );
                }
                if (!$result->isSuccess()) {
                    $this->errors[] = $result->getErrorMessages();
                }
            }
            $this->errors = array_merge(...$this->errors);
            return true;
        }

        if ($data['message']) {
            $this->errors[] = $data['message'];
        }

        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDataCount(): int
    {
        return StockTable::getCount();
    }
}
