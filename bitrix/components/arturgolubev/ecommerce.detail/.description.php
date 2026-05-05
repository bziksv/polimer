<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("ARTURGOLUBEV_ECOMMERCE_COMPONENT_DETAIL_NAME"),
	"DESCRIPTION" => GetMessage("ARTURGOLUBEV_ECOMMERCE_COMPONENT_DETAIL_NAME"),
	"ICON" => "/images/icon.gif",
	"SORT" => 510,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "AG_DOP_SERVICES",
		"NAME" => GetMessage("ARTURGOLUBEV_ECOMMERCE_MAIN_FOLDER"),
		"SORT" => 1930,
		"CHILD" => array(
			"ID" => "ECOMMERCE",
			"NAME" => GetMessage("ARTURGOLUBEV_ECOMMERCETITLE_FOLDER"),
			"SORT" => 150
		)
	),
	"COMPLEX" => "N",
);
?>