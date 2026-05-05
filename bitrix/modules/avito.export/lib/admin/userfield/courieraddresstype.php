<?php
namespace Avito\Export\Admin\UserField;

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/** @noinspection PhpUnused */
class CourierAddressType extends StringType
{
    protected static function makeInput(array $userField, array $htmlControl): string
    {
        Extension::load('avitoexport.admin.courieraddress');

        $id = preg_replace('/\W+/', '_', $htmlControl['NAME']);
        $id = trim($id, '_');
        $userField['SETTINGS']['HTML_ID'] = $id;

        $html = parent::makeInput($userField, $htmlControl);
        $html .= '<small data-entity="error-message" style="color: red;"></small>';
	    /** @noinspection BadExpressionStatementJS */
	    $html .= sprintf('<script> new BX.AvitoExport.Admin.CourierAddress("#%s", %s) </script>', $id, Json::encode([
            'orderId' => (string)$userField['SETTINGS']['ORDER_ID'],
            'exchangeId' => (int)$userField['SETTINGS']['TRADING_ID'],
        ]));

        return $html;
    }
}
