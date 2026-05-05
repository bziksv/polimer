<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!isset($arParams["CACHE_TIME"])) {
	$arParams["CACHE_TIME"] = 3600;
}

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if($this->StartResultCache(false, array()))
{
	if(!CModule::IncludeModule("iblock") || !CModule::IncludeModule("arturgolubev.ecommerce")) {
		$this->AbortResultCache();
		return false;
	}
	
	if($arParams["PRODUCT_ID"]){
		$arDopParams = [];

		if($arParams['OFFER_SELECT_FIRST'] && $arParams['OFFER_SELECT_FIRST'] == 'Y'){
			$arDopParams['OFFER_SELECT_FIRST'] = 1;
		}

		if($arParams['SELECTED_OFFER'] && intval($arParams['SELECTED_OFFER'])){
			$arDopParams['SELECTED_OFFER'] = intval($arParams['SELECTED_OFFER']);
		}

		$arResult["JS_CODE"] = CArturgolubevEcommerce::getDetailCode($arParams["PRODUCT_ID"], $arParams["OFFERS_CART_PROPERTIES"], $arDopParams);
	}

	$this->SetResultCacheKeys(array("JS_CODE"));
	$this->IncludeComponentTemplate();
}
?>