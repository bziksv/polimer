<?
//<title>Avito</title>
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global string $ACTION */
/** @global array $arOldSetupVars */
/** @global int $IBLOCK_ID */
/** @global string $SETUP_FILE_NAME */
/** @global string $SETUP_SERVER_NAME */
/** @global mixed $V */
/** @global mixed $XML_DATA */
/** @global string $SETUP_PROFILE_NAME */

use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog;
CModule::IncludeModule("abricos.avitoautoload");
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_setup_templ.php');
IncludeModuleLangFile(__FILE__);

global $APPLICATION, $USER;

$arSetupErrors = array();

$strAllowExportPath = COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/");

if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 1)
{
	if (isset($arOldSetupVars['IBLOCK_ID']))
		$IBLOCK_ID = $arOldSetupVars['IBLOCK_ID'];
	if (isset($arOldSetupVars['SITE_ID']))
		$SITE_ID = $arOldSetupVars['SITE_ID'];
	if (isset($arOldSetupVars['SETUP_FILE_NAME']))
		$SETUP_FILE_NAME = str_replace($strAllowExportPath,'',$arOldSetupVars['SETUP_FILE_NAME']);
	if (isset($arOldSetupVars['COMPANY_NAME']))
		$COMPANY_NAME = $arOldSetupVars['COMPANY_NAME'];
	if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
		$SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
	if (isset($arOldSetupVars['V']))
		$V = $arOldSetupVars['V'];
	if (isset($arOldSetupVars['XML_DATA']))
	{

			$XML_DATA = base64_encode($arOldSetupVars['XML_DATA']);
	}
	if (isset($arOldSetupVars['SETUP_SERVER_NAME']))
		$SETUP_SERVER_NAME = $arOldSetupVars['SETUP_SERVER_NAME'];
	if (isset($arOldSetupVars['USE_HTTPS']))
		$USE_HTTPS = $arOldSetupVars['USE_HTTPS'];
	if (isset($arOldSetupVars['FILTER_AVAILABLE']))
		$filterAvalable = $arOldSetupVars['FILTER_AVAILABLE'];
	if (isset($arOldSetupVars['DISABLE_REFERERS']))
		$disableReferers = $arOldSetupVars['DISABLE_REFERERS'];
	if (isset($arOldSetupVars['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $arOldSetupVars['MAX_EXECUTION_TIME'];
	if (isset($arOldSetupVars['AVITO_CATEGORY']))
		$avitoCategory = $arOldSetupVars['AVITO_CATEGORY'];
	if (isset($arOldSetupVars['AVITO_ADRESS']))
		$avitoAdress = $arOldSetupVars['AVITO_ADRESS'];
	if (isset($arOldSetupVars['AVITO_LISTINGFEE']))
		$avitoListingfee = $arOldSetupVars['AVITO_LISTINGFEE'];
	if (isset($arOldSetupVars['AVITO_CONDITION']))
		$avitoCondition = $arOldSetupVars['AVITO_CONDITION'];
	if (isset($arOldSetupVars['AVITO_ADTYPE']))
		$avitoAdtype = $arOldSetupVars['AVITO_ADTYPE'];
	if (isset($arOldSetupVars['AVITO_CATEGORY_TYPE']))
		$avitoCategoryType = $arOldSetupVars['AVITO_CATEGORY_TYPE'];
     if (isset($arOldSetupVars['AVITO_APPAREL']))
		$avitoApparel = $arOldSetupVars['AVITO_APPAREL'];
	if (isset($arOldSetupVars['AVITO_SIZE']))
		$avitoSize = $arOldSetupVars['AVITO_SIZE'];
		if (isset($arOldSetupVars['AVITO_PRICE']))
		$avitoPrice = $arOldSetupVars['AVITO_PRICE'];
		if (isset($arOldSetupVars['AVITO_FILTER']))
		$avitoFilter = $arOldSetupVars['AVITO_FILTER'];
		if (isset($arOldSetupVars['AVITO_VIDEO']))
		$avitoVideo = $arOldSetupVars['AVITO_VIDEO'];
     if (isset($arOldSetupVars['AVITO_PHONE']))
		$avitoPhone = $arOldSetupVars['AVITO_PHONE'];
	if (isset($arOldSetupVars['AVITO_CATEGORY_CUSTOM']))
	$avitoCategoryCustom = $arOldSetupVars['AVITO_CATEGORY_CUSTOM'];
	if (isset($arOldSetupVars['AVITO_PRICE_MAX']))
		$avitoPriceMax = $arOldSetupVars['AVITO_PRICE_MAX'];

	if (isset($arOldSetupVars['CHECK_PERMISSIONS']))
		$checkPermissions = $arOldSetupVars['CHECK_PERMISSIONS'];

	if (isset($arOldSetupVars['FILTER_DATA']))
				$FILTER_DATA = $arOldSetupVars['FILTER_DATA'];
	if (isset($arOldSetupVars['FILTER_PREF']))
				$FILTER_PREF = $arOldSetupVars['FILTER_PREF'];
	if (isset($arOldSetupVars['FILTER_INPUT']))
				$FILTER_INPUT = $arOldSetupVars['FILTER_INPUT'];
    if (isset($arOldSetupVars['AVITO_TITLE_PROP']))
		$AVITO_TITLE_PROP = $arOldSetupVars['AVITO_TITLE_PROP'];
    if (isset($arOldSetupVars['AVITO_NOPHOTO']))
		$AVITO_NOPHOTO = $arOldSetupVars['AVITO_NOPHOTO'];
    if (isset($arOldSetupVars['AVITO_NODESCR']))
		$AVITO_NODESCR = $arOldSetupVars['AVITO_NODESCR'];
    if (isset($arOldSetupVars['AVITO_NODETAIL_PHOTO']))
		$AVITO_NODETAIL_PHOTO = $arOldSetupVars['AVITO_NODETAIL_PHOTO'];
    if (isset($arOldSetupVars['ABRICOS_AVITO_PHOTO']))
		$ABRICOS_AVITO_PHOTO = $arOldSetupVars['ABRICOS_AVITO_PHOTO'];
    if (isset($arOldSetupVars['AVITO_DESCR_PROP']))
		$AVITO_DESCR_PROP = $arOldSetupVars['AVITO_DESCR_PROP'];
    if (isset($arOldSetupVars['AVITO_STORE']))
		$AVITO_STORE = $arOldSetupVars['AVITO_STORE'];
    if (isset($arOldSetupVars['AVITO_TITLE_TEMPL']))
		$AVITO_TITLE_TEMPL = $arOldSetupVars['AVITO_TITLE_TEMPL'];
    if (isset($arOldSetupVars['AVITO_DESCR_TEMPL']))
		$AVITO_DESCR_TEMPL = $arOldSetupVars['AVITO_DESCR_TEMPL'];
    if (isset($arOldSetupVars['AVITO_DESCR_TEMPL_PL']))
		$AVITO_DESCR_TEMPL_PL = $arOldSetupVars['AVITO_DESCR_TEMPL_PL'];
    if (isset($arOldSetupVars['AVITO_PROP_PRICE']))
		$AVITO_PROP_PRICE = $arOldSetupVars['AVITO_PROP_PRICE'];
	if (isset($arOldSetupVars['AVITO_CONDITION_ALT']))
		$AVITO_CONDITION_ALT = $arOldSetupVars['AVITO_CONDITION_ALT'];
    	if (isset($arOldSetupVars['PROP_DATA']))
		$PROP_DATA = $arOldSetupVars['PROP_DATA'];
	if (isset($arOldSetupVars['PROP_NAME']))
		$PROP_NAME = $arOldSetupVars['PROP_NAME'];
	if (isset($arOldSetupVars['PROP_DEF']))
		$PROP_DEF = $arOldSetupVars['PROP_DEF'];
	if (isset($arOldSetupVars['DISPLAYAREAS']))
		$DISPLAYAREAS = $arOldSetupVars['DISPLAYAREAS'];
	if (isset($arOldSetupVars['AVITO_STOCK']))
		$AVITO_STOCK = $arOldSetupVars['AVITO_STOCK'];
	if (isset($arOldSetupVars['AVITO_STOCK_CONST']))
		$AVITO_STOCK_CONST = $arOldSetupVars['AVITO_STOCK_CONST'];
}

if ($STEP > 1)
{
	$IBLOCK_ID = (int)$IBLOCK_ID;
	$rsIBlocks = CIBlock::GetByID($IBLOCK_ID);
	if ($IBLOCK_ID <= 0 || !($arIBlock = $rsIBlocks->Fetch()))
	{
		$arSetupErrors[] = GetMessage("CET_ERROR_NO_IBLOCK1")." #".$IBLOCK_ID." ".GetMessage("CET_ERROR_NO_IBLOCK2");
	}
	else
	{
		$bRightBlock = !CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, "iblock_admin_display");
		if ($bRightBlock)
		{
			$arSetupErrors[] = str_replace('#IBLOCK_ID#',$IBLOCK_ID,GetMessage("CET_ERROR_IBLOCK_PERM"));
		}
	}

	$SITE_ID = trim($SITE_ID);
	if ($SITE_ID === '')
	{
		$arSetupErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_EMPTY_SITE');
	}
	else
	{
		$iterator = Main\SiteTable::getList(array(
			'select' => array('LID'),
			'filter' => array('=LID' => $SITE_ID, '=ACTIVE' => 'Y')
		));
		$site = $iterator->fetch();
		if (empty($site))
		{
			$arSetupErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SITE');
		}
	}

	if (!isset($SETUP_FILE_NAME) || $SETUP_FILE_NAME == '')
	{
		$arSetupErrors[] = GetMessage("CET_ERROR_NO_FILENAME");
	}
	elseif (preg_match(BX_CATALOG_FILENAME_REG, $strAllowExportPath.$SETUP_FILE_NAME))
	{
		$arSetupErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
	}
	elseif ($APPLICATION->GetFileAccessPermission($strAllowExportPath.$SETUP_FILE_NAME) < "W")
	{
		$arSetupErrors[] = str_replace("#FILE#", $strAllowExportPath.$SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_ACCESS_DENIED'));
	}

	$SETUP_SERVER_NAME = (isset($SETUP_SERVER_NAME) ? trim($SETUP_SERVER_NAME) : '');
	$COMPANY_NAME = (isset($COMPANY_NAME) ? trim($COMPANY_NAME) : '');

	if (empty($arSetupErrors))
	{
		$bAllSections = false;
		$arSections = array();
		if (!empty($V) && is_array($V))
		{
			foreach ($V as $key => $value)
			{
				if (trim($value) == "0")
				{
					$bAllSections = true;
					break;
				}
				$value = (int)$value;
				if ($value > 0)
					$arSections[] = $value;
			}
		}

		if (!$bAllSections && !empty($arSections))
		{
			$arCheckSections = array();
			$rsSections = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'ID' => $arSections), false, array('ID'));
			while ($arOneSection = $rsSections->Fetch())
			{
				$arCheckSections[] = $arOneSection['ID'];
			}
			$arSections = $arCheckSections;
		}

		if (!$bAllSections && empty($arSections))
		{
			$arSetupErrors[] = GetMessage("CET_ERROR_NO_GROUPS");
			$V = array();
		}
	}

	if (is_array($V))
	{
		$V = array_unique(array_values($V));
		$_REQUEST['V'] = $V;
	}

	$arCatalog = CCatalogSku::GetInfoByIBlock($IBLOCK_ID);
	if (CCatalogSku::TYPE_PRODUCT == $arCatalog['CATALOG_TYPE'] || CCatalogSku::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
	{
		if (!isset($XML_DATA) || $XML_DATA == '')
		{
			$arSetupErrors[] = GetMessage('YANDEX_ERR_SKU_SETTINGS_ABSENT');
		}
	}

	if (!isset($USE_HTTPS) || $USE_HTTPS != 'Y')
		$USE_HTTPS = 'N';
	if (isset($_POST['FILTER_AVAILABLE']) && is_string($_POST['FILTER_AVAILABLE']))
		$filterAvalable = $_POST['FILTER_AVAILABLE'];
	if (!isset($filterAvalable) || $filterAvalable != 'Y')
		$filterAvalable = 'N';
	if (isset($_POST['DISABLE_REFERERS']) && is_string($_POST['DISABLE_REFERERS']))
		$disableReferers = $_POST['DISABLE_REFERERS'];
	if (!isset($disableReferers) || $disableReferers != 'Y')
		$disableReferers = 'N';
	if (isset($_POST['MAX_EXECUTION_TIME']) && is_string($_POST['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $_POST['MAX_EXECUTION_TIME'];

	$maxExecutionTime = (!isset($maxExecutionTime) ? 0 : (int)$maxExecutionTime);
	if ($maxExecutionTime < 0)
		$maxExecutionTime = 0;

	if ($ACTION=="EXPORT_SETUP" || $ACTION=="EXPORT_EDIT" || $ACTION=="EXPORT_COPY")
	{
		if (!isset($SETUP_PROFILE_NAME) || $SETUP_PROFILE_NAME == '')
			$arSetupErrors[] = GetMessage("CET_ERROR_NO_PROFILE_NAME");
	}

	if (!empty($arSetupErrors))
	{
		$STEP = 1;
	}
}

$aMenu = array(
	array(
		"TEXT"=>GetMessage("CATI_ADM_RETURN_TO_LIST"),
		"TITLE"=>GetMessage("CATI_ADM_RETURN_TO_LIST_TITLE"),
		"LINK"=>"/bitrix/admin/cat_export_setup.php?lang=".LANGUAGE_ID,
		"ICON"=>"btn_list",
	)
);

$context = new CAdminContextMenu($aMenu);

$context->Show();

if (!empty($arSetupErrors))
	ShowError(implode('<br>', $arSetupErrors));
$actionParams = "";
?>
<?
CJSCore::Init(array("jquery"));
 ?>
<!--suppress JSUnresolvedVariable -->
<form method="post" action="<?echo $APPLICATION->GetCurPage().$actionParams ?>" name="yandex_setup_form" id="yandex_setup_form">
<?
$aTabs = array(
	array("DIV" => "yand_edit1", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB1_TITLE")),
	array("DIV" => "yand_edit2", "TAB" => GetMessage("CAT_ADM_MISC_EXP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_MISC_EXP_TAB2_TITLE")),
);

$tabControl = new CAdminTabControl("tabYandex", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();

if ($STEP == 1)
{
	if (!isset($SITE_ID))
		$SITE_ID = '';
	if (!isset($XML_DATA))
		$XML_DATA = '';
	if (!isset($filterAvalable) || $filterAvalable != 'Y')
		$filterAvalable = 'N';
	if (!isset($USE_HTTPS) || $USE_HTTPS != 'Y')
		$USE_HTTPS = 'N';
	if (!isset($disableReferers) || $disableReferers != 'Y')
		$disableReferers = 'N';
	if (!isset($SETUP_SERVER_NAME))
		$SETUP_SERVER_NAME = '';
	if (!isset($COMPANY_NAME))
		$COMPANY_NAME = '';
	if (!isset($SETUP_FILE_NAME))
		$SETUP_FILE_NAME = 'avito_'.mt_rand(0, 999999).'.php';
	if (!isset($checkPermissions) || $checkPermissions != 'Y')
		$checkPermissions = 'N';

	$siteList = array();
	$iterator = Main\SiteTable::getList(array(
		'select' => array('LID', 'NAME', 'SORT'),
		'filter' => array('=ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC')
	));
	while ($row = $iterator->fetch())
		$siteList[$row['LID']] = $row['NAME'];
	unset($row, $iterator);
	$iblockIds = array();
	$iblockSites = array();
	$iblockMultiSites = array();
	$iterator = Catalog\CatalogIblockTable::getList(array(
		'select' => array(
			'IBLOCK_ID',
			'PRODUCT_IBLOCK_ID',
			'IBLOCK_ACTIVE' => 'IBLOCK.ACTIVE',
			'PRODUCT_IBLOCK_ACTIVE' => 'PRODUCT_IBLOCK.ACTIVE'
		),
		'filter' => array('')
	));
	while ($row = $iterator->fetch())
	{
		$row['PRODUCT_IBLOCK_ID'] = (int)$row['PRODUCT_IBLOCK_ID'];
		$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
		if ($row['PRODUCT_IBLOCK_ID'] > 0)
		{
			if ($row['PRODUCT_IBLOCK_ACTIVE'] == 'Y')
				$iblockIds[$row['PRODUCT_IBLOCK_ID']] = true;
		}
		else
		{
			if ($row['IBLOCK_ACTIVE'] == 'Y')
				$iblockIds[$row['IBLOCK_ID']] = true;
		}
	}
	unset($row, $iterator);
	if (!empty($iblockIds))
	{
		$activeIds = array();
		$iterator = Iblock\IblockSiteTable::getList(array(
			'select' => array('IBLOCK_ID', 'SITE_ID', 'SITE_SORT' => 'SITE.SORT'),
			'filter' => array('@IBLOCK_ID' => array_keys($iblockIds), '=SITE.ACTIVE' => 'Y'),
			'order' => array('IBLOCK_ID' => 'ASC', 'SITE_SORT' => 'ASC')
		));
		while ($row = $iterator->fetch())
		{
			$id = (int)$row['IBLOCK_ID'];

			if (!isset($iblockSites[$id]))
				$iblockSites[$id] = array(
					'ID' => $id,
					'SITES' => array()
				);
			$iblockSites[$id]['SITES'][] = array(
				'ID' => $row['SITE_ID'],
				'NAME' => $siteList[$row['SITE_ID']]
			);

			if (!isset($iblockMultiSites[$id]))
				$iblockMultiSites[$id] = false;
			else
				$iblockMultiSites[$id] = true;

			$activeIds[$id] = true;
		}
		unset($id, $row, $iterator);
		if (empty($activeIds))
		{
			$iblockIds = array();
			$iblockSites = array();
			$iblockMultiSites = array();
		}
		else
		{
			$iblockIds = array_intersect_key($iblockIds, $activeIds);
		}
		unset($activeIds);
	}
	if (empty($iblockIds))
	{

	}

	$currentList = array();
	if ($IBLOCK_ID > 0 && isset($iblockIds[$IBLOCK_ID]))
	{
		$currentList = $iblockSites[$IBLOCK_ID]['SITES'];
		if ($SITE_ID === '')
		{
			$firstSite = reset($currentList);
			$SITE_ID = $firstSite['ID'];
		}
	}

?>
<? if(CModule::IncludeModuleEx("abricos.avitoautoload")==2) {?>
<tr><td colspan=2><div align="center"><?=GetMessage("DEMO")?></div></td></tr>
<? }?>
<tr>
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_IBLOCK'); ?></td>
	<td width="60%"><?
	echo GetIBlockDropDownListEx(
		$IBLOCK_ID, 'IBLOCK_TYPE_ID', 'IBLOCK_ID',
		array(
			'ID' => array_keys($iblockIds),
			'CHECK_PERMISSIONS' => 'Y',
			'MIN_PERMISSION' => 'U'
		),
		"ClearSelected(); changeIblockSites(0); BX('id_ifr').src='/bitrix/tools/catalog_export/yandex_util.php?IBLOCK_ID=0&'+'".bitrix_sessid_get()."';",
		"ClearSelected(); changeIblockSites(this[this.selectedIndex].value); BX('id_ifr').src='/bitrix/tools/catalog_export/yandex_util.php?IBLOCK_ID='+this[this.selectedIndex].value+'&'+'".bitrix_sessid_get()."';",
		'class="adm-detail-iblock-types"',
		'class="adm-detail-iblock-list"'
	);
	?>
		<script type="text/javascript">
		var TreeSelected = [];
		<?
		$intCountSelected = 0;
		if (!empty($V) && is_array($V))
		{
			foreach ($V as $oneKey)
			{
				?>TreeSelected[<? echo $intCountSelected ?>] = <? echo (int)$oneKey; ?>;
				<?
				$intCountSelected++;
			}
		}
		?>
		function ClearSelected()
		{
			BX.showWait();
			TreeSelected = [];
		}
		</script>
	</td>
</tr>
<tr id="tr_SITE_ID" style="display: <?=(count($currentList) > 1 ? 'table-row' : 'none' ); ?>;">
	<td width="40%"><?=GetMessage('BX_CATALOG_EXPORT_YANDEX_SITE'); ?></td>
	<td width="60%">
		<script type="text/javascript">
		function changeIblockSites(iblockId)
		{
			var iblockSites = <?=CUtil::PhpToJSObject($iblockSites); ?>,
				iblockMultiSites = <?=CUtil::PhpToJSObject($iblockMultiSites); ?>,
				tableRow = null,
				siteControl = null,
				i,
				currentSiteList;

			tableRow = BX('tr_SITE_ID');
			siteControl = BX('SITE_ID');
			if (!BX.type.isElementNode(tableRow) || !BX.type.isElementNode(siteControl))
				return;

			for (i = siteControl.length-1; i >= 0; i--)
				siteControl.remove(i);
			if (typeof(iblockSites[iblockId]) !== 'undefined')
			{
				currentSiteList = iblockSites[iblockId]['SITES'];
				for (i = 0; i < currentSiteList.length; i++)
				{
					siteControl.appendChild(BX.create(
						'option',
						{
							props: {value: BX.util.htmlspecialchars(currentSiteList[i].ID)},
							html: BX.util.htmlspecialchars('[' + currentSiteList[i].ID + '] ' + currentSiteList[i].NAME)
						}
					));
				}
			}
			if (siteControl.length > 0)
				siteControl.selectedIndex = 0;
			else
				siteControl.selectedIndex = -1;
			BX.style(tableRow, 'display', (siteControl.length > 1 ? 'table-row' : 'none'));

  $.ajax({
 type:'POST',
 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
 dataType:"text",
 data:"iblockId="+iblockId,
 success:function(html) {
 	var tbody = document.getElementById('AVITO_FILTER_tbl').getElementsByTagName('TBODY')[0];
 	tbody.innerHTML=html;
 }
 });
$.ajax({
	 type:'POST',
	 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
	 dataType:"text",
	 data:"propId="+iblockId,
	 success:function(html00) {
	 	var tbody = document.getElementById('AVITO_PROP_tbl').getElementsByTagName('TBODY')[0];
	 	tbody.innerHTML=html00;
	 }
	 });
 $.ajax({
 type:'POST',
 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
 dataType:"text",
 data:"iblId="+iblockId,
 success:function(html2) {
 	var tbody2 = document.getElementById('photoId');
 	tbody2.innerHTML=html2;
 }
 });
  $.ajax({
 type:'POST',
 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
 dataType:"text",
 data:"IbTitleId="+iblockId,
 success:function(html3) {
 	var tbody3 = document.getElementById('titleId');
 	tbody3.innerHTML=html3;
 }
 });
 $.ajax({
 type:'POST',
 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
 dataType:"text",
 data:"IbVideoId="+iblockId,
 success:function(html4) {
 	var tbody4 = document.getElementById('videoId');
 	tbody4.innerHTML=html4;
 }
 });
  $.ajax({
 type:'POST',
 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
 dataType:"text",
 data:"descrId="+iblockId,
 success:function(html5) {
 	var tbody5 = document.getElementById('descrId');
 	tbody5.innerHTML=html5;
 }
 });
 $.ajax({
				 type:'POST',
				 url:'/bitrix/tools/abricos.avitoautoload/avito_ajax.php',
				 dataType:"text",
				 data:"prId="+iblockId,
				 success:function(html6) {
				 	var tbody6 = document.getElementById('priceId');
				 	tbody6.innerHTML=html6;
				 }
				 });
     }
		</script>
		<select id="SITE_ID" name="SITE_ID">
		<?
		foreach ($currentList as $site)
		{
			$selected = ($site['ID'] == $SITE_ID ? ' selected' : '');
			$name = '['.$site['ID'].'] '.$site['NAME'];
			?><option value="<?=htmlspecialcharsbx($site['ID']); ?>"<?=$selected; ?>><?=htmlspecialcharsbx($name); ?></option><?
		}
		unset($name, $selected, $site);
		?>
		</select>
	</td>
</tr>
<tr>
	<td width="40%" valign="top"><?echo GetMessage("CET_SELECT_GROUP");?></td>
	<td width="60%"><?
	if ($intCountSelected)
	{
		foreach ($V as $oneKey)
		{
			$oneKey = (int)$oneKey;
			?><input type="hidden" value="<? echo $oneKey; ?>" name="V[]" id="oldV<? echo $oneKey; ?>"><?
		}
		unset($oneKey);
	}
	?><div id="tree"></div>
	<script type="text/javascript">
	BX.showWait();
	clevel = 0;

	function delOldV(obj)
	{
		if (!!obj)
		{
			var intSelKey = BX.util.array_search(obj.value, TreeSelected);
			if (obj.checked == false)
			{
				if (-1 < intSelKey)
				{
					TreeSelected = BX.util.deleteFromArray(TreeSelected, intSelKey);
				}

				var objOldVal = BX('oldV'+obj.value);
				if (!!objOldVal)
				{
					objOldVal.parentNode.removeChild(objOldVal);
					objOldVal = null;
				}
			}
			else
			{
				if (-1 == intSelKey)
				{
					TreeSelected[TreeSelected.length] = obj.value;
				}
			}
		}
	}

	function buildNoMenu()
	{
		var buffer;
		buffer = '<?echo GetMessageJS("CET_FIRST_SELECT_IBLOCK");?>';
		BX('tree', true).innerHTML = buffer;
		BX.closeWait();
	}

	function buildMenu()
	{
		var i,
			buffer,
			imgSpace,
			space;

		buffer = '<table border="0" cellspacing="0" cellpadding="0">';
		buffer += '<tr>';
		buffer += '<td colspan="2" valign="top" align="left"><input type="checkbox" name="V[]" value="0" id="v0"'+(BX.util.in_array(0,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="v0"><font class="text"><b><?echo CUtil::JSEscape(GetMessage("CET_ALL_GROUPS"));?></b></font></label></td>';
		buffer += '</tr>';

		for (i in Tree[0])
		{
			if (!Tree[0][i])
			{
				space = '<input type="checkbox" name="V[]" value="'+i+'" id="V'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="V'+i+'"><span class="text">' + Tree[0][i][0] + '</span></label>';
				imgSpace = '';
			}
			else
			{
				space = '<input type="checkbox" name="V[]" value="'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><a href="javascript: collapse(' + i + ')"><span class="text"><b>' + Tree[0][i][0] + '</b></span></a>';
				imgSpace = '<img src="/bitrix/images/catalog/load/plus.gif" width="13" height="13" id="img_' + i + '" OnClick="collapse(' + i + ')">';
			}

			buffer += '<tr>';
			buffer += '<td width="20" valign="top" align="center">' + imgSpace + '</td>';
			buffer += '<td id="node_' + i + '">' + space + '</td>';
			buffer += '</tr>';
		}

		buffer += '</table>';

		BX('tree', true).innerHTML = buffer;
		BX.adminPanel.modifyFormElements('yandex_setup_form');
		BX.closeWait();
	}

	function collapse(node)
	{
		if (!BX('table_' + node))
		{
			var i,
				buffer,
				imgSpace,
				space;

			buffer = '<table border="0" id="table_' + node + '" cellspacing="0" cellpadding="0">';

			for (i in Tree[node])
			{
				if (!Tree[node][i])
				{
					space = '<input type="checkbox" name="V[]" value="'+i+'" id="V'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><label for="V'+i+'"><font class="text">' + Tree[node][i][0] + '</font></label>';
					imgSpace = '';
				}
				else
				{
					space = '<input type="checkbox" name="V[]" value="'+i+'"'+(BX.util.in_array(i,TreeSelected) ? ' checked' : '')+' onclick="delOldV(this);"><a href="javascript: collapse(' + i + ')"><font class="text"><b>' + Tree[node][i][0] + '</b></font></a>';
					imgSpace = '<img src="/bitrix/images/catalog/load/plus.gif" width="13" height="13" id="img_' + i + '" OnClick="collapse(' + i + ')">';
				}

				buffer += '<tr>';
				buffer += '<td width="20" align="center" valign="top">' + imgSpace + '</td>';
				buffer += '<td id="node_' + i + '">' + space + '</td>';
				buffer += '</tr>';
			}

			buffer += '</table>';

			BX('node_' + node).innerHTML += buffer;
			BX('img_' + node).src = '/bitrix/images/catalog/load/minus.gif';
		}
		else
		{
			var tbl = BX('table_' + node);
			tbl.parentNode.removeChild(tbl);
			BX('img_' + node).src = '/bitrix/images/catalog/load/plus.gif';
		}
		BX.adminPanel.modifyFormElements('yandex_setup_form');
	}
	</script>
	<iframe src="/bitrix/tools/catalog_export/yandex_util.php?IBLOCK_ID=<?=intval($IBLOCK_ID)?>&<? echo bitrix_sessid_get(); ?>" id="id_ifr" name="ifr" style="display:none"></iframe>
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('CAT_DETAIL_PROPS')?>:</td>
	<td width="60%">
		<script type="text/javascript">
		function showDetailPopup()
		{
			if (!obDetailWindow)
			{
				var s = BX('IBLOCK_ID');
				var dat = BX('XML_DATA');
				var obDetailWindow = new BX.CAdminDialog({
					'content_url': '/bitrix/tools/abricos.avitoautoload/avito_detail.php?lang=<?=LANGUAGE_ID?>&bxpublic=Y&IBLOCK_ID=' + s[s.selectedIndex].value,
					'content_post': 'XML_DATA='+BX.util.urlencode(dat.value)+'&'+'<?echo bitrix_sessid_get(); ?>',
					'width': 900, 'height': 550,
					'resizable': true
				});
				obDetailWindow.Show();
			}
		}

		function setDetailData(data)
		{
			BX('XML_DATA').value = data;
		}
		</script>
<script type="text/javascript">
$(document).ready(function(){
 $("input#customCheck").change(function(){

  if ($(this).attr("checked")) {
      $('.category_block').fadeIn().hide();
      $('.apparel_block').fadeIn().hide();
      $('.size_block').fadeIn().hide();
      $('.cond_block').fadeIn().hide();
            $('.cond_blockalt2').show();
            $('.cond_blockalt').hide();
      return;
  } else {
      $('.category_block').show();
      $('.cond_block').show();
      $('.cond_blockalt2').hide();
      $('.cond_blockalt').show();
  }

 });
})

</script>
		<input type="button" onclick="showDetailPopup(); return false;" value="<? echo GetMessage('CAT_DETAIL_PROPS_RUN'); ?>">
		<input type="hidden" id="XML_DATA" name="XML_DATA" value="<?=htmlspecialcharsbx($XML_DATA); ?>">
	</td>
</tr>

 <tr class="heading">
	<td colspan="2"><?=GetMessage('AVITOMARKET_FILTERS'); ?></td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('CAT_YANDEX_CHECK_PERMISSIONS'); ?></td>
	<td width="60%">
		<input type="hidden" name="CHECK_PERMISSIONS" value="N">
		<input type="checkbox" name="CHECK_PERMISSIONS" value="Y"<?=($checkPermissions == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('CAT_YANDEX_FILTER_AVAILABLE'); ?></td>
	<td width="60%">
		<input type="hidden" name="FILTER_AVAILABLE" value="N">
		<input type="checkbox" name="FILTER_AVAILABLE" value="Y"<? echo ($filterAvalable == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('AVITO_PRICE'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_PRICE" value='<?=$avitoPrice?>'>
    </td>
</tr>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('FILTER_PRICE_MAX'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_PRICE_MAX" value='<?=$avitoPriceMax?>'>
    </td>
</tr>

<tr class="price_block">
	<td width="40%"><? echo GetMessage('AVITO_STORE'); ?></td>
	<td width="60%">
      <div class="form_label">
		<select multiple="multiple" size=1 name="AVITO_STORE[]" id='selRegs'>
		<option value=""><? echo GetMessage('AVITO_SKIP_PROP'); ?></option>
<?
	$dbResult = CCatalogStore::GetList(
	array(),array("ACTIVE" => "Y"),false,false, array("ID","TITLE")
	);
	$i=0;
	while($arStore=$dbResult->Fetch())
	{   $selectM='';
	    if($AVITO_STORE and in_array($arStore["ID"],$AVITO_STORE))
	    $selectM='selected';
	    echo '<option value="'.$arStore["ID"].'" '.$selectM.'>'.$arStore["ID"].' '.$arStore["TITLE"].'</option>';
	    $i++;
	    }
?>
</select>
<?if($i>5)
 $i=5;
?>
<script>
$(document).ready(function () {
	$('#selRegs').attr("size", "<?=$i+1;?>");
	 });
</script>

    </td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("AVITO_STORE_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_NOPHOTO'); ?></td>
	<td width="60%">
		<input type="hidden" name="AVITO_NOPHOTO" value="N">
		<input type="checkbox" name="AVITO_NOPHOTO" value="Y"<? echo ($AVITO_NOPHOTO == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_NODESCR'); ?></td>
	<td width="60%">
		<input type="hidden" name="AVITO_NODESCR" value="N">
		<input type="checkbox" name="AVITO_NODESCR" value="Y"<? echo ($AVITO_NODESCR == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<? if($avitoFilter>0)
{?>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('AVITO_FILTER'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_FILTER" value='<?=$avitoFilter?>'>
    </td>
</tr>
<? } ?>

<tr>
<td colspan=2 align="center">
<?
if($IBLOCK_ID>0)
{?>
<table class="inner" id="AVITO_FILTER_tbl">
					<thead>
					<tr><td colspan=2><b><? echo GetMessage('AVITO_FILTER_TITLE'); ?></b></td>
					</tr>
					</thead>
					<tbody>
<?
$dbRes = CIBlockProperty::GetList(
	array('SORT' => 'ASC'),
	array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
);
$arIBlock['PROPERTY'] = array();
$arIBlock['OFFERS_PROPERTY'] = array();
while ($arRes = $dbRes->Fetch())
{
	$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
}
if ($boolOffers)
{
	$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
	while ($arProp = $rsProps->Fetch())
	{
		if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
		{
			if ($arProp['PROPERTY_TYPE'] == 'L')
			{
				$arProp['VALUES'] = array();
				$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
				while ($arPropEnum = $rsPropEnums->Fetch())
				{
					$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
				}
			}
			$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
			if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
				$arSelectOfferProps[] = $arProp['ID'];
		}
	}
}
						$intCount = 0;
						if($FILTER_DATA)
						{
							foreach ($FILTER_DATA as $arParamDetail)
							{
								echo CAbricosAvitoautoload::addParamRow($arIBlock, $intCount, $arParamDetail, '',$FILTER_PREF[$intCount],$FILTER_INPUT[$intCount]);
								?>

								<?
								$intCount++;
							}
						}
						if ($intCount == 0)
						{
							echo CAbricosAvitoautoload::addParamRow($arIBlock, $intCount, '', '','','');
							$intCount++;
						}

						?>

                  </tbody>
				</table>
				<input type="hidden" name="FILTER_COUNT" id="FILTER_COUNT" value="<? echo $intCount; ?>">
				<div style="width: 100%; text-align: center;"><input type="button" onclick="addYP2(); return false;" name="AVITO_FILTER_add" value="<? echo GetMessage('AVITO_FILTER_ADDITIONAL_MORE'); ?>"></div>
 <? }
 else {?>
 <table class="inner" id="AVITO_FILTER_tbl">
					<thead>
					<tr><td colspan=2><b><? echo GetMessage('AVITO_FILTER_TITLE'); ?></b></td>
					</tr>
					</thead>
					<tbody id="filterRow">

  </tbody>
				</table>
				<input type="hidden" name="FILTER_COUNT" id="FILTER_COUNT" value="<? echo $intCount; ?>">
				<div style="width: 100%; text-align: center;"><input type="button" onclick="addYP2(); return false;" name="AVITO_FILTER_add" value="<? echo GetMessage('AVITO_FILTER_ADDITIONAL_MORE'); ?>"></div>
 <?}?>

	</td>
</tr>
<? if(!$intCount) $intCount=1; ?>
<script type="text/javascript">
BX.ready(function(){
		setTimeout(function(){
			window.oParamSet = {
				pTypeTbl: BX("AVITO_FILTER_tbl"),
				curCount: <? echo ($intCount); ?>,
				intCounter: BX("FILTER_COUNT")

			};
		},50);
});
function addYP2()
{
	var id = window.oParamSet.curCount++,
		newRow,
		oCell,
		strContent;
	id = id.toString();
	window.oParamSet.intCounter.value = window.oParamSet.curCount;
	newRow = window.oParamSet.pTypeTbl.insertRow(window.oParamSet.pTypeTbl.rows.length);
	newRow.id = 'AVITO_FILTER_tbl_'+id;
	oCell = newRow.insertCell(-1);
	<?
	if($IBLOCK_ID>0)
	{	?>
		strContent = '<? echo CUtil::JSEscape(CAbricosAvitoautoload::addParamName($arIBlock, 'tmp_xxx', '')); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;

	<?}
	else {
	?>
		strContent = document.getElementById('AVITO_FILTER_tbl_0').getElementsByTagName('td')[0].innerHTML;
	    strContent = strContent.replace('FILTER_DATA[0]', 'FILTER_DATA['+id+']');
		oCell.innerHTML = strContent;
	<? }?>
	   	oCell = newRow.insertCell(-1);
		strContent = '<?=CAbricosAvitoautoload::addPrefRow($intCount)?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;

		oCell = newRow.insertCell(-1);
		strContent = '<?=CAbricosAvitoautoload::addInputRow($intCount)?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
	}

</script>
<tr class="heading">
	<td colspan="2"><?=GetMessage('AVITOMARKET_ITEMS'); ?></td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('AVITO_PROP_PRICE'); ?></td>
	<td width="60%" id="priceId">
	<?
	if($IBLOCK_ID>0)
	{?>
	<?=CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_PROP_PRICE',$AVITO_PROP_PRICE);?>
	<?}?>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_NODETAIL_PHOTO'); ?></td>
	<td width="60%">
		<input type="hidden" name="AVITO_NODETAIL_PHOTO" value="N">
		<input type="checkbox" name="AVITO_NODETAIL_PHOTO" value="Y"<? echo ($AVITO_NODETAIL_PHOTO == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('ABRICOS_AVITO_PHOTO'); ?></td>
	<td width="60%" id="photoId">
	<?
	if($IBLOCK_ID>0)
	{?>
	<?=CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'ABRICOS_AVITO_PHOTO',$ABRICOS_AVITO_PHOTO);?>
	<?}?>
	</td>
</tr>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('AVITO_VIDEO'); ?></td>
	<td width="60%" id="videoId">
	<?
	if($IBLOCK_ID>0)
	{?>
    <?=CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_VIDEO',$avitoVideo);?>
    <?}?>
    </td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('AVITO_TITLE_PROP'); ?></td>
	<td width="60%" id="titleId">
		<?
	if($IBLOCK_ID>0)
	{?>
    <?=CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_TITLE_PROP',$AVITO_TITLE_PROP);?>
    <?}?>
	</td>
</tr>
<tr>
	<td width="40%"><?=GetMessage('AVITO_DESCR_PROP'); ?></td>
	<td width="60%" id="descrId">
		<?
	if($IBLOCK_ID>0)
	{?>
    <?=CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_DESCR_PROP',$AVITO_DESCR_PROP);?>
    <?}?>
	</td>
</tr>
<script>
function first() {
document.getElementById("second_hide").setAttribute("style", "opacity:1; transition: 1s; height: 100%;");
document.getElementById("tree_hide").setAttribute("style", "opacity:1; transition: 1s; height: 100%;");
document.getElementById("for_hide").setAttribute("style", "opacity:1; transition: 1s; height: 100%;");
document.getElementById("first").setAttribute("style", "display: none");

document.getElementById("first_yelloy").setAttribute("style", "display: block");

}

function first_yelloy() {

document.getElementById("second_hide").setAttribute("style", "display: none");
document.getElementById("tree_hide").setAttribute("style", "display: none");
document.getElementById("for_hide").setAttribute("style", "display: none");
document.getElementById("first_yelloy").setAttribute("style", "display: none");

document.getElementById("first").setAttribute("style", "display: block");

}

</script>
<tr>
	<td colspan="2" align="center">
	<p id="first" onclick="first()"><?=GetMessage('AVITO_TITLE_GEN'); ?></p>
	<p id="first_yelloy"; style="display:none" onclick="first_yelloy()"><?=GetMessage('AVITO_TITLE_GEN_CLOSE'); ?></p>
	</td>
</tr>
<tr <? if(!$AVITO_TITLE_TEMPL and !$AVITO_DESCR_TEMPL) {?>id="second_hide" style="display:none" <?}?>>
	<td width="40%"><?=GetMessage('AVITO_TITLE_TEMPL'); ?></td>
	<td width="60%">
	<textarea name="AVITO_TITLE_TEMPL" rows="2" cols="40" ><?
	if($AVITO_TITLE_TEMPL)
	  echo $AVITO_TITLE_TEMPL;
	?></textarea>
	</td>
</tr>
<tr <? if(!$AVITO_TITLE_TEMPL and !$AVITO_DESCR_TEMPL) {?>id="tree_hide" style="display:none" <?}?>>
	<td width="40%"><?=GetMessage('AVITO_DESCR_TEMPL'); ?></td>
	<td width="60%" >
	<textarea name="AVITO_DESCR_TEMPL" rows="5" cols="40" ><?
	if($AVITO_DESCR_TEMPL)
	  echo $AVITO_DESCR_TEMPL;
	else echo $AVITO_DESCR_TEMPL0;
	?></textarea>
<br />
	<small><?=GetMessage('AVITO_TITLE_TEMPL_NOTE'); ?></small>
	</td>
</tr>
<tr <? if(!$AVITO_TITLE_TEMPL and !$AVITO_DESCR_TEMPL) {?>id="for_hide" style="display:none" <?}?>>
	<td width="40%"><?=GetMessage('AVITO_DESCR_TEMPL_PL'); ?></td>
	<td width="60%" >
	<input type="checkbox" name="AVITO_DESCR_TEMPL_PL" value="Y"<? echo ($AVITO_DESCR_TEMPL_PL == 'Y' ? ' checked' : ''); ?>>
<br />
	<small><?=GetMessage('AVITO_DESCR_TEMPL_NOTE_PL'); ?></small>
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><?=GetMessage('AVITOPARAM_ALL'); ?></td>
</tr>
<? if(!$avitoCategoryCustom){?>
<tr>
	<td colspan="2"><?=GetMessage('AVITOPARAM_ALL_NOTE'); ?></td>
</tr>
<tr>
<td colspan=2 align="center">
<?
if($IBLOCK_ID>0)
{?>
<table class="inner" id="AVITO_PROP_tbl">
<thead>
<tr><td colspan=2><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE'); ?></b></div></td>
</tr>
</thead>
<tbody>
<tr><td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_NAME'); ?></b></div></td>
<td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_PROP'); ?></b></div></td>
<td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_DEFAULT'); ?></b></div></td>
</tr>

<?
$dbRes = CIBlockProperty::GetList(
	array('SORT' => 'ASC'),
	array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
);
$arIBlock['PROPERTY'] = array();
while ($arRes = $dbRes->Fetch())
{
	$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
}
$intCountProp = 0;

if($PROP_DATA)
{
	foreach ($PROP_DATA as $arParamDetail)
	{
		echo CAbricosAvitoautoload::addPropRow($arIBlock, $intCountProp, $arParamDetail, '',$PROP_NAME[$intCountProp],$PROP_DEF[$intCountProp]);
		$intCountProp++;
	}
}
if ($intCountProp == 0)
{
	echo CAbricosAvitoautoload::addPropRow($arIBlock, 0, '', '','','');
}
?>
</tbody>
</table>
<input type="hidden" name="PROP_COUNT" id="PROP_COUNT" value="<? echo $intCountProp; ?>">
<div style="width: 100%; text-align: center;"><input type="button" onclick="addYP3(); return false;" name="AVITO_PROP_add" value="<? echo GetMessage('AVITO_FILTER_ADDITIONAL_MORE'); ?>"></div>
 <? }
 else {?>
 <table class="inner" id="AVITO_PROP_tbl">
	<thead>
	<tr><td colspan=2><b><? echo GetMessage('AVITO_PROP_TITLE'); ?></b></td>
	</tr>
	<tr><td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_NAME'); ?></b></div></td>
<td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_PROP'); ?></b></div></td>
<td><div align="center"><b><? echo GetMessage('AVITO_PROP_TITLE_DEFAULT'); ?></b></div></td>
</tr>
	</thead>
	<tbody id="propRow">
  </tbody>
 </table>
				<input type="hidden" name="PROP_COUNT" id="PROP_COUNT" value="<? echo $intCountProp; ?>">
				<div style="width: 100%; text-align: center;"><input type="button" onclick="addYP3(); return false;" name="AVITO_PROP_add" value="<? echo GetMessage('AVITO_FILTER_ADDITIONAL_MORE'); ?>"></div>
 <?}?>
	</td>
</tr>
<? if(!$intCountProp) $intCountProp=1; ?>
 <script>
BX.ready(function(){
			setTimeout(function(){
			window.oParamSetProp = {
				pTypeTbl: BX("AVITO_PROP_tbl"),
				curCount: <? echo ($intCountProp); ?>,
				intCounter: BX("PROP_COUNT")

			};
		},50);
});
function addYP3()
{
	var id = window.oParamSetProp.curCount++,
		newRow,
		oCell,
		strContent;
	id = id.toString();
	window.oParamSetProp.intCounter.value = window.oParamSetProp.curCount;
	newRow = window.oParamSetProp.pTypeTbl.insertRow(window.oParamSetProp.pTypeTbl.rows.length);
	newRow.id = 'AVITO_PROP_tbl_'+id;
		oCell = newRow.insertCell(-1);
		strContent = '<input name="PROP_NAME['+id+']" type="text" value="" placeholder="<?=GetMessage('PROP_NAME_PLACEHOLD')?>">';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
	oCell = newRow.insertCell(-1);

	<?
	if($IBLOCK_ID>0)
	{	?>
		strContent = '<?=CUtil::JSEscape(CAbricosAvitoautoload::addPropName($arIBlock, 'tmp_xxx', '')); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
	<?}
	else {
	?>
		strContent = document.getElementById('AVITO_PROP_tbl_0').getElementsByTagName('td')[1].innerHTML;
	    strContent = strContent.replace('PROP_DATA[0]', 'PROP_DATA['+id+']');
		oCell.innerHTML = strContent;
	<? }?>

		oCell = newRow.insertCell(-1);
		strContent = '<input name="PROP_DEF['+id+']" type="text" value="">';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
	}
	</script>
<?}?>

<? if($avitoCategoryCustom){?>
<tr >
	<td width="40%"><b><? echo GetMessage('AVITO_CATEGORY_CUSTOM'); ?></b></td>
	<td width="60%">
		<input type="hidden" name="AVITO_CATEGORY_CUSTOM" value="N">
		<input type="checkbox" id="customCheck" name="AVITO_CATEGORY_CUSTOM" value="Y"<? echo ($avitoCategoryCustom == 'Y' ? ' checked' : ''); ?>>   </td>
</tr>
<?}?>
<? if($avitoCategory){?>
<tr class="category_block" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>>
<script src="/bitrix/js/abricos/linkedselect.js"></script>
	<td width="40%"><? echo GetMessage('AVITO_CATEGORY'); ?></td>
	<td width="60%">
	<?
	echo CAbricosAvitoautoload::categoryBlock($avitoCategory);
	?>
</td>
</tr>
<tr class="category_block" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>>
	<td width="40%"><? echo GetMessage('AVITO_CATEGORY_TYPE'); ?></td>
	<td width="60%">
		<select name="AVITO_CATEGORY_TYPE" id="List2" autocomplete="off"></select>
    </td>
</tr>
<tr  class="apparel_block" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>>
	<td width="40%"><? echo GetMessage('AVITO_APPAREL'); ?></td>
	<td width="60%">
		<select name="AVITO_APPAREL" id="List3" class="list_comm"></select>
    </td>
</tr>
<tr class="size_block" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>>
	<td width="40%"><? echo GetMessage('AVITO_SIZE'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_SIZE" value='<?=$avitoSize?>'>
    </td>
</tr>
<?}?>
<? if($avitoCondition){?>
<tr class="cond_block" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>>
	<td width="40%"><? echo GetMessage('AVITO_CONDITION'); ?></td>
	<td width="60%">
		<select  name="AVITO_CONDITION" >
		<option value="zero" <?if($avitoCondition=='zero') echo 'selected'?>><? echo GetMessage('AVITO_CONDITION_NONE'); ?></option>
        <option value="new" <?if($avitoCondition=='new') echo 'selected'?>><? echo GetMessage('AVITO_CONDITION_NEW'); ?></option>
        <option value="new2" <?if($avitoCondition=='new2') echo 'selected'?>><? echo GetMessage('AVITO_CONDITION_NEW2'); ?></option>
        <option value="bu" <?if($avitoCondition=='bu') echo 'selected'?>><? echo GetMessage('AVITO_CONDITION_BU'); ?></option>
    </td>
</tr>
<tr >
	<td width="40%"><? echo GetMessage('AVITO_CONDITION'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_CONDITION_ALT" value='<?=$AVITO_CONDITION_ALT?>'>

    </td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><div class="cond_blockalt" <? echo ($avitoCategoryCustom == 'Y' ? 'style="display:none"' : ''); ?>><small><?=GetMessage("AVITO_CONDITION_ALT_NOTE"); ?></small></div>
	<div class="cond_blockalt2" <? echo (($avitoCategoryCustom == 'N' or  $avitoCategoryCustom=='') ? 'style="display:none"' : ''); ?>><small><?=GetMessage("AVITO_CONDITION_ALT_NOTE2"); ?></small></div></td>
</tr>
<script type="text/javascript">
var syncList1 = new syncList;
syncList1.dataList = {
  <?=CAbricosAvitoautoload::categoryTypeBlock()?>
};
syncList1.sync("List1","List2","List3");
</script>
 <script>
$("#List2 [value='<?=$avitoCategoryType?>']").attr("selected", "selected");
$("#List3").find("option:contains('<?=$avitoApparel?>')").attr("selected", true);
    window.onload = Selected();
	function Selected() {
		if(document.getElementsByClassName("list_comm")[0].textContent == "") {
			document.getElementsByClassName("apparel_block")[0].style.display = "none";
			document.getElementsByClassName("size_block")[0].style.display = "none";
		}
		else {
            document.getElementsByClassName("size_block")[0].style.display = "table-row";
			document.getElementsByClassName("apparel_block")[0].style.display = "table-row";
		}
	};
</script>
<?}?>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_LISTINGFEE'); ?></td>
	<td width="60%">
		<select name="AVITO_LISTINGFEE">
          <option value="Package" <?if($avitoListingfee=='Package') echo 'selected'?>><? echo GetMessage('AVITO_LISTINGFEE_PACKAGE'); ?></option>
          <option value="Single" <?if($avitoListingfee=='Single') echo 'selected'?>><? echo GetMessage('AVITO_LISTINGFEE_SINGLE'); ?></option>
        </select>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_ADTYPE'); ?></td>
	<td width="60%">
		<select name="AVITO_ADTYPE">
          <option value="<? echo GetMessage('AVITO_ADTYPE_SALE'); ?>" <?if($avitoAdtype==GetMessage('AVITO_ADTYPE_SALE')) echo 'selected'?>><? echo GetMessage('AVITO_ADTYPE_SALE'); ?></option>
          <option value="<? echo GetMessage('AVITO_ADTYPE_PROIS'); ?>" <?if($avitoAdtype==GetMessage('AVITO_ADTYPE_PROIS')) echo 'selected'?>><? echo GetMessage('AVITO_ADTYPE_PROIS'); ?></option>
       </select>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_PHONE'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_PHONE" value="<?=$avitoPhone;?>">
			</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_ADRESS'); ?></td>
	<td width="60%">
        <? if($avitoAdress) {?>
		<textarea name="AVITO_ADRESS"rows="5" cols="40"  ><?=$avitoAdress;?></textarea>
		<? }
		else {
		?>
		<textarea name="AVITO_ADRESS" rows="5" cols="40" ><? echo GetMessage('AVITO_ADRESS_DEFAULT'); ?></textarea>
			<?}
			?>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('DISPLAYAREAS'); ?></td>
	<td width="60%">
		<textarea name="DISPLAYAREAS"rows="5" cols="40"  ><?=$DISPLAYAREAS;?></textarea>
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><?=GetMessage('AVITO_ALL'); ?></td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_STOCK'); ?></td>
	<td width="60%">
		<input type="hidden" name="AVITO_STOCK" value="N">
		<input type="checkbox" name="AVITO_STOCK" value="Y"<? echo ($AVITO_STOCK == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('AVITO_STOCK_CONST'); ?></td>
	<td width="60%">
		<input type="text" name="AVITO_STOCK_CONST" value="<?=$AVITO_STOCK_CONST?>">
	</td>
</tr>
<? $SETUP_FILE_NAME_STOCK=substr($SETUP_FILE_NAME,0,-4).'_stock.php';?>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("AVITO_STOCK_NOTE");?><div id='stockFile'><a href="<? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?><?=htmlspecialcharsbx($SETUP_FILE_NAME_STOCK); ?>" target='_blank' id='link_stock'><? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?><?=htmlspecialcharsbx($SETUP_FILE_NAME_STOCK); ?></a></div></small></td>
</tr>
<tr>
<?
	$maxExecutionTime = (isset($maxExecutionTime) ? (int)$maxExecutionTime : 0);
?><tr>
	<td width="40%"><?=GetMessage('CAT_MAX_EXECUTION_TIME');?></td>
	<td width="60%">
		<input type="text" name="MAX_EXECUTION_TIME" size="40" value="<?=$maxExecutionTime; ?>">
	</td>
</tr>
<tr>
	<td width="40%" style="padding-top: 0;">&nbsp;</td>
	<td width="60%" style="padding-top: 0;"><small><?=GetMessage("CAT_MAX_EXECUTION_TIME_NOTE");?></small></td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('CAT_YANDEX_USE_HTTPS'); ?></td>
	<td width="60%">
		<input type="hidden" name="USE_HTTPS" value="N">
		<input type="checkbox" name="USE_HTTPS" value="Y"<? echo ($USE_HTTPS == 'Y' ? ' checked' : ''); ?>
	</td>
</tr>
<tr>
	<td width="40%"><?echo GetMessage("CET_SERVER_NAME");?></td>
	<td width="60%">
		<input type="text" name="SETUP_SERVER_NAME" value="<?=htmlspecialcharsbx($SETUP_SERVER_NAME); ?>" size="50"> <input type="button" onclick="this.form['SETUP_SERVER_NAME'].value = window.location.host;" value="<?echo htmlspecialcharsbx(GetMessage('CET_SERVER_NAME_SET_CURRENT'))?>">
	</td>
</tr>

<tr>
	<td width="40%"><?echo GetMessage("CET_SAVE_FILENAME");?></td>
	<td width="60%">
		<b><? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?></b><input type="text" name="SETUP_FILE_NAME" value="<?=htmlspecialcharsbx($SETUP_FILE_NAME); ?>" size="50" onchange='fileForm(this.value)'>
	</td>
</tr>
<script>
function fileForm(value) {
  if (value != '') {
  	value=value.replace('.php','')+'_stock.php';
    filestock=document.getElementById("link_stock");
    filestock.textContent = '<? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?>'+value;
    filestock.href = '<? echo htmlspecialcharsbx(COption::GetOptionString("catalog", "export_default_path", "/bitrix/catalog_export/"));?>'+value;
  }
}
</script>
<?
	if ($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
	{
?><tr>
	<td width="40%"><?echo GetMessage("CET_PROFILE_NAME");?></td>
	<td width="60%">
		<input type="text" name="SETUP_PROFILE_NAME" value="<?echo htmlspecialcharsbx($SETUP_PROFILE_NAME) ?>" size="30">
	</td>
</tr><?
	}
}

$tabControl->EndTab();

$tabControl->BeginNextTab();

if ($STEP==2)
{
	$SETUP_FILE_NAME = $strAllowExportPath.$SETUP_FILE_NAME;
	if (strlen($XML_DATA) > 0)
	{
		$XML_DATA = base64_decode($XML_DATA);
	}
	$SETUP_SERVER_NAME = htmlspecialcharsbx($SETUP_SERVER_NAME);
	$_POST['SETUP_SERVER_NAME'] = htmlspecialcharsbx($_POST['SETUP_SERVER_NAME']);
	$_REQUEST['SETUP_SERVER_NAME'] = htmlspecialcharsbx($_REQUEST['SETUP_SERVER_NAME']);

	$FINITE = true;
}
$tabControl->EndTab();

$tabControl->Buttons();

?><? echo bitrix_sessid_post();?><?
if ($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
{
	?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
}

if (2 > $STEP)
{
	?><input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
	<input type="hidden" name="ACT_FILE" value="<?echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]) ?>">
	<input type="hidden" name="ACTION" value="<?echo htmlspecialcharsbx($ACTION) ?>">
	<input type="hidden" name="STEP" value="<?echo intval($STEP) + 1 ?>">
	<input type="hidden" name="SETUP_FIELDS_LIST" value="V,IBLOCK_ID,SITE_ID,SETUP_SERVER_NAME,COMPANY_NAME,AVITO_CATEGORY_TYPE,AVITO_CATEGORY,AVITO_LISTINGFEE,AVITO_ADRESS,AVITO_CONDITION,AVITO_PHONE,AVITO_ADTYPE,AVITO_APPAREL,AVITO_VIDEO,AVITO_SIZE,AVITO_PRICE,AVITO_CATEGORY_CUSTOM,AVITO_FILTER,SETUP_FILE_NAME,XML_DATA,USE_HTTPS,FILTER_AVAILABLE,DISABLE_REFERERS,MAX_EXECUTION_TIME,CHECK_PERMISSIONS,FILTER_INPUT,FILTER_PREF,FILTER_DATA,ABRICOS_AVITO_PHOTO,AVITO_TITLE_PROP,AVITO_NODETAIL_PHOTO,AVITO_NOPHOTO,AVITO_NODESCR,AVITO_DESCR_PROP,AVITO_STORE,AVITO_PRICE_MAX,AVITO_TITLE_TEMPL,AVITO_DESCR_TEMPL,AVITO_DESCR_TEMPL_PL,AVITO_PROP_PRICE,AVITO_CONDITION_ALT,PROP_DATA,PROP_NAME,PROP_DEF,DISPLAYAREAS,AVITO_STOCK,AVITO_STOCK_CONST">
	<input type="submit" value="<?echo ($ACTION=="EXPORT")?GetMessage("CET_EXPORT"):GetMessage("CET_SAVE")?>"><?
}

$tabControl->End();
?></form>
<script type="text/javascript">
<?if ($STEP < 2):?>
tabYandex.SelectTab("yand_edit1");
tabYandex.DisableTab("yand_edit2");
<?elseif ($STEP == 2):?>
tabYandex.SelectTab("yand_edit2");
tabYandex.DisableTab("yand_edit1");
<?endif;?>
</script>