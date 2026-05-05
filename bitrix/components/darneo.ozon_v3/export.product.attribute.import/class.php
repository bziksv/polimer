<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\ConnectionSectionTreeTable;
use Darneo\Ozon\Import\ConnectionPropValue;
use Darneo\Ozon\Import\Core\Attribute;
use Darneo\Ozon\Import\Core\AttributeValue;
use Darneo\Ozon\Import\Table\ConnectionPropCategoryTable;

class OzonExportProductAttributeImportComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private string $categoryId;
    private string $typeId;

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'import':
                    $this->deleteCategoryData();
                    $this->importAttribute();
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
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
        $this->initConnectionField($this->request['connectionSectionTree'] ?: 0);
    }

    private function initConnectionField(int $connectionSectionTree): void
    {
        $connection = ConnectionSectionTreeTable::getById($connectionSectionTree)->fetch();
        $this->categoryId = $connection['CATEGORY_ID'] ?: '';
        $this->typeId = $connection['TYPE_ID'] ?: '';
    }

    private function deleteCategoryData(): void
    {
        // удаление привязок аттрибутов к категории
        $result = ConnectionPropCategoryTable::getList(
            [
                'filter' => [
                    'CATEGORY_ID' => $this->categoryId,
                    'TYPE_ID' => $this->typeId
                ],
                'select' => ['ID']
            ]
        );
        while ($row = $result->fetch()) {
            ConnectionPropCategoryTable::delete($row['ID']);
        }

        // удаление привязок значений свойств к аттрибутам
        (new ConnectionPropValue($this->typeId))->dropTable();
    }

    private function importAttribute(): void
    {
        (new Attribute())->start($this->categoryId, $this->typeId);
        (new AttributeValue())->start($this->categoryId, $this->typeId);
    }

    private function setTemplateData(): void
    {
        $this->arResult['DATA_VUE'] = [];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.attribute'
        );
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
