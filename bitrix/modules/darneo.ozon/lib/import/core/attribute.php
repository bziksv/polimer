<?php

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Api;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;
use Darneo\Ozon\Import\Table\PropertyGroupTable;
use Darneo\Ozon\Import\Table\PropertyListTable;

class Attribute extends Base
{
    public function start(string $categoryId = '', string $typeId = ''): void
    {
        $level3 = $this->getLevel3($categoryId, $typeId);
        foreach ($level3 as $level) {
            $data = (new Api\v1\DescriptionCategory())->attribute($level['CATEGORY_ID'], $level['TYPE_ID']);
            if (!$data['result']) {
                $this->errors[] = Loc::getMessage(
                    'DARNEO_OZON_IMPORT_CORE_ATTR_ERROR_IMPORT',
                    [
                        '#CATEGORY_ID#' => $level['CATEGORY_ID'],
                        '#TYPE_ID#' => $level['TYPE_ID'],
                        '#ANSWER#' => Json::encode($data),
                    ]
                );
                continue;
            }

            foreach ($data['result'] as $prop) {
                $propId = $this->addCategoryAttribute($prop);
                if ($propId) {
                    $groupId = 0;
                    if ($prop['group_id'] > 0) {
                        $groupId = $this->addGroup($prop['group_id'], $prop['group_name']);
                    }
                    $this->addConnectionAttribute($propId, $level['CATEGORY_ID'], $level['TYPE_ID'], $groupId);
                }
            }
        }
    }

    private function getLevel3(string $categoryId = '', string $typeId = ''): array
    {
        $rows = [];

        $parameters = [
            'select' => ['CATEGORY_ID', 'TYPE_ID'],
        ];

        if ($categoryId) {
            $parameters['filter']['CATEGORY_ID'] = $categoryId;
        }

        if ($typeId) {
            $parameters['filter']['TYPE_ID'] = $typeId;
        }

        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function addCategoryAttribute(array $prop): int
    {
        $result = PropertyListTable::add(
            [
                'ID' => $prop['id'],
                'NAME' => $prop['name'],
                'TYPE' => $prop['type'],
                'DICTIONARY_ID' => $prop['dictionary_id'],
                'DESCRIPTION' => $prop['description'],
                'IS_COLLECTION' => $prop['is_collection'],
                'IS_REQUIRED' => $prop['is_required'],
            ]
        );
        if ($result->isSuccess()) {
            return $result->getId();
        }

        $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        return 0;
    }

    private function addGroup(int $groupId, string $name): int
    {
        $result = PropertyGroupTable::add(
            [
                'ID' => $groupId,
                'NAME' => $name,
            ]
        );
        if ($result->isSuccess()) {
            return $result->getId();
        }

        $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        return 0;
    }

    private function addConnectionAttribute(int $propId, string $categoryId, string $typeId, int $groupId): void
    {
        $params = [
            'PROPERTY_ID' => $propId,
            'CATEGORY_ID' => $categoryId,
            'TYPE_ID' => $typeId
        ];
        if ($groupId) {
            $params['GROUP_ID'] = $groupId;
        }

        $result = ConnectionPropCategoryTable::add($params);
        if (!$result->isSuccess()) {
            $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
        }
    }
}
