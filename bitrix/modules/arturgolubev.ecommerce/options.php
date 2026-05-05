<?
use \Arturgolubev\Ecommerce\Settings,
	\Arturgolubev\Ecommerce\Unitools as UTools;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option;

$module_id = 'arturgolubev.ecommerce';
$module_name = str_replace('.', '_', $module_id);
$MODULE_NAME = strtoupper($module_name);

include $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$module_id.'/lib/installation.php';

if(!Loader::includeModule($module_id)){
	include 'autoload.php';
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/options.php");

global $USER, $APPLICATION;
if (!$USER->IsAdmin()) return;

$r = Settings::checkModuleDemoEx($module_id);
if($r == 'exit'){
	return;
}

if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog") || !Loader::includeModule("sale")){
	?>
		<div class="adm-info-message-wrap adm-info-message-red">
			<div class="adm-info-message">
				<div class="adm-info-message-title"><?//=GetMessage($MODULE_NAME . "_ALLOW_URL_FOPEN_NOT_FOUND")?></div>
				<?=GetMessage("ARTURGOLUBEV_EC_SMALL_BITRIX")?>
				<div class="adm-info-message-icon"></div>
			</div>
		</div>
	<?
	return;
}

/* get site */
$siteList = Settings::getSites();

/* get currency */
$arCurrencyList = array();
$arCurrencyList[] = GetMessage("ARTURGOLUBEV_EC_CONVERT_CURRENCY_EMPTY");
$arCurrencyAllowed = array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');
$dbRes = CCurrency::GetList($by = 'sort', $order = 'asc');
while ($arRes = $dbRes->GetNext())
{
	if (in_array($arRes['CURRENCY'], $arCurrencyAllowed))
		$arCurrencyList[$arRes['CURRENCY']] = $arRes['FULL_NAME'];
}


/* get catalog */
$arCatalogs = array();
$res = CCatalog::GetList(Array(), Array('IBLOCK_ACTIVE'=>'Y'), false, false, array("*", "OFFERS"));
while($ar_res = $res->Fetch())
{
	$arCatalogs[] = $ar_res["IBLOCK_ID"];
}

$arMainCatalogs = array();
foreach($arCatalogs as $catalogId){
	$arCatalogInfo = CCatalog::GetByID($catalogId);

	$arCatalogInfo["PROPS"] = [];
	$properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arCatalogInfo["IBLOCK_ID"]));
	while ($prop_fields = $properties->GetNext()){
		if($prop_fields["PROPERTY_TYPE"] == 'F' || $prop_fields["PROPERTY_TYPE"] == 'N')
			continue;
		
		$arCatalogInfo["PROPS"][] = $prop_fields;
	}
	
	$arMainCatalogs[] = $arCatalogInfo;
}

$request_mode_variants = array(
	"events" => GetMessage("ARTURGOLUBEV_EC_REQUEST_MODE_EVENTS"),
	"interval" => GetMessage("ARTURGOLUBEV_EC_REQUEST_MODE_INTERVALS"),
	"noajax" => GetMessage("ARTURGOLUBEV_EC_REQUEST_MODE_NOAJAX"),
);

$card_mode_variants = array(
	"" => GetMessage("ARTURGOLUBEV_EC_PRODUCT_CARD_MODE_COMPONENT"),
	"id" => GetMessage("ARTURGOLUBEV_EC_PRODUCT_CARD_MODE_GLOBAL"),
);


/* make options */
$catagloIblockVariants = [];

$arOptions = array();
$arOptions["main"] = array();

$arOptions["main"][] = array("off_mode", GetMessage("ARTURGOLUBEV_EC_OFF_MODE"), "N", array("checkbox"));
$arOptions["main"][] = array("request_mode", GetMessage("ARTURGOLUBEV_EC_REQUEST_MODE"), "", array("selectbox", $request_mode_variants));
// $arOptions["main"][] = array("product_card_mode", GetMessage("ARTURGOLUBEV_EC_PRODUCT_CARD_MODE"), "", array("selectbox", $card_mode_variants));

$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_DATA_SECTION");

$orderNumVariants = array("selectbox", array(
	"ID"=> GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ID"),
	"ACCOUNT_NUMBER"=> GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ACCOUNT_NUMBER"),
));
$arOptions["main"][] = array("get_order_id_from", GetMessage("ARTURGOLUBEV_EC_GET_ORDER_ID_FROM"), "N", $orderNumVariants);

