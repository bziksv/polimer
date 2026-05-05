<?
//<title>CSV Export Abricos</title>
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global string $ACTION */
/** @global array $arOldSetupVars */
/** @global int $IBLOCK_ID */
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_setup_templ.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_yandex.php');
IncludeModuleLangFile(__FILE__);
 CModule::IncludeModule("abricos.csv");
global $APPLICATION, $USER;

$maxDepthLevel = (int)COption::GetOptionInt("catalog", "num_catalog_levels", 3);
if ($maxDepthLevel <= 0)
	$maxDepthLevel = 3;

$arSetupErrors = array();

$strCatalogDefaultFolder = COption::GetOptionString("catalog", "export_default_path", CATALOG_DEFAULT_EXPORT_PATH);

$STEP = (int)$STEP;
if (0 >= $STEP)
	$STEP = 1;

$ACTION = strval($ACTION);

//********************  ACTIONS  **************************************//
if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 1)
{
	if (isset($arOldSetupVars['IBLOCK_ID']))
		$IBLOCK_ID = $arOldSetupVars['IBLOCK_ID'];
}
if ($STEP > 1)
{
	$IBLOCK_ID = (int)$IBLOCK_ID;
	if ($IBLOCK_ID <= 0)
	{
		$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
	}
	else
	{
		$rsIBlocks = CIBlock::GetList(array(),array('IBLOCK_ID' => $IBLOCK_ID,'CHECK_PERMISSIONS' => 'N'));
		if (!($arIBlock = $rsIBlocks->Fetch()))
		{
			$arSetupErrors[] = GetMessage("CATI_NO_IBLOCK");
		}
		elseif (!CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, 'iblock_admin_display'))
		{
			$arSetupErrors[] = str_replace('#IBLOCK_ID#', $IBLOCK_ID, GetMessage('CET_ERROR_IBLOCK_PERM'));
		}
	}

	if (!empty($arSetupErrors))
	{
		$STEP = 1;
	}
}
if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 2)
{
	if (isset($arOldSetupVars['IBLOCK_ID']) && $arOldSetupVars['IBLOCK_ID'] == $IBLOCK_ID)
	{
		if (isset($arOldSetupVars['V']))
				$V = $arOldSetupVars['V'];
		if (isset($arOldSetupVars['FILTER_DATA']))
				$FILTER_DATA = $arOldSetupVars['FILTER_DATA'];
		if (isset($arOldSetupVars['FILTER_PREF']))
				$FILTER_PREF = $arOldSetupVars['FILTER_PREF'];
		if (isset($arOldSetupVars['FILTER_INPUT']))
				$FILTER_INPUT = $arOldSetupVars['FILTER_INPUT'];
		if (isset($arOldSetupVars['FILTER_ACTIVE']))
				$FILTER_ACTIVE = $arOldSetupVars['FILTER_ACTIVE'];
		if (isset($arOldSetupVars['FILTER_AVAILABLE']))
				$FILTER_AVAILABLE = $arOldSetupVars['FILTER_AVAILABLE'];
		if (isset($arOldSetupVars['FILTER_PRICE_MAX']))
				$FILTER_PRICE_MAX = $arOldSetupVars['FILTER_PRICE_MAX'];
		if (isset($arOldSetupVars['FILTER_PRICE_MIN']))
				$FILTER_PRICE_MIN = $arOldSetupVars['FILTER_PRICE_MIN'];
		if (isset($arOldSetupVars['FILTER_QUANTYTY']))
				$FILTER_QUANTYTY = $arOldSetupVars['FILTER_QUANTYTY'];
	}
}

