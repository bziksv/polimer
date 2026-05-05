<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Import\Table\TreeBindCategoryTypeTable;
use Darneo\Ozon\Import\Table\TreeCategoryTable;
use Darneo\Ozon\Import\Table\TreeTypeTable;

class OzonExportProductSectionComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private int $iblockId = 0;
    private string $level1;
    private string $level2;

    private array $sectionCategory = [];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                case 'tree':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'setCategory':
                    $sectionId = $this->request['sectionId'] ?: 0;
                    $level2 = $this->request['level2'];
                    $level3 = $this->request['level3'];
                    $status = $this->setCategory($level2, $level3, $sectionId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                case 'deleteCategory':
                    $sectionId = $this->request['sectionId'] ?: 0;
                    $status = $this->deleteCategory($sectionId);
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                default:
                    $this->setTemplateData();
                    $this->includeComponentTemplate();
            }
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }

        return $result;
    }

    private function loadModules(): void
    {
        foreach (self::$moduleNames as $moduleName) {
            $moduleLoaded = Loader::includeModule($moduleName);
            if (!$moduleLoaded) {
                throw new LoaderException(
                    Loc::getMessage('DARNEO_OZON_MODULE_LOAD_ERROR', ['#MODULE_NAME#' => $moduleName])
                );
            }
        }
        if (!\Darneo\Ozon\Main\Helper\Access::isPermission()) {
            throw new LoaderException('Access is not allowed');
        }
    }

    private function dataManager(): void
    {
        $this->iblockId = $this->getIblockId($this->arParams['ELEMENT_ID']);
        $this->level1 = $this->request['level1'] ?: '';
        $this->level2 = $this->request['level2'] ?: '';
    }

    private function getIblockId($elementId): int
    {
        $parameters = [
            'filter' => [
                'ID' => $elementId
            ],
            'select' => ['IBLOCK_ID'],
            'cache' => ['ttl' => 86400]
        ];
        $result = ProductListTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['IBLOCK_ID'] ?: 0;
        }

        return 0;
    }

    private function setTemplateData(): void
    {
        $this->sectionCategory = $this->getSectionCategory();

        $sections = $this->getIblockSection();

        // bind tree
        $sectionTree['ROOT'] = [];
        $tmpSection[0] = &$sectionTree['ROOT'];
        foreach ($sections as $section) {
            $tmpSection[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']] = $section;
            $tmpSection[$section['ID']] = &$tmpSection[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']];
        }
        unset($tmpSection);

        // unset key
        $sectionTree = $this->arrayValuesRecursive($sectionTree['ROOT']) ?: [];
        if ($this->iblockId) {
            $sectionTree['NAME'] = Loc::getMessage('DARNEO_OZON_MODULE_PRODUCT_SECTION_IBLOCK');
            $sectionTree['IBLOCK_ID'] = $this->iblockId;
            $sectionTree['ID'] = 0;
            $sectionTree['CATEGORY'] = $this->sectionCategory[0]['CATEGORY_NAME'] ?: '';
        }

        $level1 = $this->getCategoryMain(['CATEGORY_PARENT_ID' => 0]);
        $level2 = $this->level1 ? $this->getCategoryMain(['CATEGORY_PARENT_ID' => $this->level1]) : [];
        $level3 = $this->level2 ? $this->getTypes($this->level2) : [];

        $this->arResult['DATA_VUE'] = [
            'SECTION' => $sectionTree,
            'TREE' => [
                'LEVEL_1' => $level1,
                'LEVEL_2' => $level2,
                'LEVEL_3' => $level3,
                'SELECTED' => [
                    'LEVEL_1' => $this->level1,
                    'LEVEL_2' => $this->level2
                ],
            ],
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['PATH_TO_AJAX_IMPORT'] = $this->getPath() . '/ajax_import.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.section'
        );
    }

    private function getSectionCategory(): array
    {
        if (!$this->iblockId) {
            return [];
        }
        $categoryIds = $this->getConnectionSectionTree();
        if (!$categoryIds) {
            return [];
        }
        $categoryName = $this->getTypeName($categoryIds);
        $data = [];
        foreach ($categoryIds as $sectionId => $categoryId) {
            $data[$sectionId] = [
                'CATEGORY_ID' => $categoryId,
                'CATEGORY_NAME' => $categoryName[$categoryId],
            ];
        }

        return $data;
    }

    private function getConnectionSectionTree(): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['IBLOCK_ID' => $this->iblockId],
            'select' => ['ID', 'SECTION_ID', 'CATEGORY_ID', 'TYPE_ID'],

        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['SECTION_ID']] = $row['TYPE_ID'];
        }

        return $rows;
    }

    private function getTypeName(array $ids): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['TYPE_ID' => $ids],
            'select' => ['TYPE_ID', 'TYPE_NAME'],

        ];
        $result = TreeTypeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[$row['TYPE_ID']] = $row['TYPE_NAME'];
        }

        return $rows;
    }

    private function getIblockSection(): array
    {
        $rows = [];
        if ($this->iblockId > 0) {
            $parameters = [
                'filter' => [
                    'IBLOCK_ID' => $this->iblockId,
                    'ACTIVE' => 'Y',
                    'GLOBAL_ACTIVE' => 'Y'
                ],
                'select' => ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'IBLOCK_ID'],
                'order' => ['LEFT_MARGIN' => 'ASC', 'SORT' => 'ASC'],
                'cache' => ['ttl' => 86400]
            ];
            $result = SectionTable::getList($parameters);
            while ($row = $result->fetch()) {
                $row['CATEGORY'] = $this->sectionCategory[$row['ID']]['CATEGORY_NAME'] ?: '';
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function arrayValuesRecursive(array $arr): array
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->arrayValuesRecursive($value);
            }
        }

        if (isset($arr['CHILD'])) {
            $arr['CHILD'] = array_values($arr['CHILD']);
        }

        return $arr;
    }

    private function getCategoryMain(array $filter): array
    {
        $rows = [];

        $parameters = [
            'filter' => ['DISABLED' => false],
            'select' => ['CATEGORY_ID', 'CATEGORY_NAME', 'DISABLED'],
            'order' => ['CATEGORY_NAME' => 'ASC']
        ];

        if ($filter) {
            $parameters['filter'] = array_merge($parameters['filter'], $filter);
        }

        $result = TreeCategoryTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'CATEGORY_ID' => $row['CATEGORY_ID'],
                'CATEGORY_NAME' => $row['CATEGORY_NAME'],
                'DISABLED' => $row['DISABLED']
            ];
        }

        return $rows;
    }

    private function getTypes(int $categoryId): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'CATEGORY_ID' => $categoryId,
                'DISABLED' => false
            ],
            'select' => [
                'TYPE_ID',
                'TYPE_NAME' => 'TYPE.TYPE_NAME',
                'DISABLED' => 'TYPE.DISABLED'
            ],
            'order' => ['TYPE_NAME' => 'ASC']
        ];

        $result = TreeBindCategoryTypeTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'TYPE_ID' => $row['TYPE_ID'],
                'TYPE_NAME' => $row['TYPE_NAME'],
                'DISABLED' => $row['DISABLED'],
                'ACTIVE' => false
            ];
        }

        return $rows;
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function setCategory(string $level2, string $level3, int $sectionId = 0): array
    {
        $params = [
            'IBLOCK_ID' => $this->iblockId,
            'CATEGORY_ID' => $level2,
            'TYPE_ID' => $level3,
        ];
        if ($sectionId) {
            $params['SECTION_ID'] = $sectionId;
        }

        $id = $this->getConnectionTreeId($sectionId);
        if ($id) {
            $result = ConnectionSectionTreeTable::update($id, $params);
        } else {
            $result = ConnectionSectionTreeTable::add($params);
        }
        if ($result->isSuccess()) {
            return ['STATUS' => 'SUCCESS'];
        }

        return ['ERROR_LIST' => $result->getErrorMessages(), 'STATUS' => 'ERROR'];
    }

    private function getConnectionTreeId(int $sectionId = 0): int
    {
        $parameters = [
            'filter' =>
                [
                    'IBLOCK_ID' => $this->iblockId,
                    'SECTION_ID' => $sectionId ?: false
                ],
            'select' => ['ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            return $row['ID'];
        }

        return 0;
    }

    private function deleteCategory(int $sectionId = 0): array
    {
        $parameters = [
            'filter' => ['IBLOCK_ID' => $this->iblockId, 'SECTION_ID' => $sectionId ?: false],
            'select' => ['ID']
        ];
        $result = ConnectionSectionTreeTable::getList($parameters);
        if ($row = $result->fetch()) {
            $delete = ConnectionSectionTreeTable::delete($row['ID']);
            if ($delete->isSuccess()) {
                return ['STATUS' => 'SUCCESS'];
            }
            return ['ERROR_LIST' => $delete->getErrorMessages(), 'STATUS' => 'ERROR'];
        }

        return ['STATUS' => 'ERROR'];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
