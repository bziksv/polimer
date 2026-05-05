<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.allimportexport';
CModule::IncludeModule($moduleId);
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$isAdmin = $USER->IsAdmin();
function GetFieldEextraVal($arSettings, $name)
{
	if(!is_array($arSettings)) return '';
	$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
	$fnPart = str_replace('[FIELDS_LIST]', '', $oRequest->get('field_name'));
	$fName = 'EXTRA'.$fnPart.'['.$name.']';
	if(preg_match_all('/\[([^\]]*)\]/Us', $fnPart, $m))
	{
		foreach($m[1] as $key) $arSettings = (isset($arSettings[$key]) ? $arSettings[$key] : array());
	}
	$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
	$val = (isset($arSettings[$name]) ? $arSettings[$name] : '');
	return array($fName, $val);
}

$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
$entityId = $oRequest->get('entity');
$profileId = $oRequest->get('PROFILE_ID');
$fieldName = htmlspecialcharsex($oRequest->get('field_name'));
$field = $oRequest->get('field');

$oProfile = \Bitrix\EsolAie\Export\Profile::getInstance($entityId);
$arProfile = $oProfile->GetByID($profileId);

$fl = \Bitrix\EsolAie\Export\FieldList::getInstance($entityId);

$oProfile->ApplyExtra($PEXTRASETTINGS, $profileId);
if(array_key_exists('POSTEXTRA', $_POST))
{
	$arFieldParams = $_POST['POSTEXTRA'];
	if(!defined('BX_UTF') || !BX_UTF)
	{
		$arFieldParams = $APPLICATION->ConvertCharset($arFieldParams, 'UTF-8', 'CP1251');
	}
	if($arFieldParams) $arFieldParams = \Bitrix\EsolAie\Utils::JsObjectToPhp($arFieldParams);
	if(!$arFieldParams) $arFieldParams = array();
	$fName = 'EXTRA'.str_replace('[FIELDS_LIST]', '', $fieldName);
	/*
	$fNameEval = strtr($fName, array("["=>"['", "]"=>"']"));
	$command = '$arFieldsParamsInArray = &$P'.$fNameEval.';';
	eval($command);
	*/
	$arFieldsParamsInArray = &$PEXTRASETTINGS;
	if(preg_match_all('/\[([^\]]*)\]/Us', $fName, $m))
	{
		foreach($m[1] as $key)
		{
			if(!is_array($arFieldsParamsInArray) || !array_key_exists($key, $arFieldsParamsInArray)) $arFieldsParamsInArray[$key] = array();
			$arFieldsParamsInArray = &$arFieldsParamsInArray[$key];
		}
	}
	$arFieldsParamsInArray = $arFieldParams;
}

if($_POST['action']=='save' && is_array($_POST['EXTRASETTINGS']))
{
	define('PUBLIC_AJAX_MODE', 'Y');
	\Bitrix\EsolAie\Export\Extrasettings::HandleParams($PEXTRASETTINGS, $_POST['EXTRASETTINGS']);
	preg_match_all('/\[([_\d]+)\]/', $_GET['field_name'], $keys);
	$oid = 'field_settings_'.$keys[1][0].'_'.$keys[1][1];

	$APPLICATION->RestartBuffer();
	ob_end_clean();
	
	if($_GET['return_data'])
	{
		$returnJson = (empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]) ? '""' : \Bitrix\EsolAie\Utils::PhpToJSObject($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]));
		echo '<script>EList.SetExtraParams("'.$oid.'", '.$returnJson.')</script>';
	}
	else
	{
		$oProfile->UpdateExtra($profileId, $PEXTRASETTINGS);
		$isEmpty = (empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]));
		echo '<script>ESettings.OnSettingsSave("'.$oid.'", '.($isEmpty ? 'false' : 'true').');</script>';
	}
	die();
}

/*$ee = new CKDAExportExcel();
$bPicture = $ee->IsPictureField($field);
$bMultipleProp = $ee->IsMultipleField($field);*/