if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 3)
{
	if (isset($arOldSetupVars['IBLOCK_ID']) && $arOldSetupVars['IBLOCK_ID'] == $IBLOCK_ID)
	{
		if (isset($arOldSetupVars['field_needed']))
			$field_needed = $arOldSetupVars['field_needed'];
		if (isset($arOldSetupVars['field_num']))
			$field_num = $arOldSetupVars['field_num'];
		if (isset($arOldSetupVars['field_code']))
			$field_code = $arOldSetupVars['field_code'];
	}
	if (isset($arOldSetupVars['fields_type']))
		$fields_type = $arOldSetupVars['fields_type'];

	if (isset($arOldSetupVars['delimiter_r']))
		$delimiter_r = $arOldSetupVars['delimiter_r'];
	if (isset($arOldSetupVars['delimiter_r_char']))
		$delimiter_r_char = $arOldSetupVars['delimiter_r_char'];
	if (isset($arOldSetupVars['delimiter_other_r']))
		$delimiter_other_r = $arOldSetupVars['delimiter_other_r'];

	if (isset($arOldSetupVars['SETUP_FILE_NAME']))
		$SETUP_FILE_NAME = str_replace($strCatalogDefaultFolder, '', $arOldSetupVars['SETUP_FILE_NAME']);
	if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
		$SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
	if (isset($arOldSetupVars['first_line_names']))
		$first_line_names = $arOldSetupVars['first_line_names'];
	if (isset($arOldSetupVars['export_files']))
		$export_files = $arOldSetupVars['export_files'];
	if (isset($arOldSetupVars['export_from_clouds']))
		$export_from_clouds = $arOldSetupVars['export_from_clouds'];
	if (isset($arOldSetupVars['CML2_LINK_IS_XML']))
		$CML2_LINK_IS_XML = $arOldSetupVars['CML2_LINK_IS_XML'];
	if (isset($arOldSetupVars['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $arOldSetupVars['MAX_EXECUTION_TIME'];
}

if ($STEP>3)
{
	if (!isset($fields_type) || ($fields_type!="F" && $fields_type!="R"))
	{
		$arSetupErrors[] = GetMessage("CATI_NO_FORMAT");
	}

	$delimiter_r_char = "";
	if (isset($delimiter_r))
	{
		switch ($delimiter_r)
		{
			case "TAB":
				$delimiter_r_char = "\t";
				break;
			case "ZPT":
				$delimiter_r_char = ",";
				break;
			case "SPS":
				$delimiter_r_char = " ";
				break;
			case "OTR":
				$delimiter_r_char = (isset($delimiter_other_r)? mb_substr($delimiter_other_r, 0, 1) : '');
				$delimiter_other_r = $delimiter_r_char;
				break;
			case "TZP":
				$delimiter_r_char = ";";
				break;
		}
	}

	if (mb_strlen($delimiter_r_char) != 1)
	{
		$arSetupErrors[] = GetMessage("CATI_NO_DELIMITER");
	}

	if (!isset($SETUP_FILE_NAME) || $SETUP_FILE_NAME == '')
	{
		$arSetupErrors[] = GetMessage("CATI_NO_SAVE_FILE");
	}

	if (empty($arSetupErrors))
	{
		$SETUP_FILE_NAME = str_replace('//','/',$strCatalogDefaultFolder.Rel2Abs("/", $SETUP_FILE_NAME));
		if (preg_match(BX_CATALOG_FILENAME_REG, $SETUP_FILE_NAME))
		{
			$arSetupErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
		}
		elseif ($strCatalogDefaultFolder == $SETUP_FILE_NAME)
		{
			$arSetupErrors[] = GetMessage("CATI_NO_SAVE_FILE");
		}
	}

	if (empty($arSetupErrors))
	{
		if (mb_strtolower(mb_substr($SETUP_FILE_NAME, mb_strlen($SETUP_FILE_NAME) - 4)) != ".csv")
			$SETUP_FILE_NAME .= ".csv";
		if (HasScriptExtension($SETUP_FILE_NAME))
		{
			$arSetupErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME_EXTENTIONS");
		}
	}

	if (empty($arSetupErrors))
	{
		if ($APPLICATION->GetFileAccessPermission($SETUP_FILE_NAME) < "W")
		{
			$arSetupErrors[] = str_replace("#FILE#", $SETUP_FILE_NAME, GetMessage('CATI_NO_RIGHTS_FILE'));
		}
		else
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);
			if (!($fp = fopen($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, "wb")))
			{
				$arSetupErrors[] = GetMessage("CATI_CANNOT_CREATE_FILE");
			}
			else
			{
				fclose($fp);
				unlink($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);
			}
		}
	}

	$bFieldsPres = (!empty($field_needed) && is_array($field_needed) && in_array('Y', $field_needed));
	if ($bFieldsPres && (empty($field_code) || !is_array($field_code)))
	{
		$bFieldsPres = false;
	}
	if (!$bFieldsPres)
	{
		$arSetupErrors[] = GetMessage("CATI_NO_FIELDS");
	}

	$CML2_LINK_IS_XML = (isset($CML2_LINK_IS_XML) && $CML2_LINK_IS_XML == 'Y' ? 'Y' : 'N');

	if (isset($_POST['MAX_EXECUTION_TIME']) && is_string($_POST['MAX_EXECUTION_TIME']))
		$maxExecutionTime = $_POST['MAX_EXECUTION_TIME'];
	$maxExecutionTime = (!isset($maxExecutionTime) ? 0 : (int)$maxExecutionTime);
	if ($maxExecutionTime < 0)
		$maxExecutionTime = 0;

	if (($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && (!isset($SETUP_PROFILE_NAME) || $SETUP_PROFILE_NAME == ''))
	{
		$arSetupErrors[] = GetMessage("CET_ERROR_NO_NAME");
	}

	if (!empty($arSetupErrors))
	{
		$STEP = 3;
	}
}

//********************  END ACTIONS  **********************************//

$aMenu = array(
	array(
		"TEXT" => GetMessage("CATI_ADM_RETURN_TO_LIST"),
		"TITLE" => GetMessage("CATI_ADM_RETURN_TO_LIST_TITLE"),
		"LINK" => "/bitrix/admin/cat_export_setup.php?lang=".LANGUAGE_ID,
		"ICON" => "btn_list",
	)
);

$context = new CAdminContextMenu($aMenu);

$context->Show();

if (!empty($arSetupErrors))
	ShowError(implode('<br />', $arSetupErrors));

$actionParams = "";
if ($adminSidePanelHelper->isSidePanel())
{
	$actionParams = "?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER";
}
?>

<? if(CModule::IncludeModuleEx("abricos.csv")==2) {?>
<div align="center"><?=GetMessage("DEMO")?></div>
<? }?>
<? if(CModule::IncludeModuleEx("abricos.csv")==3) {?>
<div align="center"><?=GetMessage("DEMO_OFF")?></div>
<? }?>
<form method="POST" action="<? echo $APPLICATION->GetCurPage().$actionParams; ?>" enctype="multipart/form-data" name="dataload">
<?
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("CAT_ADM_CSV_EXP_TAB1"), "ICON" => "store", "TITLE" => GetMessage("CAT_ADM_CSV_EXP_TAB1_TITLE")),
	array("DIV" => "edit2", "TAB" => GetMessage("CAT_A_CSV_EXP_TAB2"), "ICON" => "store", "TITLE" => GetMessage("CAT_A_CSV_EXP_TAB2_TITLE")),
	array("DIV" => "edit3", "TAB" => GetMessage("CAT_A_CSV_EXP_TAB3"), "ICON" => "store", "TITLE" => GetMessage("CAT_A_CSV_EXP_TAB3_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();

$tabControl->BeginNextTab();

if ($STEP == 1)
{
	?><tr class="heading">
		<td colspan="2"><?echo GetMessage("CATI_DATA_EXPORT") ?></td>
	</tr>
	<tr>
		<td valign="top" width="40%"><? echo GetMessage("CAT_ADM_CSV_EXP_IBLOCK_ID"); ?>:</td>
		<td valign="top" width="60%"><?
			if (!isset($IBLOCK_ID))
				$IBLOCK_ID = 0;
			echo GetIBlockDropDownListEx(
				$IBLOCK_ID,
				'IBLOCK_TYPE_ID',
				'IBLOCK_ID',
				array('CHECK_PERMISSIONS' => 'Y','MIN_PERMISSION' => 'U'),
				'',
				'',
				'class="adm-detail-iblock-types"',
				'class="adm-detail-iblock-list"'
			);
		?></td>
	</tr><?
}

$tabControl->EndTab();

$tabControl->BeginNextTab();

if ($STEP == 2)
{
	$catalog = CCatalogSku::GetInfoByIBlock($IBLOCK_ID);
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
<tr>
	<td width="40%" valign="top"><?echo GetMessage("CET_SELECT_GROUP");?></td>
	<td width="60%">
	<?
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
		<iframe src="/bitrix/tools/abricos.csv/acsv_util.php?IBLOCK_ID=<?=intval($IBLOCK_ID)?>&<? echo bitrix_sessid_get(); ?>" id="id_ifr" name="ifr" style="display:none"></iframe>

		</td>
</tr>
<? if(false) {?>
<? //if($catalog) {?>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('FILTER_PRICE_MAX'); ?></td>
	<td width="60%">
		<input type="text" name="FILTER_PRICE_MAX" value='<?=$FILTER_PRICE_MAX?>'>
    </td>
</tr>
<tr class="price_block">
	<td width="40%"><? echo GetMessage('FILTER_PRICE_MIN'); ?></td>
	<td width="60%">
		<input type="text" name="FILTER_PRICE_MIN" value='<?=$FILTER_PRICE_MIN?>'>
    </td>
</tr>
<?}?>
<tr>
<td colspan=2 align="center">
<?
if($IBLOCK_ID>0)
{?>
<table class="inner" id="CSV_FILTER_tbl">
					<thead>
					<tr><td colspan=2><b><? echo GetMessage('CSV_FILTER_TITLE'); ?></b></td>
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
								echo CAbricosCSV::addParamRow($arIBlock, $intCount, $arParamDetail, '',$FILTER_PREF[$intCount],$FILTER_INPUT[$intCount]);
								?>

								<?
								$intCount++;
							}
						}
						if ($intCount == 0)
						{
							echo CAbricosCSV::addParamRow($arIBlock, $intCount, '', '','','');
							$intCount++;
						}

						?>

                  </tbody>
				</table>
				<input type="hidden" name="FILTER_COUNT" id="FILTER_COUNT" value="<? echo $intCount; ?>">
				<div style="width: 100%; text-align: center;"><input type="button" onclick="addYP(); return false;" name="CSV_FILTER_add" value="<? echo GetMessage('CSV_FILTER_ADDITIONAL_MORE'); ?>"></div>
 <? }?>
	</td>
</tr>
<? if(!$intCount) $intCount=1; ?>
<script type="text/javascript">
BX.ready(function(){
		setTimeout(function(){
			window.oParamSet = {
				pTypeTbl: BX("CSV_FILTER_tbl"),
				curCount: <? echo ($intCount); ?>,
				intCounter: BX("FILTER_COUNT")

			};
		},50);
});
function addYP()
{
	var id = window.oParamSet.curCount++,
		newRow,
		oCell,
		strContent;
	id = id.toString();
	window.oParamSet.intCounter.value = window.oParamSet.curCount;
	newRow = window.oParamSet.pTypeTbl.insertRow(window.oParamSet.pTypeTbl.rows.length);
	newRow.id = 'CSV_FILTER_tbl_'+id;
	oCell = newRow.insertCell(-1);
	<?
	if($IBLOCK_ID>0)
	{	?>
		strContent = '<? echo CUtil::JSEscape(CAbricosCSV::addParamName($arIBlock, 'tmp_xxx', '')); ?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;

	<?}
	else {
	?>
		strContent = document.getElementById('CSV_FILTER_tbl_0').getElementsByTagName('td')[0].innerHTML;
	    strContent = strContent.replace('FILTER_DATA[0]', 'FILTER_DATA['+id+']');
		oCell.innerHTML = strContent;
	<? }?>
	   	oCell = newRow.insertCell(-1);
		strContent = '<?=CAbricosCSV::addPrefRow($intCount)?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;

		oCell = newRow.insertCell(-1);
		strContent = '<?=CAbricosCSV::addInputRow($intCount)?>';
		strContent = strContent.replace(/tmp_xxx/ig, id);
		oCell.innerHTML = strContent;
	}

</script>
<tr>
	<td width="40%"><? echo GetMessage('FILTER_ACTIVE'); ?></td>
	<td width="60%">
		<select name="FILTER_ACTIVE">
		  <option value="ALL" <?if($FILTER_ACTIVE=='ALL') echo 'selected'?>><? echo GetMessage('FILTER_ACTIVE_ALL'); ?></option>
          <option value="Y" <?if($FILTER_ACTIVE=='Y') echo 'selected'?>><? echo GetMessage('FILTER_ACTIVE_Y'); ?></option>
		  <option value="N" <?if($FILTER_ACTIVE=='N') echo 'selected'?>><? echo GetMessage('FILTER_ACTIVE_N'); ?></option>
        </select>
	</td>
</tr>
<? if($catalog) {?>
<tr>
	<td width="40%"><? echo GetMessage('FILTER_AVAILABLE'); ?></td>
	<td width="60%">
		<select name="FILTER_AVAILABLE">
		  <option value="ALL" <?if($FILTER_AVAILABLE=='ALL') echo 'selected'?>><? echo GetMessage('FILTER_AVAILABLE_ALL'); ?></option>
          <option value="Y" <?if($FILTER_AVAILABLE=='Y') echo 'selected'?>><? echo GetMessage('FILTER_AVAILABLE_Y'); ?></option>
		  <option value="N" <?if($FILTER_AVAILABLE=='N') echo 'selected'?>><? echo GetMessage('FILTER_AVAILABLE_N'); ?></option>
        </select>
	</td>
</tr>
<tr>
	<td width="40%"><? echo GetMessage('FILTER_QUANTYTY'); ?></td>
	<td width="60%">
		<input type="text" name="FILTER_QUANTYTY" value="<?=$FILTER_QUANTYTY;?>">
	</td>
</tr>
<?}?>
	<?
	}
$tabControl->EndTab();

$tabControl->BeginNextTab();

if ($STEP == 3)
{
	?><tr class="heading">
		<td colspan="2"><?echo GetMessage("CATI_FORMAT_PROPS") ?></td>
	</tr>
	<tr>
		<td valign="top" width="40%"><?echo GetMessage("CATI_DELIMITERS") ?>:</td>
		<td valign="top" width="60%"><?
			if (!isset($delimiter_r) || empty($delimiter_r))
				$delimiter_r = 'TZP';
			?><input type="hidden" name="fields_type" value="R">
			<input type="radio" name="delimiter_r" value="TZP" <?if ($delimiter_r=="TZP") echo "checked"?>><?echo GetMessage("CATI_TZP") ?><br>
			<input type="radio" name="delimiter_r" value="ZPT" <?if ($delimiter_r=="ZPT") echo "checked"?>><?echo GetMessage("CATI_ZPT") ?><br>
			<input type="radio" name="delimiter_r" value="TAB" <?if ($delimiter_r=="TAB") echo "checked"?>><?echo GetMessage("CATI_TAB") ?><br>
			<input type="radio" name="delimiter_r" value="SPS" <?if ($delimiter_r=="SPS") echo "checked"?>><?echo GetMessage("CATI_SPS") ?><br>
			<input type="radio" name="delimiter_r" value="OTR" <?if ($delimiter_r=="OTR") echo "checked"?>><?echo GetMessage("CATI_OTR") ?>
			<input type="text" class="typeinput" name="delimiter_other_r" size="3" value="<?echo htmlspecialcharsbx($delimiter_other_r); ?>">
		</td>
	</tr>
	<tr>
		<td valign="top" width="40%"><label for="first_line_names_Y"><?echo GetMessage("CATI_FIRST_LINE_NAMES") ?>:</label></td>
		<td valign="top" width="60%"><?
			if (!isset($first_line_names))
				$first_line_names = 'Y';
			?><input type="hidden" name="first_line_names" id="first_line_names_N" value="N">
			<input type="checkbox" name="first_line_names" id="first_line_names_Y" value="Y" <?if ($first_line_names=="Y") echo "checked"?>>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("CATI_FIELDS") ?></td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="internal">
<?
	$boolCatalog = false;
	$boolOffers = false;
	$rsCatalogs = CCatalog::GetList(
		array(),
		array('IBLOCK_ID' => $IBLOCK_ID),
		false,
		false,
		array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID')
	);
	if ($arCatalog = $rsCatalogs->Fetch())
	{
		$boolCatalog = true;
		$boolOffers = ((int)$arCatalog['PRODUCT_IBLOCK_ID'] > 0);
	}

	$allowedProductFields = array();
	$allowedSectionFields = array();
	$allowedPriceQuantityFields = CCatalogCSVSettings::getDefaultSettings(CCatalogCSVSettings::FIELDS_PRICE_EXT, true);
	$allowedPriceFields = array();

	$fieldsOption = trim(COption::GetOptionString('catalog', 'allowed_product_fields'));
	if ($fieldsOption != '')
	{
		$allowedProductFields = explode(',', $fieldsOption);
	}
	$fieldsOption = trim(COption::GetOptionString('catalog', 'allowed_group_fields'));
	if ($fieldsOption != '')
	{
		$allowedSectionFields = explode(',', $fieldsOption);
	}
	if ($boolCatalog)
	{
		$fieldsOption = trim(COption::GetOptionString('catalog', 'allowed_price_fields'));
		if ($fieldsOption != '')
		{
			$allowedPriceFields = explode(',', $fieldsOption);
		}
	}

	$arAvailFields = array();
	$intCount = 0;
	$boolSep = true;

	if (!empty($allowedProductFields))
	{
		$elementFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_ELEMENT, true);
		foreach ($allowedProductFields as &$fieldName)
		{
			if (isset($elementFields[$fieldName]))
			{
				$arAvailFields[$intCount] = $elementFields[$fieldName];
				$arAvailFields[$intCount]['sort'] = ($intCount+1)*10;
				if ($boolSep)
				{
					$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_ELEMENTS');
					$boolSep = false;
				}
				$intCount++;
			}
		}
		unset($fieldName, $elementFields);
	}

	$properties = CIBlockProperty::GetList(array("SORT"=>"ASC", "ID"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE"=>"Y", 'CHECK_PERMISSIONS' => 'N'));
	while ($prop_fields = $properties->Fetch())
	{
		$prop_fields['CODE'] = (string)$prop_fields['CODE'];
		if ($prop_fields['CODE'] === '')
			$prop_fields['CODE'] = $prop_fields['ID'];
		$arAvailFields[$intCount] = array(
			"value" => "IP_PROP".$prop_fields["ID"],
			"name" => GetMessage("CATI_FI_PROPS").' "'.$prop_fields["NAME"].'"'.' ['.$prop_fields['CODE'].']',
			'sort' => ($intCount+1)*10,
		);
		if ($boolSep)
		{
			$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_ELEMENTS');
			$boolSep = false;
		}
		$intCount++;
	}
	if (isset($prop_fields))
		unset($prop_fields);
	unset($properties);

	$boolSep = true;
	if (!empty($allowedSectionFields))
	{
		$sectionFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_SECTION, true);
		$selectedSectionFields = array();
		foreach ($allowedSectionFields as &$fieldName)
		{
			if (isset($sectionFields[$fieldName]))
			{
				$selectedSectionFields[] = $sectionFields[$fieldName];
			}
		}
		unset($fieldName, $allowedSectionFields, $sectionFields);
		if (!empty($selectedSectionFields))
		{
			for ($currentDepthLevel = 0; $currentDepthLevel < $maxDepthLevel; $currentDepthLevel++)
			{
				$subSep = true;
				foreach ($selectedSectionFields as &$oneField)
				{
					$arAvailFields[$intCount] = $oneField;
					$arAvailFields[$intCount]['value'] .= $currentDepthLevel;
					$arAvailFields[$intCount]['sort'] = ($intCount+1)*10;
					if ($boolSep)
					{
						$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_SECTIONS');
						$boolSep = false;
					}
					if ($subSep)
					{
						$arAvailFields[$intCount]['SUB_SEP'] = str_replace('#LEVEL#',($currentDepthLevel+1),GetMessage("CAT_ADM_CSV_EXP_SECTION_LEVEL"));
						$subSep = false;
					}
					$intCount++;
				}
				unset($oneField);
			}
		}
		unset($selectedSectionFields);
	}

	if ($boolCatalog)
	{
		if (!empty($allowedProductFields))
		{
			$boolSep = true;
			$productFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_CATALOG, true);
			foreach ($allowedProductFields as &$fieldName)
			{
				if (isset($productFields[$fieldName]))
				{
					$arAvailFields[$intCount] = $productFields[$fieldName];
					$arAvailFields[$intCount]['sort'] = ($intCount+1)*10;
					if ($boolSep)
					{
						$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_PRODUCT');
						$boolSep = false;
					}
					$intCount++;
				}
			}
			unset($fieldName, $productFields);
		}

		if (!empty($allowedPriceFields))
		{
			$boolSep = true;
			$priceQuantityFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_PRICE_EXT, true);
			foreach ($allowedPriceQuantityFields as &$fieldName)
			{
				if (isset($priceQuantityFields[$fieldName]))
				{
					$arAvailFields[$intCount] = $priceQuantityFields[$fieldName];
					$arAvailFields[$intCount]['sort'] = ($intCount+1)*10;
					if ($boolSep)
					{
						$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_PRICES');
						$boolSep = false;
					}
					$intCount++;
				}
			}
			unset($fieldName, $priceQuantityFields);

			$priceFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_PRICE, true);
			$rsPriceTypes = CCatalogGroup::GetListEx(
				array('SORT' => 'ASC', 'ID' => 'ASC'),
				array(),
				false,
				false,
				array('ID', 'NAME', 'NAME_LANG')
			);
			while ($priceType = $rsPriceTypes->Fetch())
			{
				$priceType['NAME_LANG'] = (string)$priceType['NAME_LANG'];
				foreach ($allowedPriceFields as &$fieldName)
				{
					if (isset($priceFields[$fieldName]))
					{
						$priceName = ($priceType['NAME_LANG'] !== '' ?
							str_replace(array('#TYPE#', '#NAME#'), array($priceType['NAME'], $priceType['NAME_LANG']), GetMessage('EST_PRICE_TYPE2')):
							str_replace("#TYPE#", $priceType['NAME'], GetMessage('EST_PRICE_TYPE'))
						);
						$arAvailFields[$intCount] = $priceFields[$fieldName];
						$arAvailFields[$intCount]['value'] .= '_'.$priceType['ID'];
						$arAvailFields[$intCount]['name'] = $priceName.': '.$arAvailFields[$intCount]['name'];
						$arAvailFields[$intCount]['sort'] = ($intCount+1)*10;
						if ($boolSep)
						{
							$arAvailFields[$intCount]['SEP'] = GetMessage('CAT_ADM_CSV_EXP_SEP_PRICES');
							$boolSep = false;
						}
						$intCount++;
					}
				}
				unset($fieldName);
			}
			unset($priceType, $rsPriceTypes, $priceFields);
		}
	}

	$intCountAvailFields = $intCount;
	$intCountChecked = 0;
	$arCheckID = array();
	$boolAll = true;
	if (isset($field_code) && !empty($field_code) && is_array($field_code))
	{
		foreach ($arAvailFields as $i => $arOneAvailField)
		{
			$intSort = 0;
			$key = array_search($arOneAvailField['value'], $field_code);
			if (false !== $key)
			{
				if (isset($field_needed[$key]) && 'Y' == $field_needed[$key])
				{
					$boolAll = false;
					$arCheckID[] = $arOneAvailField['value'];
					$intCountChecked++;
				}
				if (isset($field_num[$key]) && 0 < intval($field_num[$key]))
					$intSort = intval($field_num[$key]);
			}
			if (0 < $intSort)
				$arAvailFields[$i]['sort'] = $intSort;
		}
	}
	if ($boolAll)
		$intCountChecked = $intCountAvailFields;
				?><tr class="heading">
					<td valign="middle" align="left" style="text-align: left;">
						<input style="vertical-align: middle;" type="checkbox" name="field_needed_all" id="field_needed_all" value="Y" onclick="checkAll(this,<? echo $intCountAvailFields; ?>);"<? echo ($boolAll || ($intCountChecked == $intCountAvailFields) ? ' checked' : ''); ?>>&nbsp;
						<b><?echo GetMessage("CATI_FIELDS_NEEDED") ?></b></td>
					<td valign="middle" align="center"><b><?echo GetMessage("CATI_FIELDS_NAMES") ?></b></td>
					<td valign="middle" align="center"><b><?echo GetMessage("CATI_FIELDS_SORTING") ?></b></td>
				</tr><?
				foreach ($arAvailFields as $i => $arOneAvailField)
				{
					if (!empty($arOneAvailField['SEP']))
					{
						?><tr><td colspan="3" valign="middle" align="center"><b><? echo htmlspecialcharsbx($arOneAvailField['SEP']); ?></b></td></tr><?
					}
					if (!empty($arOneAvailField['SUB_SEP']))
					{
						?><tr><td>&nbsp;</td><td valign="middle" align="left"><b><? echo htmlspecialcharsbx($arOneAvailField['SUB_SEP']); ?></b></td><td>&nbsp;</td></tr><?
					}
					?>
					<tr>
				<td valign="top" align="left"><input type="checkbox" name="field_needed[<? echo $i; ?>]" id="field_needed_<? echo $i; ?>"
					<?if ($boolAll || in_array($arOneAvailField['value'],$arCheckID)) echo "checked"; ?>
					value="Y" onclick="checkOne(this,<? echo $intCountAvailFields; ?>);"></td>
				<td valign="middle" align="left">
								<?if ($i<2) echo "<b>";?>
								<?echo htmlspecialcharsbx($arOneAvailField["name"]); ?>
								<?if ($i<2) echo "</b>";?>
							</td>
				<td valign="top" align="center">
							<?if ($i<2) echo "<b>";?>
							<input type="text" class="typeinput" name="field_num[<?echo $i ?>]" value="<?echo $arOneAvailField['sort']; ?>" size="4"> <input type="hidden" name="field_code[<?echo $i ?>]"
					value="<?echo htmlspecialcharsbx($arOneAvailField["value"]) ?>">
							<?if ($i<2) echo "</b>";?>
						</td>
			</tr>
					<?
				}
				?>
			</table>
			<input type="hidden" name="count_checked" id="count_checked" value="<? echo $intCountChecked; ?>">
			<script type="text/javascript">
			function checkAll(obj,cnt)
			{
				var boolCheck = obj.checked,
					i;
				for (i = 0; i < cnt; i++)
				{
					BX('field_needed_'+i).checked = boolCheck;
				}
				BX('count_checked').value = (boolCheck ? cnt : 0);
			}
			function checkOne(obj,cnt)
			{
				var boolCheck = obj.checked;
				var intCurrent = parseInt(BX('count_checked').value);
				intCurrent += (boolCheck ? 1 : -1);
				BX('field_needed_all').checked = (intCurrent >= cnt);
				BX('count_checked').value = intCurrent;
			}
			</script>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><? echo GetMessage('CAT_ADM_CSV_EXP_ADD_SETTINGS') ?></td>
	</tr><?
	if (!isset($CML2_LINK_IS_XML))
		$CML2_LINK_IS_XML = 'N';
	if ($boolOffers)
	{
		?>
		<tr>
			<td width="40%"><? echo GetMessage('CAT_ADM_CSV_EXP_CML2_LINK_IS_XML'); ?>:</td>
			<td width="60%">
				<input type="hidden" name="CML2_LINK_IS_XML" value="N">
				<input type="checkbox" name="CML2_LINK_IS_XML" value="Y" <? echo ($CML2_LINK_IS_XML == 'Y' ? 'checked' : ''); ?>>
			</td>
		</tr>
	<?
	}
	?><tr>
		<td valign="top" width="40%"><label for="export_files"><? echo GetMessage('CAT_ADM_CSV_EXP_EXPORT_FILES'); ?>:</label></td>
		<td valign="top" width="60%">
			<input type="hidden" name="export_files" id="export_files_N" value="N">
			<input type="checkbox" name="export_files" id="export_files_Y" value="Y" <? echo (isset($export_files) && $export_files == 'Y' ? 'checked' : ''); ?>>
		</td>
	</tr><?
	$boolHaveClouds = false;
	if ($boolHaveClouds)
	{
		?>
	<tr>
		<td valign="top" width="40%"><label for="export_from_clouds"><? echo GetMessage('CAT_ADM_CSV_EXP_EXPORT_FROM_CLOUDS'); ?>:</label></td>
		<td valign="top" width="60%">
			<input type="hidden" name="export_from_clouds" id="export_from_clouds_N" value="N">
			<input type="checkbox" name="export_from_clouds" id="export_from_clouds_Y" value="Y" <? echo (isset($export_from_clouds) && $export_from_clouds == 'Y' ? 'checked' : ''); ?>>
		</td>
	</tr><?
	}
	else
	{
		?><input type="hidden" name="export_from_clouds" id="export_from_clouds_N" value="N"><?
	}

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
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("CATI_DATA_FILE_NAME") ?></td>
	</tr>
	<tr>
		<td valign="top" width="40%"><?echo GetMessage("CATI_DATA_FILE_NAME1") ?>:</td>
		<td valign="top" width="60%"><b><? echo htmlspecialcharsex($strCatalogDefaultFolder); ?></b>
			<input type="text" class="typeinput" name="SETUP_FILE_NAME" size="40" value="<?echo htmlspecialcharsbx($SETUP_FILE_NAME <> '' ? str_replace($strCatalogDefaultFolder, '', $SETUP_FILE_NAME): "acsv_".mt_rand(0, 999999).".csv");?>"><br>
		<small><?echo GetMessage("CATI_DATA_FILE_NAME1_DESC") ?></small>
		</td>
	</tr>

	<?if ($ACTION == "EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
	{
	?><tr class="heading">
		<td colspan="2"><?echo GetMessage("CATI_SAVE_SCHEME") ?></td>
	</tr>
	<tr>
		<td valign="top" width="40%"><?echo GetMessage("CATI_SSCHEME_NAME") ?>:</td>
		<td valign="top" width="60%"><input type="text" class="typeinput" name="SETUP_PROFILE_NAME" size="40"
			value="<?echo htmlspecialcharsbx($SETUP_PROFILE_NAME)?>"></td>
	</tr><?
	}
}
$tabControl->EndTab();


