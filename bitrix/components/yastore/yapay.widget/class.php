<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

if (!Loader::includeModule("yandex.market")) {
    ShowError(Loc::getMessage("SOA_MODULE_NOT_INSTALL"));

    return;
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class YaPayWidget extends \CBitrixComponent
{

    public function executeComponent()
    {
        if (Option::get('yandex.market', 'SHOW_BUTTON', 'N') !== 'Y') {
            $this->abortResultCache();
            return;
        }

        if (Option::get('yandex.market', 'YA_PAY_MERCHANT_ID', '') === '') {
            $this->abortResultCache();
            return;
        }

        $this->prepareResult();
        $this->includeComponentTemplate();
    }

    protected function prepareResult()
    {
        $this->arResult['merchant_id']        = Option::get('yandex.market', 'YA_PAY_MERCHANT_ID', '');
        $this->arResult['widget_outline']     = (bool)Option::get('yandex.market', 'YA_PAY_WIDGET_OUTLINE');
        $this->arResult['widget_size']        = Option::get('yandex.market', 'YA_PAY_WIDGET_SIZE');
        $this->arResult['widget_background']  = Option::get('yandex.market', 'YA_PAY_WIDGET_BACKGROUND');
        $this->arResult['widget_hide_header'] = (bool)Option::get('yandex.market', 'YA_PAY_WIDGET_HIDE_HEADER');
        $this->arResult['widget_theme']       = Option::get('yandex.market', 'YA_PAY_WIDGET_THEME');
        $this->arResult['widget_padding']     = Option::get('yandex.market', 'YA_PAY_WIDGET_PADDING');
        $this->arResult['widget_radius']      = Option::get('yandex.market', 'YA_PAY_WIDGET_RADIUS');
        $this->arResult['widget_split']       = Option::get('yandex.market', 'YA_PAY_WIDGET_SPLIT');

        $widgetWidth = intval(Option::get('yandex.market', 'YA_PAY_WIDGET_WIDTH'));
        Asset::getInstance()->addString("<style>#ya-pay-widget{ width: {$widgetWidth}px !important;}</style>");
    }
}