$bPicture = $fl->IsPictureField($field, true);

$arFields = $fl->GetEntityFieldsForFilter();
foreach($arFields as $k=>$v) $arFields[$k] = $v['title'];
$arFields = array(array(
	'TITLE' => GetMessage("KDA_EE_GROUP_FIELDS_TITLE"),
	'FIELDS' => $arFields
));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings"<?if(!$isAdmin){echo ' data-notadmin="1"';}?>>
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">

		<?
		$entityDataClass = $fl->GetEntityClass();
		if(is_callable(array($entityDataClass, 'GetExportFieldSettings')))
		{
			$arData = $entityDataClass::GetExportFieldSettings($field);
			foreach($arData as $arDataItem)
			{
				if(array_key_exists('NOTE', $arDataItem))
				{
					?>
					<tr>
						<td colspan="2">
							<?
							echo BeginNote().$arDataItem['NOTE'].EndNote();
							?>
						</td>
					</tr>
					<?
				}
				?>
				<tr>
					<td class="adm-detail-content-cell-l"><?echo $arDataItem['TITLE'];?>:</td>
					<td class="adm-detail-content-cell-r">
						<?
						list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, $arDataItem['NAME']);
						if($arDataItem['TYPE']=='SELECT')
						{
							if($arDataItem['MULTIPLE']=='Y') echo '<select name="'.$fName.'[]" multiple>';
							else echo '<select name="'.$fName.'">';
							foreach($arDataItem['OPTIONS'] as $arOption)
							{
								echo '<option value="'.htmlspecialcharsbx($arOption['VALUE']).'"'.(($arDataItem['MULTIPLE']=='Y' ? (is_array($val) && (bool)in_array($arOption['VALUE'], $val)) : (bool)($arOption['VALUE']==$val)) ? 'selected' : '').'>'.htmlspecialcharsbx($arOption['TITLE']).'</option>';
							}
							echo '</select>';
						}
						else
						{
							echo '<input type="text" name="'.$fName.'"  value="'.htmlspecialcharsbx($val).'">';
						}
						?>
					</td>
				</tr>
				<?
			}
		}
		?>
		
		<?if($bPicture){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_INSERT_PICTURE");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'INSERT_PICTURE');
					$insertPic = $val;
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="ESettings.ToggleSubfields(this)">
					&nbsp; <?echo GetMessage("KDA_EE_SETTINGS_INSERT_PICTURE_NOTE");?>
				</td>
			</tr>
			<tr class="subfield" <?if($insertPic!='Y'){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PICTURE_WIDTH");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PICTURE_WIDTH');
					?>
					<input type="text" name="<?=$fName?>"  value="<?=htmlspecialcharsex($val)?>" placeholder="100">
				</td>
			</tr>
			<tr class="subfield" <?if($insertPic!='Y'){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PICTURE_HEIGHT");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PICTURE_HEIGHT');
					?>
					<input type="text" name="<?=$fName?>"  value="<?=htmlspecialcharsex($val)?>" placeholder="100">
				</td>
			</tr>
		<?}?>
		
		<?if($bMultipleProp){?>
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_EE_SETTINGS_CHANGE_MULTIPLE_SEPARATOR");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'CHANGE_MULTIPLE_SEPARATOR');
					list($fName2, $val2) = GetFieldEextraVal($PEXTRASETTINGS, 'MULTIPLE_SEPARATOR');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="$('#multiple_separator').css('display', (this.checked ? '' : 'none'));"><br>
					<input type="text" id="multiple_separator" name="<?=$fName2?>" value="<?=htmlspecialcharsex($val2)?>" placeholder="<?echo GetMessage("KDA_EE_SETTINGS_MULTIPLE_SEPARATOR_PLACEHOLDER");?>" <?=($val!='Y' ? 'style="display: none"' : '')?>>
				</td>
			</tr>
			
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_EE_SETTINGS_MULTIPLE_SEPARATE_BY_ROWS");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'MULTIPLE_SEPARATE_BY_ROWS');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			
			<tr>
				<td class="adm-detail-content-cell-l" valign="top"><?echo GetMessage("KDA_EE_SETTINGS_MULTIPLE_FROM_VALUE");?>:<br><small><?echo GetMessage("KDA_EE_SETTINGS_MULTIPLE_FROM_VALUE_COMMENT");?></small></td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName1, $val1) = GetFieldEextraVal($PEXTRASETTINGS, 'MULTIPLE_FROM_VALUE');
					list($fName2, $val2) = GetFieldEextraVal($PEXTRASETTINGS, 'MULTIPLE_TO_VALUE');
					?>
					<input type="text" size="5" name="<?=$fName1?>" value="<?echo htmlspecialcharsex($val1);?>" placeholder="1">
					<?echo GetMessage("KDA_EE_SETTINGS_MULTIPLE_TO_VALUE");?>
					<input type="text" size="5" name="<?=$fName2?>" value="<?echo htmlspecialcharsex($val2);?>">
				</td>
			</tr>
		<?}?>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_EE_SETTINGS_CONVERSION_TITLE");?></td>
		</tr>
		<tr>
			<td class="kda-ee-settings-margin-container" colspan="2">
				<?
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'CONVERSION');
				if(!is_array($arVals)) $arVals = array();
				$showCondition = true;
				if(!is_array($arVals) || count($arVals)==0)
				{
					$showCondition = false;
					$arVals = array(
						array(
							'WHEN' => '',
							'FROM' => '',
							'THEN' => '',
							'TO' => ''
						)
					);
				}
				
				foreach($arVals as $k=>$v)
				{
					$isDisabled = (bool)($v['THEN']=='EXPRESSION' && !$isAdmin);
					$cellsOptions = '<option value="">'.sprintf(GetMessage("KDA_EE_SETTINGS_CONVERSION_CELL_CURRENT"), $i).'</option>';
					foreach($arFields as $k=>$arGroup)
					{
						if(is_array($arGroup['FIELDS']))
						{
							$cellsOptions .= '<optgroup label="'.$arGroup['TITLE'].'">';
							foreach($arGroup['FIELDS'] as $gkey=>$gfield)
							{
								$cellsOptions .= '<option value="'.$gkey.'"'.($v['CELL']==$gkey ? ' selected' : '').'>'.$gfield.'</option>';
							}
							$cellsOptions .= '</optgroup>';
						}
					}
					$cellsOptions .= '<optgroup label="'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CELL_GROUP_OTHER").'">';
					$cellsOptions .= '<option value="ELSE"'.($v['CELL']=='ELSE' ? ' selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CELL_ELSE").'</option>';
					$cellsOptions .= '</optgroup>';
					
					
					echo '<div class="kda-ee-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
							GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_TITLE").
							' <select name="'.$fName.'[CELL][]" class="field_cell">'.
								$cellsOptions.
							'</select> '.
							' <select name="'.$fName.'[WHEN][]" class="field_when">'.
								'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
								'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
								'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
								'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
								'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
								'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
								'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
								'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
								'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
								'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
								'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'<option value="NOT_REGEXP" '.($v['WHEN']=='NOT_REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_NOT_REGEXP").'</option>'.
								'<option value="ANY" '.($v['WHEN']=='ANY' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_ANY").'</option>'.
							'</select> '.
							'<input type="text" class="field_from" name="'.$fName.'[FROM][]" value="'.htmlspecialcharsex($v['FROM']).'"> '.
							GetMessage("KDA_EE_SETTINGS_CONVERSION_CONDITION_THEN").
							' <select class="field_then" name="'.$fName.'[THEN][]">'.
								'<optgroup label="'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_GROUP_STRING").'">'.
									'<option value="REPLACE_TO" '.($v['THEN']=='REPLACE_TO' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_REPLACE_TO").'</option>'.
									'<option value="REMOVE_SUBSTRING" '.($v['THEN']=='REMOVE_SUBSTRING' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_REMOVE_SUBSTRING").'</option>'.
									'<option value="REPLACE_SUBSTRING_TO" '.($v['THEN']=='REPLACE_SUBSTRING_TO' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_REPLACE_SUBSTRING_TO").'</option>'.
									'<option value="ADD_TO_BEGIN" '.($v['THEN']=='ADD_TO_BEGIN' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_ADD_TO_BEGIN").'</option>'.
									'<option value="ADD_TO_END" '.($v['THEN']=='ADD_TO_END' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_ADD_TO_END").'</option>'.
									'<option value="TRANSLIT" '.($v['THEN']=='TRANSLIT' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_TRANSLIT").'</option>'.
									'<option value="STRIP_TAGS" '.($v['THEN']=='STRIP_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_STRIP_TAGS").'</option>'.
									'<option value="CLEAR_TAGS" '.($v['THEN']=='CLEAR_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_CLEAR_TAGS").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_GROUP_MATH").'">'.
									'<option value="MATH_ROUND" '.($v['THEN']=='MATH_ROUND' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_MATH_ROUND").'</option>'.
									'<option value="MATH_MULTIPLY" '.($v['THEN']=='MATH_MULTIPLY' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_MATH_MULTIPLY").'</option>'.
									'<option value="MATH_DIVIDE" '.($v['THEN']=='MATH_DIVIDE' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_MATH_DIVIDE").'</option>'.
									'<option value="MATH_ADD" '.($v['THEN']=='MATH_ADD' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_MATH_ADD").'</option>'.
									'<option value="MATH_SUBTRACT" '.($v['THEN']=='MATH_SUBTRACT' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_MATH_SUBTRACT").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_GROUP_OTHER").'">'.
									'<option value="SKIP_LINE" '.($v['THEN']=='SKIP_LINE' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_SKIP_LINE").'</option>'.
									'<option value="ADD_LINK" '.($v['THEN']=='ADD_LINK' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_ADD_LINK").'</option>'.
									'<option value="EXPRESSION" '.($v['THEN']=='EXPRESSION' ? 'selected' : '').'>'.GetMessage("KDA_EE_SETTINGS_CONVERSION_THEN_EXPRESSION").'</option>'.
								'</optgroup>'.
							'</select> '.
							'<input class="field_to" type="text" name="'.$fName.'[TO][]" value="'.htmlspecialcharsex($v['TO']).'"'.($isDisabled ? ' readonly' : '').'>'.
							'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this)"'.($isDisabled ? ' disabled' : '').'>'.
							'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("KDA_EE_SETTINGS_DELETE").'" class="delete"></a>'.
						 '</div>';
				}
				?>
				<a href="javascript:void(0)" onclick="ESettings.AddConversion(this)"><?echo GetMessage("KDA_EE_SETTINGS_CONVERSION_ADD_VALUE");?></a>
			</td>
		</tr>
		
		<?if($bPrice){?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("KDA_EE_SETTINGS_PRICE_TITLE");?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PRICE_USE_LANG_SETTINGS");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PRICE_USE_LANG_SETTINGS');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PRICE_SHOW_CURRENCY");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PRICE_SHOW_CURRENCY');
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PRICE_CONVERT_CURRENCY");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PRICE_CONVERT_CURRENCY');
					$convertCurrency = $val;
					?>
					<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?> onchange="ESettings.ToggleSubfields(this)">
				</td>
			</tr>
			<tr class="subfield" <?if($convertCurrency!='Y'){echo 'style="display: none;"';}?>>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_PRICE_CONVERT_CURRENCY_TO");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PRICE_CONVERT_CURRENCY_TO');
					?>
					<select name="<?=$fName?>">
					<?
					foreach($arCurrency as $item)
					{
						?><option value="<?echo $item['CURRENCY']?>"<?if($val==$item['CURRENCY']){echo 'selected';}?>>[<?echo $item['CURRENCY']?>] <?echo $item['FULL_NAME']?></option><?
					}
					?>
					</select>
				</td>
			</tr>
		<?}?>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_EE_SETTINGS_DISPLAY_TITLE");?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_SETTINGS_DISPLAY_WIDTH");?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'DISPLAY_WIDTH');
				?>
				<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="200">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_TEXT_ALIGN"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'TEXT_ALIGN');
				?>
				<select name="<?=$fName?>">
					<option value=""><?echo GetMessage("KDA_EE_NOT_CHANGE"); ?></option>
					<option value="LEFT" <?if($val=='LEFT'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_TEXT_ALIGN_LEFT"); ?></option>
					<option value="CENTER" <?if($val=='CENTER'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_TEXT_ALIGN_CENTER"); ?></option>
					<option value="RIGHT" <?if($val=='RIGHT'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_TEXT_ALIGN_RIGHT"); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_VERTICAL_ALIGN"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'VERTICAL_ALIGN');
				?>
				<select name="<?=$fName?>">
					<option value=""><?echo GetMessage("KDA_EE_NOT_CHANGE"); ?></option>
					<option value="TOP" <?if($val=='TOP'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_VERTICAL_ALIGN_TOP"); ?></option>
					<option value="CENTER" <?if($val=='CENTER'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_VERTICAL_ALIGN_CENTER"); ?></option>
					<option value="BOTTOM" <?if($val=='BOTTOM'){echo 'selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_VERTICAL_ALIGN_BOTTOM"); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_FONT_COLOR"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'FONT_COLOR');
				?>
				<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="#ffffff">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_BACKGROUND_COLOR"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'BACKGROUND_COLOR');
				?>
				<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="#ffffff">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_FONT_FAMILY"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'FONT_FAMILY');
				?>
				<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="Calibri">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_FONT_SIZE"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'FONT_SIZE');
				?>
				<input type="text" name="<?=$fName?>" value="<?=htmlspecialcharsex($val)?>" placeholder="11">
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_FONT_STYLE_BOLD"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'STYLE_BOLD');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_FONT_STYLE_ITALIC"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'STYLE_ITALIC');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'NUMBER_FORMAT');
				?>
				<select name="<?=$fName?>">
					<option value=""><?echo GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_GENERAL"); ?></option>
					<option value="1"<?if($val=='1'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_NUMERIC"), '1234'); ?></option>
					<option value="3"<?if($val=='3'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_NUMERIC"), '1 234'); ?></option>
					<option value="2"<?if($val=='2'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_NUMERIC"), '1234,10'); ?></option>
					<option value="4"<?if($val=='4'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_NUMERIC"), '1 234,10'); ?></option>
					<option value="5"<?if($val=='5'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_FINANCIAL"), '1 234 P'); ?></option>
					<option value="7"<?if($val=='7'){echo ' selected';}?>><?echo sprintf(GetMessage("KDA_EE_DISPLAY_NUMBER_FORMAT_FINANCIAL"), '1 234,10 P'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_PROTECTION"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'PROTECTION');
				?>
				<select name="<?=$fName?>">
					<option value=""><?echo GetMessage("KDA_EE_DISPLAY_PROTECTION_ENABLE"); ?></option>
					<option value="N"<?if($val=='N'){echo ' selected';}?>><?echo GetMessage("KDA_EE_DISPLAY_PROTECTION_DISABLE"); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_EE_DISPLAY_MAKE_DROPDOWN"); ?>:</td>
			<td class="adm-detail-content-cell-r">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'MAKE_DROPDOWN');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		
	</table>
</form>
<script>
var admKDASettingMessages = <?echo \Bitrix\EsolAie\Utils::PhpToJSObject($arFields)?>;
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>