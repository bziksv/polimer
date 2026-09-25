<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

Bitrix\Main\Loader::includeModule("sale");

$agreementList = AgreementTable::getList([
	'select' => ['ID', 'NAME'],
]);

$consentParamsList = [];

foreach($agreementList as $arConsent) {
    $consentParamsList[$arConsent["ID"]] = "[" . $arConsent["ID"] . "] " . $arConsent["NAME"];
}

$personTypes = [];
$dbRes = \Bitrix\Sale\PersonType::getList();
foreach($dbRes as $arType) {
	$personTypes[$arType['ID']] = "[" . $arType["ID"] . "] " . $arType["NAME"];
}


$arComponentParameters = array(
	"GROUPS"     => [],
	"PARAMETERS" => [
		'PRODUCT_ID'             => [
			"PARENT"   => "BASE",
			"NAME"     => Loc::getMessage("YASTORE.WIDGET_COMPONENT_TCB_BUTTON_PARAM_PRODUCT_ID"),
			"TYPE"     => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT"  => '={$arResult["ID"]}',
		],

		'OFFERS'                 => [
			"PARENT"   => "BASE",
			"NAME"     => Loc::getMessage("YASTORE.WIDGET_COMPONENT_TCB_BUTTON_PARAM_OFFERS"),
			"TYPE"     => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT"  => '={$arResult["OFFERS"]}',
		],
		'PERSON_TYPE_ID' => [
			"PARENT"   => "BASE",
			"NAME"     => Loc::getMessage("YASTORE.WIDGET_COMPONENT_TCB_BUTTON_PARAM_PERSON_TYPE_ID"),
			"TYPE"     => "LIST",
			"MULTIPLE" => "N",
			"VALUES"   => $personTypes,
		],
		'CACHE_TIME' => array('DEFAULT' => 3600),
		'CACHE_GROUPS' => array(
			'PARENT' => 'CACHE_SETTINGS',
			'NAME' => GetMessage('CP_CPV_CACHE_GROUPS'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
		)
	],
);
