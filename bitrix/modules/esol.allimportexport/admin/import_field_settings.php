<?
use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\EsolAie\Import;
	
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.allimportexport';
Loader::includeModule($moduleId);
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
$tblField = htmlspecialcharsex($oRequest->get('field'));

$oProfile = \Bitrix\EsolAie\Import\Profile::getInstance($entityId);
$arProfile = $oProfile->GetByID($profileId);
$SETTINGS_DEFAULT = $arProfile['SETTINGS_DEFAULT'];

$fl = \Bitrix\EsolAie\Import\FieldList::getInstance($entityId);

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
	$APPLICATION->RestartBuffer();
	ob_end_clean();

	\Bitrix\EsolAie\Import\Extrasettings::HandleParams($PEXTRASETTINGS, $_POST['EXTRASETTINGS']);
	preg_match_all('/\[([_\d]+[_P\d]*)\]/', $fieldName, $keys);
	$oid = 'field_settings_'.$keys[1][0].'_'.$keys[1][1];
	
	if($_GET['return_data'])
	{
		$returnJson = (empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]) ? '""' : \Bitrix\EsolAie\Utils::PhpToJSObject($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]]));
		echo '<script>EList.SetExtraParams("'.$oid.'", '.$returnJson.')</script>';
	}
	else
	{
		$oProfile->UpdateExtra($profileId, $PEXTRASETTINGS);
		if(!empty($PEXTRASETTINGS[$keys[1][0]][$keys[1][1]])) echo '<script>$("#'.$oid.'").removeClass("inactive");</script>';
		else echo '<script>$("#'.$oid.'").addClass("inactive");</script>';
		echo '<script>BX.WindowManager.Get().Close();</script>';
	}
	die();
}

$arFields = $fl->GetEntityFieldsForFilter();
foreach($arFields as $k=>$v) $arFields[$k] = $v['title'];
$arFields = array(array(
	'TITLE' => GetMessage("KDA_EE_GROUP_FIELDS_TITLE"),
	'FIELDS' => $arFields
));

$bPicture = false;

$countCols = intval($_REQUEST['count_cols']);	

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");

