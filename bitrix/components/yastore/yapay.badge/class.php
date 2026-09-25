<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}


Loc::loadMessages(__FILE__);

if (!Loader::includeModule("yandex.market")) {
    ShowError(Loc::getMessage("SOA_MODULE_NOT_INSTALL"));

    return;
}


// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class YaPayBadge extends \CBitrixComponent
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
        $this->arResult['merchant_id'] = Option::get('yandex.market', 'YA_PAY_MERCHANT_ID', '');
        $this->arResult['badge_type'] = Option::get('yandex.market', 'badge_type', 'bnpl'); // TODO: ?
        $this->arResult['badge_theme'] = Option::get('yandex.market', 'YA_PAY_BADGE_THEME', 'light');
        $this->arResult['badge_size'] = Option::get('yandex.market', 'YA_PAY_BADGE_SIZE', 'l');
        $this->arResult['badge_align'] = Option::get('yandex.market', 'YA_PAY_BADGE_ALIGN', 'left');
        $this->arResult['badge_color'] = Option::get('yandex.market', 'YA_PAY_BADGE_COLOR', 'primary');
        $this->arResult['badge_variant'] = Option::get('yandex.market', 'YA_PAY_BADGE_VARIANT', 'detailed');
        $this->arResult['badge_split'] = Option::get('yandex.market', 'YA_PAY_BADGE_USE_SPLIT');
        $this->arResult['badge_cashback'] = Option::get('yandex.market', 'YA_PAY_BADGE_USE_CASHBACK');
    }
}
