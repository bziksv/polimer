<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Main\Table\ApiLogTable;

class OzonSystemLogDetailComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            $this->setTemplateData();
            $this->includeComponentTemplate();
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
    }

    private function setTemplateData(): void
    {
        $element = $this->getList();

        $this->arResult['DATA_VUE'] = [
            'DATA' => $element
        ];
    }

    private function getList(): array
    {
        $rows = [];

        $parameters = [
            'filter' => ['ID' => $this->arParams['ELEMENT_ID']],
            'select' => [
                'ID',
                'DATE_CREATED',
                'CLIENT_ID',
                'KEY',
                'URL',
                'METHOD_TRACKER',
                'DATA_SEND',
                'DATA_RECEIVED',
            ],
        ];
        $result = ApiLogTable::getList($parameters);
        if ($row = $result->fetch()) {
            if ($row['DATE_CREATED'] instanceof \Bitrix\Main\Type\DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->toString();
            }

            $row['DOCS'] = Darneo\Ozon\Api\Config::getDocumentationByUrl($row['URL']);

            $rows = $row;
        }

        return $rows;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