foreach($arMainCatalogs as $arCatalog){
	$empty_value_name = ($arCatalog['OFFERS'] == 'N') ? GetMessage("ARTURGOLUBEV_EC_BRAND_NO_SELECT") : GetMessage("ARTURGOLUBEV_EC_BRAND_SKU_NO_SELECT") ;

	$arValues = [
		"" => $empty_value_name
	];

	foreach($arCatalog["PROPS"] as $arProp){
		$arValues[$arProp["ID"]] = '['.$arProp["ID"].'] '.$arProp["NAME"];
	}
	
	$name = 'BRAND_PROPERTY_'.$arCatalog["IBLOCK_ID"];
	$arOptions["main"][] = array($name, GetMessage("ARTURGOLUBEV_EC_OPTIONS_BRAND_PROP").'<b>'.$arCatalog["NAME"].' ['.$arCatalog["IBLOCK_ID"].']</b>:', "", array("selectbox", $arValues));

	// echo '<pre>'; print_r($arCatalog); echo '</pre>';
	$catagloIblockVariants[$arCatalog["IBLOCK_ID"]] = $arCatalog["NAME"]. '['.$arCatalog["IBLOCK_ID"].']';
}


$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_COLLECT_DATA");
$arOptions["main"][] = array("collect_utm", GetMessage("ARTURGOLUBEV_EC_COLLECT_UTM"), "N", array("checkbox"));
$arOptions["main"][] = array("collect_client_id", GetMessage("ARTURGOLUBEV_EC_COLLECT_CLIENT_ID"), "N", array("checkbox"));

$arOptions["main"][] = GetMessage("ARTURGOLUBEV_EC_SYSTEM");
$arOptions["main"][] = array("debug", GetMessage("ARTURGOLUBEV_EC_DEBUG_MODE"), "N", array("checkbox"));

$arTabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage("ARTURGOLUBEV_EC_OPTIONS_MAINTAB"), "TITLE" => GetMessage("ARTURGOLUBEV_EC_OPTIONS_MAINTAB"), "OPTIONS"=>"main"),
);

if(count($siteList))
{
	foreach($siteList as $arSite)
	{
		$key = "site_options_".$arSite["ID"];
		
		$arOptions[$key] = array();
		
		$arOptions[$key][] = array("off_mode_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_DISABLED_SITE")." <b>".$arSite["NAME"]." [".$arSite["ID"]."]</b>:", "N", array("checkbox"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_ALL_SETTING");
		// $arOptions[$key][] = array("catalogs_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CATALOGS"), "", array("multiselectbox", $catagloIblockVariants)); // for automap
		$arOptions[$key][] = array("convert_currency_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CONVERT_CURRENCY"), "", array("selectbox", $arCurrencyList), "N", GetMessage("ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY_NOTE"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_ALL_SCHEME");
		$arOptions[$key][] = array("cart_page_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CART_PAGE"), "", array("text"), "N", GetMessage("ARTURGOLUBEV_EC_CART_PAGE_NOTE"));
		$arOptions[$key][] = array("order_page_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE"), "", array("text"), "N", GetMessage("ARTURGOLUBEV_EC_ORDER_PAGE_NOTE"));
		// $arOptions[$key][] = array("product_page_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_PRODUCT_PAGE"), "", array("text"), "N", GetMessage("ARTURGOLUBEV_EC_PRODUCT_PAGE_NOTE")); // for automap
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING");
		$arOptions[$key][] = array("ya_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_OFF"), "N", array("checkbox"));
		$arOptions[$key][] = array("container_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_CONTAINER_NAME"), "dataLayer", array("text"));
		
		if(Option::get($module_id, "yandex_target_order_".$arSite["ID"])){
			$arOptions[$key][] = array("yandex_target_order_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_TARGET_ORDER"), "", array("text"));
		}
		
		$arOptions[$key][] = array("yandex_goal_counter_id_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_COUNTER_ID"), "", array("text"));
		$arOptions[$key][] = array("yandex_goal_product_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_PRODUCT"), "N", array("checkbox"));
		$arOptions[$key][] = array("yandex_goal_add_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_ADD"), "N", array("checkbox"));
		$arOptions[$key][] = array("yandex_goal_remove_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_REMOVE"), "N", array("checkbox"));
		$arOptions[$key][] = array("yandex_goal_cart_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_CART"), "N", array("checkbox"));
		$arOptions[$key][] = array("yandex_goal_order_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_ORDER"), "N", array("checkbox"));
		$arOptions[$key][] = array("yandex_goal_purchase_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_YA_GOAL_PURCHASE"), "N", array("checkbox"));
		
		$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING");
		$arOptions[$key][] = array("ga_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_GA_OFF"), "N", array("checkbox"));
		$arOptions[$key][] = array("ga_gbv_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_USE_GBV"), "N", array("checkbox"));

		$arOptions[$key][] = array("ga_payship_type_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE"), "", array("selectbox", array(
			"" => GetMessage("ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE_STANDART"),
			"order" => GetMessage("ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE_ORDER"),
		)));

		// $arOptions[$key][] = array("ga_type_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_GA_TYPE"), "", array("selectbox", array(
			// "" => GetMessage("ARTURGOLUBEV_EC_GA_TYPE_COUNTERS"),
			// "mp" => GetMessage("ARTURGOLUBEV_EC_GA_TYPE_MP"),
		// )));

		$fbq = (Option::get($module_id, "show_facebook") == 'Y' || (Option::get($module_id, "fb_off_".$arSite["ID"]) == 'N'));
		if($fbq){
			$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_FACEBOOK_SETTING");
			$arOptions[$key][] = array("fb_off_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_FB_OFF"), "N", array("checkbox"));
		}

		if(Loader::includeModule("form")){
			$arOptions[$key][] = GetMessage("ARTURGOLUBEV_EC_OPTIONS_FORMS_SETTING");
			$arOptions[$key][] = array("forms_ym_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_FORMS_YM"), "N", array("checkbox"));
			$arOptions[$key][] = array("forms_ga_".$arSite["ID"], GetMessage("ARTURGOLUBEV_EC_FORMS_GA"), "N", array("checkbox"));
		}

		$arTabs[] = array("DIV" => "site_setting_".$key, "TAB" => $arSite["NAME"].' ['.$arSite["ID"].']', "TITLE" => GetMessage("ARTURGOLUBEV_EC_SITE_SETTING").' "'.$arSite["NAME"].'" ['.$arSite["ID"].']', "OPTIONS"=>$key);
	}
}

