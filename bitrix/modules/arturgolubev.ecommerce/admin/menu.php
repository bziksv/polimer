<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.ecommerce/menu.php");

$arSubmenu[] = [
	'text' => GetMessage("ARTURGOLUBEV_EC_SUBMENU_SETTINGS"),
	'more_url' => [],
	'url' => '/bitrix/admin/settings.php?lang='.LANG.'&mid=arturgolubev.ecommerce',
	'icon' => 'sys_menu_icon',
];

$aMenu = [
	'parent_menu' => 'global_menu_services',
	'section' => 'arturgolubev_ecommerce',
	'sort' => 5,
	'text' => GetMessage("ARTURGOLUBEV_EC_MENU_MAIN"),
	'icon' => 'arturgolubev_ecommerce_icon_main',
	'items_id' => 'arcg_icon_main',
	'items' => $arSubmenu,
];


return $aMenu;