$tabControl->Buttons();

?><? echo bitrix_sessid_post();?><?
if ($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
{
	?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
}

if ($STEP < 4)
{
	$_POST['FILTER_DATA'] = $FILTER_DATA;
	?>

	<input type="hidden" name="STEP" value="<? echo $STEP+1; ?>">
	<input type="hidden" name="lang" value="<? echo LANGUAGE_ID; ?>">
	<input type="hidden" name="ACT_FILE" value="<? echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]); ?>">
	<input type="hidden" name="ACTION" value="<? echo htmlspecialcharsbx($ACTION); ?>">
		<?if ($STEP ==2)
	{
		?><input type="hidden" name="IBLOCK_ID" value="<? echo $IBLOCK_ID; ?>">
		<input type="hidden" name="SETUP_FIELDS_LIST" value="FILTER_DATA,FILTER_PREF,FILTER_INPUT,FILTER_ACTIVE,FILTER_AVAILABLE,FILTER_PRICE_MAX,FILTER_PRICE_MIN,FILTER_QUANTYTY,V"><?
	}
	if ($STEP ==3)
	{
		?><input type="hidden" name="IBLOCK_ID" value="<? echo $IBLOCK_ID; ?>">
     <?  $i=0;
	     foreach($FILTER_PREF as $fpref) {
	     	echo "<input type='hidden' name='FILTER_PREF[".$i."]' value='".$fpref."'>";
	     	$i++;
	     }
     ?>
     <?  $i=0;
	     foreach($FILTER_INPUT as $finput) {
	     	echo "<input type='hidden' name='FILTER_INPUT[".$i."]' value='".$finput."'>";
	     	$i++;
	     }
     ?>
     <?  $i=0;
	     foreach($FILTER_DATA as $fdata) {
	     	echo "<input type='hidden' name='FILTER_DATA[".$i."]' value='".$fdata."'>";
	     	$i++;
	     }
     ?>
     <?  $i=0;
	     foreach($V as $fv) {
	     	echo "<input type='hidden' name='V[".$i."]' value='".$fv."'>";
	     	$i++;
	     }
     ?>
		<input type="hidden" name="FILTER_ACTIVE" value="<? echo $FILTER_ACTIVE; ?>">
		<input type="hidden" name="FILTER_AVAILABLE" value="<? echo $FILTER_AVAILABLE; ?>">
		<input type="hidden" name="FILTER_PRICE_MAX" value="<? echo $FILTER_PRICE_MAX; ?>">
		<input type="hidden" name="FILTER_PRICE_MIN" value="<? echo $FILTER_PRICE_MIN; ?>">
		<input type="hidden" name="FILTER_QUANTYTY" value="<? echo $FILTER_QUANTYTY; ?>">
		<input type="hidden" name="SETUP_FIELDS_LIST" value="IBLOCK_ID,SETUP_FILE_NAME,fields_type,delimiter_r,delimiter_other_r,first_line_names,field_needed,field_num,field_code,export_files,export_from_clouds,CML2_LINK_IS_XML,MAX_EXECUTION_TIME,FILTER_DATA,FILTER_PREF,FILTER_INPUT,FILTER_ACTIVE,FILTER_AVAILABLE,FILTER_PRICE_MAX,FILTER_PRICE_MIN,FILTER_QUANTYTY,V"><?
	}
	if ($STEP > 1)
	{
		?><input type="submit" class="button" name="backButton" value="&lt;&lt; <?echo GetMessage("CATI_BACK") ?>"><?
	}
	?><input type="submit" class="button" value="<?echo ($STEP == 3)?(($ACTION == "EXPORT")?GetMessage("CATI_NEXT_STEP_F"):GetMessage("CET_SAVE")):GetMessage("CATI_NEXT_STEP")." &gt;&gt;" ?>" name="submit_btn"><?
}

$tabControl->End();

?></form>
<?
if ($STEP == 4)
{
	$FINITE = true;
}
?>
<script type="text/javascript">
<?if ($STEP < 2):?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
<?elseif ($STEP == 2):?>
tabControl.SelectTab("edit2");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit3");
<?elseif ($STEP == 3):?>
tabControl.SelectTab("edit3");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");
<?endif;?>
</script>