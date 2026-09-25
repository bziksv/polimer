<?php
namespace Yandex\Market\Checkout\Delivery;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

class YandexKitDelivery extends Base
{
    protected function calculateConcrete(Shipment $shipment)
    {
        $result = new CalculationResult();
        $result->setDeliveryPrice(0);

        return $result;
    }

    public static function getClassTitle()
    {
        return Loc::getMessage('YANDEX_KIT_DELIVERY_TITLE') ?: 'YCP';
    }

    public static function getClassDescription()
    {
        return Loc::getMessage('YANDEX_KIT_DELIVERY_DESCRIPTION') ?: 'Служебная служба доставки для YCP API';
    }

    public static function isProfile()
    {
        return false;
    }

    protected function getConfigStructure($initParams = array())
    {
        return array();
    }
}
