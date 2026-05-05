<?php
namespace Avito\Export\Admin\UserField;

use Bitrix\Main\UI\Extension;

/** @noinspection PhpUnused */
class CourierTimeType extends StringType
{
    public static function GetEditFormHTML(array $userField, array $htmlControl) : string
    {
        Extension::load('avitoexport.admin.couriertime');

        $id = preg_replace('/\W+/', '_', $htmlControl['NAME']);
        $id = trim($id, '_');

        $html = sprintf('
            <div id="%s" style="display: flex; flex-direction: column; max-width: 190px">
                <select data-entity="date" disabled></select>
                <select name="%s" data-entity="time" disabled></select>
            </div>
        ', $id, $htmlControl['NAME']);
        /** @noinspection BadExpressionStatementJS */
        $html .= sprintf('<script> new BX.AvitoExport.Admin.CourierTime("#%s") </script>', $id);

        return $html;
    }
}
