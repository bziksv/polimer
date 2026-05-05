<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Main\Table\ApiLogTable;
use Darneo\Ozon\Main\Table\SettingsTable;

class OzonSystemLogListComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private int $count = 50;
    private string $navigation = 'nav-log';

    private string $filterSearch;
    private string $filterStatus;
    private int $totalCount = 0;

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'setSettingLog':
                    $count = $this->request['count'] ?: 1;
                    $status = $this->setSettingLog($count);
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
        if ($this->request['clear'] === 'y') {
            ApiLogTable::clearTable();
            LocalRedirect($this->arParams['SEF_FOLDER']);
        }

        $this->totalCount = ApiLogTable::getTotalRecordsCount();
        $this->filterSearch = $this->request['filterSearch'] ?: '';
        $this->filterStatus = $this->request['filter'] ?: 'all';
    }

    private function setTemplateData(): void
    {
        $elements = $this->getList();

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements,
            'CLEAR' => $this->getClearUrl(),
            'COUNT_ALL' => number_format($this->totalCount, 0, '.', ' '),
            'LOG_SAVE' => HelperSettings::getLogRetentionDays(),
            'FILTER' => [
                'SEARCH' => $this->filterSearch
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.system.log.list'
        );
    }

    private function getList(): array
    {
        $rows = [];

        $nav = new PageNavigation($this->navigation);
        $nav->allowAllRecords(true)
            ->setPageSize($this->count)
            ->initFromUri();

        $offset = $nav->getOffset();
        $limit = $nav->getLimit();

        $parameters = [
            'select' => [
                'ID',
                'DATE_CREATED',
                'CLIENT_ID',
                'KEY',
                'URL'
            ],
            'order' => ['ID' => 'DESC'],
            'offset' => $offset,
            'limit' => $limit > 0 ? $limit + 1 : 0,
        ];

        $result = ApiLogTable::getList($parameters);

        $n = 0;
        while ($row = $result->fetch()) {
            $n++;
            if ($limit > 0 && $n > $limit) {
                break;
            }
            if ($row['DATE_CREATED'] instanceof \Bitrix\Main\Type\DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->toString();
            }

            $row['DOCS'] = Darneo\Ozon\Api\Config::getDocumentationByUrl($row['URL']);
            $row['DETAIL_PAGE_URL'] = $this->generateDetailUrl($row['ID']);

            $rows[] = $row;
        }

        $nav->setRecordCount($offset + $n);
        $nav->allowAllRecords(false);

        $this->arResult['NAV'] = $nav;

        return $rows;
    }

    private function generateDetailUrl(int $elementId): string
    {
        return $this->arParams['SEF_FOLDER'] . str_replace(
                '#ELEMENT_ID#',
                $elementId,
                $this->arParams['URL_TEMPLATES']['detail']
            );
    }

    private function getClearUrl(): string
    {
        $uri = new Uri($this->arParams['SEF_FOLDER']);
        $uri->addParams(['clear' => 'y']);

        return $uri->getUri();
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function setSettingLog(int $count): array
    {
        if ($count > 0 && $count <= 365) {
            $result = SettingsTable::update('CRON', ['VALUE' => $count]);
            if ($result->isSuccess()) {
                return ['STATUS' => 'SUCCESS'];
            }
            return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => ['Error count']];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
