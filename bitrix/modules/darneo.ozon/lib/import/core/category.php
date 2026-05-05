<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api;
use Darneo\Ozon\Import\Table\TreeBindCategoryTypeTable;
use Darneo\Ozon\Import\Table\TreeCategoryTable;
use Darneo\Ozon\Import\Table\TreeTypeTable;

class Category extends Base
{
    public function start(): void
    {
        $data = (new Api\v1\DescriptionCategory())->tree();
        if (!$data['result']) {
            $this->errors[] = Loc::getMessage(
                'DARNEO_OZON_IMPORT_CORE_CATEGORY_ERROR_IMPORT',
                [
                    '#ANSWER#' => Json::encode($data),
                ]
            );
            return;
        }

        $this->getGroupsRecursive($data['result']);
    }

    private function getGroupsRecursive(array $row, int $parentId = 0): void
    {
        foreach ($row as $item) {
            if ($item['type_id']) {
                if (!TreeTypeTable::getById($item['type_id'])->fetch()) {
                    $result = TreeTypeTable::add(
                        [
                            'TYPE_ID' => $item['type_id'],
                            'TYPE_NAME' => $item['type_name'],
                            'TREE_SECTION_ID' => $parentId,
                            'DISABLED' => $item['disabled'],
                        ]
                    );
                    if (!$result->isSuccess()) {
                        $this->setLog(implode(', ', $result->getErrorMessages()));
                    }
                }

                $result = TreeBindCategoryTypeTable::add(
                    [
                        'CATEGORY_ID' => $parentId,
                        'TYPE_ID' => $item['type_id'],
                    ]
                );
                if (!$result->isSuccess()) {
                    $this->setLog(implode(', ', $result->getErrorMessages()));
                }
            } else {
                $result = TreeCategoryTable::add(
                    [
                        'CATEGORY_ID' => $item['description_category_id'],
                        'CATEGORY_NAME' => $item['category_name'],
                        'CATEGORY_PARENT_ID' => $parentId,
                        'DISABLED' => $item['disabled'],
                    ]
                );
                if (!$result->isSuccess()) {
                    $this->setLog(implode(', ', $result->getErrorMessages()));
                }
            }

            if ($item['children'] && $item['description_category_id']) {
                $this->getGroupsRecursive($item['children'], $item['description_category_id']);
            }
        }
    }
}