$tabControl = new CAdminTabControl("tabControl", $arTabs);

// ****** SaveBlock
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && check_bitrix_sessid())
{
	CAdminNotify::Add(array('MESSAGE' => GetMessage("ARTURGOLUBEV_EC_CLEAR_CACHE"),  'TAG' => $module_name."_clear_cache", 'MODULE_ID' => $module_id, 'ENABLE_CLOSE' => 'Y'));

	foreach ($arOptions as $aOptGroup) {
		foreach ($aOptGroup as $option) {
			__AdmSettingsSaveOption($module_id, $option);
		}
	}
	
    if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0)
        LocalRedirect($_REQUEST["back_url_settings"]);
    else
        LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());
}

\Arturgolubev\Ecommerce\Admin\SettingsHelper::checkModuleRules();

$collect_utm = UTools::getSetting('collect_utm');
$collect_client_id = UTools::getSetting('collect_client_id');
agInstaHelperEcommerce::checkOrderStructure($collect_utm, $collect_client_id);
?>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->Begin();?>
	
	<?foreach($arTabs as $key=>$tab):
		$tabControl->BeginNextTab();
		Settings::showSettingsList($module_id, $arOptions, $tab);
	endforeach;?>
	
	<?$tabControl->Buttons();?>
		<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
				
		<?if(strlen($_REQUEST["back_url_settings"])>0):?>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
		<?endif?>
		
		<?=bitrix_sessid_post();?>
	<?$tabControl->End();?>
</form>

<?if(Option::get($module_id, "debug") == 'Y'):
	$arDebugTypes = [
		'product', 'addcart', 'removecart', 'order', 'purchase'
	];
?>
	<div class="help_note_wrap">
		<?=BeginNote();?>
			<div style="color: #000; font-size: 14px;">
				<b><?=GetMessage("ARTURGOLUBEV_EC_DEBUG_INFORMATION")?></b><br/><br/>
				<p><?=GetMessage("ARTURGOLUBEV_EC_DEBUG_INFORMATION_TEXT")?></p>
				
				<ul>
					<?foreach($arDebugTypes as $dtype):
						$lastCall = Option::get($module_id, "work_".$dtype);
					?>
						<li style="margin: 5px 0;"><?=GetMessage('ARTURGOLUBEV_EC_DEBUG_EVENT_'.strtoupper($dtype))?>: <?=($lastCall) ? date('d.m.Y H:i', Option::get($module_id, "work_".$dtype)) : '-';?></li>
					<?endforeach;?>
				</ul>
			</div>
		<?=EndNote();?>
	</div>
<?endif?>

<?Settings::showInitUI();?>

<div class="help_note_wrap">
	<?= BeginNote();?>
		<p class="title"><?=GetMessage("ARTURGOLUBEV_ECOMMERCE_HELP_TAB_TITLE")?></p>
		<p><?=GetMessage("ARTURGOLUBEV_ECOMMERCE_HELP_TAB_VALUE")?></p>
	<?= EndNote();?>
</div>