$arRelFields = $fl->GetRelsByField($tblField);
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings"<?if(!$isAdmin){echo ' data-notadmin="1"';}?>>
	<input type="hidden" name="action" value="save">
	<table width="100%">
		<col width="50%">
		<col width="50%">
		
		<?if(is_array($arRelFields) && count($arRelFields) > 1){?>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_REL_FIELD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'REL_FIELD');
					?>
					<select name="<?echo $fName;?>" class="chosen" style="max-width: 450px;">
						<?
						foreach($arRelFields as $k=>$arRelField)
						{
							echo '<option value="'.$k.'"'.(($val==$k || (!$val && $arRelField['primary'])) ? ' selected' : '').'>'.$arRelField['title'].'</option>';
						}
						?>
					</select>
				</td>
			</tr>
		<?}?>
		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_TITLE");?></td>
		</tr>
		<tr>
			<td class="kda-ie-settings-margin-container" colspan="2">
				<?
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'CONVERSION');
				if(!is_array($arVals)) $arVals = array();
				$showCondition = true;
				if(!is_array($arVals) || count($arVals)==0)
				{
					$showCondition = false;
					$arVals = array(
						array(
							'CELL' => '',
							'WHEN' => '',
							'FROM' => '',
							'THEN' => '',
							'TO' => ''
						)
					);
				}
				
				$arColLetters = range('A', 'Z');
				foreach(range('A', 'Z') as $v1)
				{
					foreach(range('A', 'Z') as $v2)
					{
						$arColLetters[] = $v1.$v2;
					}
				}
				
				foreach($arVals as $k=>$v)
				{
					$isDisabled = (bool)($v['THEN']=='EXPRESSION' && !$isAdmin);
					$cellsOptions = '<option value="">'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_CURRENT"), $i).'</option>';
					for($i=1; $i<=$countCols; $i++)
					{
						$cellsOptions .= '<option value="'.$i.'"'.($v['CELL']==$i ? ' selected' : '').'>'.sprintf(GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_NUMBER"), $i, $arColLetters[$i-1]).'</option>';
					}
					$cellsOptions .= /*'<option value="GROUP_AND"'.($v['CELL']=='GROUP_AND' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_AND").'</option>'.
						'<option value="GROUP_OR"'.($v['CELL']=='GROUP_OR' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_GROUP_OR").'</option>'.*/
						'<option value="ELSE"'.($v['CELL']=='ELSE' ? ' selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CELL_ELSE").'</option>';
					echo '<div class="kda-ie-settings-conversion" '.(!$showCondition ? 'style="display: none;"' : '').'>'.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_TITLE").
							' <select name="'.$fName.'[CELL][]" class="field_cell">'.
								$cellsOptions.
							'</select> '.
							' <select name="'.$fName.'[WHEN][]" class="field_when">'.
								'<option value="EQ" '.($v['WHEN']=='EQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EQ").'</option>'.
								'<option value="NEQ" '.($v['WHEN']=='NEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NEQ").'</option>'.
								'<option value="GT" '.($v['WHEN']=='GT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GT").'</option>'.
								'<option value="LT" '.($v['WHEN']=='LT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LT").'</option>'.
								'<option value="GEQ" '.($v['WHEN']=='GEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_GEQ").'</option>'.
								'<option value="LEQ" '.($v['WHEN']=='LEQ' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_LEQ").'</option>'.
								'<option value="CONTAIN" '.($v['WHEN']=='CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_CONTAIN").'</option>'.
								'<option value="NOT_CONTAIN" '.($v['WHEN']=='NOT_CONTAIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_CONTAIN").'</option>'.
								'<option value="EMPTY" '.($v['WHEN']=='EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_EMPTY").'</option>'.
								'<option value="NOT_EMPTY" '.($v['WHEN']=='NOT_EMPTY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_EMPTY").'</option>'.
								'<option value="REGEXP" '.($v['WHEN']=='REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_REGEXP").'</option>'.
								'<option value="NOT_REGEXP" '.($v['WHEN']=='NOT_REGEXP' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_NOT_REGEXP").'</option>'.
								'<option value="ANY" '.($v['WHEN']=='ANY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_ANY").'</option>'.
							'</select> '.
							'<input type="text" name="'.$fName.'[FROM][]" class="field_from" value="'.htmlspecialcharsex($v['FROM']).'"> '.
							GetMessage("KDA_IE_SETTINGS_CONVERSION_CONDITION_THEN").
							' <select class="field_then" name="'.$fName.'[THEN][]">'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_STRING").'">'.
									'<option value="REPLACE_TO" '.($v['THEN']=='REPLACE_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_TO").'</option>'.
									'<option value="REMOVE_SUBSTRING" '.($v['THEN']=='REMOVE_SUBSTRING' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REMOVE_SUBSTRING").'</option>'.
									'<option value="REPLACE_SUBSTRING_TO" '.($v['THEN']=='REPLACE_SUBSTRING_TO' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_REPLACE_SUBSTRING_TO").'</option>'.
									'<option value="ADD_TO_BEGIN" '.($v['THEN']=='ADD_TO_BEGIN' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_BEGIN").'</option>'.
									'<option value="ADD_TO_END" '.($v['THEN']=='ADD_TO_END' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_ADD_TO_END").'</option>'.
									'<option value="LCASE" '.($v['THEN']=='LCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_LCASE").'</option>'.
									'<option value="UCASE" '.($v['THEN']=='UCASE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UCASE").'</option>'.
									'<option value="UFIRST" '.($v['THEN']=='UFIRST' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UFIRST").'</option>'.
									'<option value="UWORD" '.($v['THEN']=='UWORD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_UWORD").'</option>'.
									'<option value="TRANSLIT" '.($v['THEN']=='TRANSLIT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_TRANSLIT").'</option>'.
									'<option value="STRIP_TAGS" '.($v['THEN']=='STRIP_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_STRIP_TAGS").'</option>'.
									'<option value="CLEAR_TAGS" '.($v['THEN']=='CLEAR_TAGS' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_CLEAR_TAGS").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_MATH").'">'.
									'<option value="MATH_ROUND" '.($v['THEN']=='MATH_ROUND' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ROUND").'</option>'.
									'<option value="MATH_MULTIPLY" '.($v['THEN']=='MATH_MULTIPLY' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_MULTIPLY").'</option>'.
									'<option value="MATH_DIVIDE" '.($v['THEN']=='MATH_DIVIDE' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_DIVIDE").'</option>'.
									'<option value="MATH_ADD" '.($v['THEN']=='MATH_ADD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_ADD").'</option>'.
									'<option value="MATH_SUBTRACT" '.($v['THEN']=='MATH_SUBTRACT' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_MATH_SUBTRACT").'</option>'.
								'</optgroup>'.
								'<optgroup label="'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_GROUP_OTHER").'">'.
									'<option value="NOT_LOAD" '.($v['THEN']=='NOT_LOAD' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_NOT_LOAD").'</option>'.
									'<option value="EXPRESSION" '.($v['THEN']=='EXPRESSION' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_CONVERSION_THEN_EXPRESSION").'</option>'.
								'</optgroup>'.
							'</select> '.
							'<input class="field_to" type="text" name="'.$fName.'[TO][]" value="'.htmlspecialcharsex($v['TO']).'"'.($isDisabled ? ' readonly' : '').'>'.
							'<input class="choose_val" value="..." type="button" onclick="ESettings.ShowChooseVal(this, '.$countCols.')"'.($isDisabled ? ' disabled' : '').'>'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionUp(this)" title="'.GetMessage("KDA_IE_SETTINGS_UP").'" class="up"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.ConversionDown(this)" title="'.GetMessage("KDA_IE_SETTINGS_DOWN").'" class="down"></a>'.
							'<a href="javascript:void(0)" onclick="ESettings.RemoveConversion(this)" title="'.GetMessage("KDA_IE_SETTINGS_DELETE").'" class="delete"></a>'.
							(!$isAdmin ? '<input type="hidden" name="'.$fName.'[INDEX][]" value="'.htmlspecialcharsbx(strlen($v['INDEX']) > 0 ? $v['INDEX'] : $k).'">' : '').
						 '</div>';
				}
				?>
				<a href="javascript:void(0)" onclick="return ESettings.AddConversion(this, event);" title="<?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_ADD_HINT");?>"><?echo GetMessage("KDA_IE_SETTINGS_CONVERSION_ADD_VALUE");?></a>
			</td>
		</tr>
		
		<?
		if($bPicture)
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
			Loader::includeModule('iblock');
			$arFieldNames = array(
				'SCALE',
				'WIDTH',
				'HEIGHT',
				'IGNORE_ERRORS_DIV',
				'IGNORE_ERRORS',
				'METHOD_DIV',
				'METHOD',
				'COMPRESSION',
				'USE_WATERMARK_FILE',
				'WATERMARK_FILE',
				'WATERMARK_FILE_ALPHA',
				'WATERMARK_FILE_POSITION',
				'USE_WATERMARK_TEXT',
				'WATERMARK_TEXT',
				'WATERMARK_TEXT_FONT',
				'WATERMARK_TEXT_COLOR',
				'WATERMARK_TEXT_SIZE',
				'WATERMARK_TEXT_POSITION',
			);
			$arFields = array();
			foreach($arFieldNames as $k=>$field)
			{
				list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'PICTURE_PROCESSING');
				$arFields[$field] = array(
					'NAME' => $fName.'['.$field.']',
					'VALUE' => (is_array($arVals) ? $arVals[$field] : '')
				);
			}
			?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_PICTURE_PROCESSING"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"></td>
				<td class="adm-detail-content-cell-r">
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['SCALE']['NAME']?>"
							name="<?echo $arFields['SCALE']['NAME']?>"
							<?
							if($arFields['SCALE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['WIDTH']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['HEIGHT']['NAME']?>').style.display =
								/*BX('DIV_<?echo $arFields['IGNORE_ERRORS_DIV']['NAME']?>').style.display =*/
								BX('DIV_<?echo $arFields['METHOD_DIV']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['COMPRESSION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['SCALE']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_SCALE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WIDTH']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WIDTH")?>:&nbsp;<input name="<?echo $arFields['WIDTH']['NAME']?>" type="text" value="<?echo htmlspecialcharsbx($arFields['WIDTH']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['HEIGHT']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_HEIGHT")?>:&nbsp;<input name="<?echo $arFields['HEIGHT']['NAME']?>" type="text" value="<?echo htmlspecialcharsbx($arFields['HEIGHT']['VALUE'])?>" size="7">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['IGNORE_ERRORS_DIV']['NAME']?>"
					style="padding-left:16px;display:<?
						//echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
						echo 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
							name="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
							<?
							if($arFields['IGNORE_ERRORS']['VALUE']==="Y")
								echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['IGNORE_ERRORS']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_IGNORE_ERRORS")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['METHOD_DIV']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['METHOD']['NAME']?>"
							name="<?echo $arFields['METHOD']['NAME']?>"
							<?
								if($arFields['METHOD']['VALUE']==="Y")
									echo "checked";
							?>
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['METHOD']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_METHOD")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['COMPRESSION']['NAME']?>"
					style="padding-left:16px;display:<?
						echo ($arFields['SCALE']['VALUE']==="Y")? 'block': 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_COMPRESSION")?>:&nbsp;<input
						name="<?echo $arFields['COMPRESSION']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['COMPRESSION']['VALUE'])?>"
						style="width: 30px"
					>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
							name="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
							<?
							if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_FILE_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_USE_WATERMARK_FILE")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['USE_WATERMARK_FILE']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?CAdminFileDialog::ShowScript(array(
						"event" => "BtnClick".strtr($fieldName, array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_FILE']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'jpg,jpeg,png,gif',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_FILE")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_FILE']['NAME']?>"
						id="<?echo strtr($arFields['WATERMARK_FILE']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_FILE']['VALUE'])?>"
						size="35"
					>&nbsp;<input type="button" value="..." onClick="BtnClick<?echo strtr($fieldName, array('['=>'_', ']'=>'_'))?>()">
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_FILE_ALPHA")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_FILE_ALPHA']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_FILE_ALPHA']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_FILE_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_FILE']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?echo SelectBox(
						$arFields['WATERMARK_FILE_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_FILE_POSITION']['VALUE']
					);?>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control">
						<input
							type="checkbox"
							value="Y"
							id="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							name="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
							<?
							if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y")
								echo "checked";
							?>
							onclick="
								BX('DIV_<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>').style.display =
								BX('DIV_<?echo $arFields['WATERMARK_TEXT_POSITION']['NAME']?>').style.display =
								this.checked? 'block': 'none';
							"
						>
					</div>
					<div class="adm-list-label">
						<label
							for="<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
						><?echo GetMessage("KDA_IE_PICTURE_USE_WATERMARK_TEXT")?></label>
					</div>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['USE_WATERMARK_TEXT']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT']['VALUE'])?>"
						size="35"
					>
					<?CAdminFileDialog::ShowScript(array(
						"event" => "BtnClickFont".strtr($fieldName, array('['=>'_', ']'=>'_')),
						"arResultDest" => array("ELEMENT_ID" => strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))),
						"arPath" => array("PATH" => GetDirPath($arFields['WATERMARK_TEXT_FONT']['VALUE'])),
						"select" => 'F',// F - file only, D - folder only
						"operation" => 'O',// O - open, S - save
						"showUploadTab" => true,
						"showAddToMenuTab" => false,
						"fileFilter" => 'ttf',
						"allowAllFiles" => false,
						"SaveConfig" => true,
					));?>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT_FONT")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_FONT']['NAME']?>"
						id="<?echo strtr($arFields['WATERMARK_TEXT_FONT']['NAME'], array('['=>'_', ']'=>'_'))?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_FONT']['VALUE'])?>"
						size="35">&nbsp;<input
						type="button"
						value="..."
						onClick="BtnClickFont<?echo strtr($fieldName, array('['=>'_', ']'=>'_'))?>()"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_TEXT_COLOR")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						id="<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_COLOR']['VALUE'])?>"
						size="7"
					><script>
						function EXTRA_WATERMARK_TEXT_COLOR(color)
						{
							BX('<?echo $arFields['WATERMARK_TEXT_COLOR']['NAME']?>').value = color.substring(1);
						}
					</script>&nbsp;<input
						type="button"
						value="..."
						onclick="BX.findChildren(this.parentNode, {'tag': 'IMG'}, true)[0].onclick();"
					><span style="float:left;width:1px;height:1px;visibility:hidden;position:absolute;"><?
						$APPLICATION->IncludeComponent(
							"bitrix:main.colorpicker",
							"",
							array(
								"SHOW_BUTTON" =>"Y",
								"ONSELECT" => "EXTRA_WATERMARK_TEXT_COLOR",
							)
						);
					?></span>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['USE_WATERMARK_TEXT']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_SIZE")?>:&nbsp;<input
						name="<?echo $arFields['WATERMARK_TEXT_SIZE']['NAME']?>"
						type="text"
						value="<?echo htmlspecialcharsbx($arFields['WATERMARK_TEXT_SIZE']['VALUE'])?>"
						size="3"
					>
				</div>
				<div class="adm-list-item"
					id="DIV_<?echo $arFields['WATERMARK_TEXT_POSITION']['NAME']?>"
					style="padding-left:16px;display:<?
						if($arFields['WATERMARK_TEXT_POSITION']['VALUE']==="Y") echo 'block'; else echo 'none';
					?>"
				>
					<?echo GetMessage("KDA_IE_PICTURE_WATERMARK_POSITION")?>:&nbsp;<?echo SelectBox(
						$arFields['WATERMARK_TEXT_POSITION']['NAME'],
						IBlockGetWatermarkPositions(),
						"",
						$arFields['WATERMARK_TEXT_POSITION']['VALUE']
					);?>
				</div>
				</td>
			</tr>
		<?}?>
		
		<?if(1){?>
			<tr class="heading">
				<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_FILTER"); ?></td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_UPLOAD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'UPLOAD_VALUES');
					if(!is_array($arVals)) $arVals = array();
					$fName .= '[]';
					if(!is_array($arVals) || count($arVals) == 0)
					{
						$arVals = array('');
					}
					foreach($arVals as $k=>$v)
					{
						$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
						$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
								'<option value="">'.GetMessage("KDA_IE_SETTINGS_FILTER_VAL").'</option>'.
								'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_EMPTY").'</option>'.
								'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
							'</select>';
						echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsex($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
					}
					?>
					<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?echo GetMessage("KDA_IE_ADD_VALUE");?></a>
				</td>
			</tr>
			<tr>
				<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_NOT_UPLOAD");?>:</td>
				<td class="adm-detail-content-cell-r">
					<?
					list($fName, $arVals) = GetFieldEextraVal($PEXTRASETTINGS, 'NOT_UPLOAD_VALUES');
					if(!is_array($arVals)) $arVals = array();
					$fName .= '[]';
					if(!is_array($arVals) || count($arVals) == 0)
					{
						$arVals = array('');
					}
					foreach($arVals as $k=>$v)
					{
						$hide = (bool)in_array($v, array('{empty}', '{not_empty}'));
						$select = '<select name="filter_vals" onchange="ESettings.OnValChange(this)">'.
								'<option value="">'.GetMessage("KDA_IE_SETTINGS_FILTER_VAL").'</option>'.
								'<option value="{empty}" '.($v=='{empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_EMPTY").'</option>'.
								'<option value="{not_empty}" '.($v=='{not_empty}' ? 'selected' : '').'>'.GetMessage("KDA_IE_SETTINGS_FILTER_NOT_EMPTY").'</option>'.
							'</select>';
						echo '<div>'.$select.' <input type="text" name="'.$fName.'" value="'.htmlspecialcharsex($v).'" '.($hide ? 'style="display: none;"' : '').'></div>';
					}
					?>
					<a href="javascript:void(0)" onclick="ESettings.AddValue(this)"><?echo GetMessage("KDA_IE_ADD_VALUE");?></a>
				</td>
			</tr>
			<?if($field!='SECTION_SEP_NAME_PATH' && $field!='SECTION_SEP_NAME'){?>
				<?/*?>
				<tr>
					<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_USE_FILTER_FOR_DEACTIVATE");?>:</td>
					<td class="adm-detail-content-cell-r">
						<?
						list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'USE_FILTER_FOR_DEACTIVATE');
						?>
						<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
					</td>
				</tr>
				<?*/?>
				<tr>
					<td class="kda-ie-settings-margin-container" colspan="2">
						<a href="javascript:void(0)" onclick="ESettings.ShowPHPExpression(this)"><?echo GetMessage("KDA_IE_SETTINGS_FILTER_EXPRESSION");?></a>
						<?
						list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'FILTER_EXPRESSION');
						?>
						<div class="kda-ie-settings-phpexpression" style="display: none;">
							<?echo GetMessage("KDA_IE_SETTINGS_FILTER_EXPRESSION_HINT");?>
							<textarea name="<?echo $fName?>"<?if(!$isAdmin){echo ' readonly';}?>><?echo $val?></textarea>
						</div>
					</td>
				</tr>
			<?}?>
		<?}?>	

		
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("KDA_IE_SETTINGS_ADDITIONAL"); ?></td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_ONLY_FOR_NEW");?>:</td>
			<td class="adm-detail-content-cell-r" style="min-width: 30%;">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'SET_NEW_ONLY');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("KDA_IE_SETTINGS_NOT_TRIM");?>:</td>
			<td class="adm-detail-content-cell-r" style="min-width: 30%;">
				<?
				list($fName, $val) = GetFieldEextraVal($PEXTRASETTINGS, 'NOT_TRIM');
				?>
				<input type="checkbox" name="<?=$fName?>" value="Y" <?=($val=='Y' ? 'checked' : '')?>>
			</td>
		</tr>

	</table>
</form>
<?
if(!is_array($arSFields)) $arSFields = array();
?>
<script>
var admKDASettingMessages = {
	'CELL_VALUE': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_VALUE"));?>',
	'CELL_LINK': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_LINK"));?>',
	'CELL_COMMENT': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_CELL_COMMENT"));?>',
	'IFILENAME': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_IFILENAME"));?>',
	'ISHEETNAME': '<?echo htmlspecialcharsex(GetMessage("KDA_IE_SETTINGS_LANG_ISHEETNAME"));?>',
	'EXTRAFIELDS': <?echo \Bitrix\EsolAie\Utils::PhpToJSObject($arSFields)?>
};
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>