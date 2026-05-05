<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
    "NAME" =>  Loc::getMessage("SM_NAME"),
    "DESCRIPTION" =>  Loc::getMessage("SM_DESCRIPTION"),
    "ICON" => "/images/icon.png",
    "PATH" => array(
        "ID" =>  Loc::getMessage("MAIN_GROUP_NAME_SOTBIT"),
        "CHILD" => array(
            "ID" => "sotbit.seo.meta",
            "NAME" => Loc::getMessage("MAIN_MENU_NAME_SOTBIT"),
        )
    ),
);
?>