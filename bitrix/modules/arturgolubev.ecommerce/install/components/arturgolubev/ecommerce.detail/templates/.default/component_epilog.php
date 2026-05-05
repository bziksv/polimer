<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(CModule::IncludeModule('arturgolubev.ecommerce') && $arResult["JS_CODE"]){
	$action_script = '<script>function agec_detail_script(){'.$arResult["JS_CODE"].'};' . PHP_EOL . 'if(window.frameCacheVars !== undefined){agec_detail_script();}else{document.addEventListener("DOMContentLoaded", function(){agec_detail_script();});}</script>' . PHP_EOL;
	\Arturgolubev\Ecommerce\Tools::addEpilogScript($action_script);
}