<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Darneo\Ozon\Main\Helper\Access;

class OzonSystemAgentListComponent extends CBitrixComponent
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
        if (!Access::isPermission()) {
            throw new LoaderException('Access is not allowed');
        }
    }

    private function dataManager(): void
    {
    }

    private function setTemplateData(): void
    {
        $elements = $this->getList();

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements
        ];
    }

    private function getList(): array
    {
        global $DB;

        $rows = [];

        $strSql = "SELECT 
                    ID, NAME, ACTIVE, LAST_EXEC, NEXT_EXEC, AGENT_INTERVAL  
                        FROM b_agent WHERE MODULE_ID = 'darneo.ozon'";
        $result = $DB->Query($strSql);
        while ($row = $result->Fetch()) {
            $row['LAST_EXEC'] = $this->getDateFormated($row['LAST_EXEC'] ?: '');
            $row['NEXT_EXEC'] = $this->getDateFormated($row['NEXT_EXEC'] ?: '');

            $row['LINK'] = Loc::getMessage('DARNEO_OZON_MODULE_AGENT_LINK', ['#ID#' => $row['ID']]);

            $rows[] = $row;
        }

        return $rows;
    }

    private function getDateFormated(string $date): string
    {
        $timestamp = strtotime($date);

        $formattedDate = '';
        if ($timestamp !== false) {
            $formattedDate = date('d.m.Y H:i:s', $timestamp);
        }

        return $formattedDate;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
