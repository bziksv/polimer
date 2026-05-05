<?
use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\EsolAie\Import;
	
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.allimportexport';
$moduleFilePrefix = 'esol_allimportexport';
$moduleJsId = str_replace('.', '_', $moduleId);
$moduleJsId2 = $moduleJsId;
$moduleDemoExpiredFunc = $moduleJsId2.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
$moduleRunnerClass = '\Bitrix\EsolAie\Runner';
Loader::includeModule($moduleId);
\CJSCore::Init(array('fileinput', $moduleJsId.'_import'));
Loc::loadMessages(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');
if (call_user_func($moduleDemoExpiredFunc)) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	call_user_func($moduleShowDemoFunc);
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
$entityId = $oRequest->get('entity');

if($MODE!='AJAX' && !\Bitrix\EsolAie\Entity\Utils::EntityTableExists($entityId))
{
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	echo 'Entity not exists';
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

/*Close session*/
$sess = $_SESSION;
session_write_close();
$_SESSION = $sess;
/*/Close session*/

$oProfile = \Bitrix\EsolAie\Import\Profile::getInstance($entityId);
$fl = \Bitrix\EsolAie\Import\FieldList::getInstance($entityId);
if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
{
	$PROFILE_ID = (int)$PROFILE_ID;
	$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $PROFILE_ID);
	if($EXTRASETTINGS)
	{
		foreach($EXTRASETTINGS as $k=>$v)
		{
			foreach($v as $k2=>$v2)
			{
				if($v2 && !is_array($v2))
				{
					$EXTRASETTINGS[$k][$k2] = \Bitrix\EsolAie\Utils::JsObjectToPhp($v2);
				}
			}
		}
	}
	$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
	
	/*New file storage*/
	if($SETTINGS_DEFAULT['URL_DATA_FILE'] && !$SETTINGS_DEFAULT["DATA_FILE"])
	{
		$filepath = $_SERVER["DOCUMENT_ROOT"].$SETTINGS_DEFAULT['URL_DATA_FILE'];
		if(!file_exists($filepath))
		{
			if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
			else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
		}
		$arFile = \Bitrix\EsolAie\Import\Utils::MakeFileArray($filepath);
		$fid = \Bitrix\EsolAie\Import\Utils::SaveFile($arFile);
		$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
	}
	/*/New file storage*/
}

$SHOW_FIRST_LINES =  (isset($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) && intval($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) > 0 ? intval($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW']) : 10);
$STEP = intval($STEP);
if ($STEP <= 0)
	$STEP = 1;

$notRewriteFile = false;
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	if(isset($_POST["backButton"]) && strlen($_POST["backButton"]) > 0) $STEP = $STEP - 2;
	if(isset($_POST["backButton2"]) && strlen($_POST["backButton2"]) > 0) $STEP = 1;
	if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && $STEP > 2)
	{
		$STEP = $STEP - 1;
		$notRewriteFile = true;
	}
}

$strError = $oProfile->GetErrors();
$htmlError = '';
$io = CBXVirtualIo::GetInstance();

function ShowTblLine($data, $list, $line, $checked = true)
{
	?><tr>
		<td class="line-settings" title="<?echo Loc::getMessage("ESOL_AE_LINE_NUM").' '.($line+1);?>">
			<input type="hidden" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="0">
			<input type="checkbox" name="SETTINGS[IMPORT_LINE][<?echo $list;?>][<?echo $line;?>]" value="1" <?if($checked){echo 'checked';}?>>
			<span class="sandwich" title="<?=Loc::getMessage("ESOL_AE_ACTIONS_BTN")?>"></span>
		</td><?
		foreach($data as $row)
		{
			$style = $parentStyle = $dataStyle = '';
			$parentStyle = '';
			if($row['STYLE'])
			{
				$arStyle = $row['STYLE'];
				if(isset($arStyle['EXT']) && is_array($arStyle['EXT']))
				{
					$arStyle = array_merge($arStyle, $arStyle['EXT']);
					unset($arStyle['EXT'], $row['STYLE']['EXT']);
				}
				if($arStyle['BACKGROUND'])
				{
					$style .= 'background-color:#'.$arStyle['BACKGROUND'].';';
					$parentStyle .= 'background-color:#'.$arStyle['BACKGROUND'].';';
				}
				if($arStyle['COLOR']) $style .= 'color:#'.$arStyle['COLOR'].';';
				if($arStyle['FONT-WEIGHT']) $style .= 'font-weight:bold;';
				if($arStyle['FONT-STYLE']) $style .= 'font-style:italic;';
				if($arStyle['TEXT-DECORATION']=='single') $style .= 'text-decoration:underline;';
				$dataStyle = 'data-style="'.htmlspecialcharsex(\Bitrix\EsolAie\Utils::PhpToJSObject($row['STYLE'])).'"';
			}
			$style = ($style ? 'style="'.$style.'"' : '');
			$parentStyle = ($parentStyle ? 'style="'.$parentStyle.'"' : '');
		?><td <?echo $parentStyle;?>><div class="cell" <?echo $parentStyle;?>><div class="cell_inner" <?echo $style;?> <?echo $dataStyle;?>><?echo nl2br(htmlspecialcharsex($row['VALUE']));?></div></div></td><?
		}
	?></tr><?
}
/////////////////////////////////////////////////////////////////////
if ($REQUEST_METHOD == "POST" && $MODE=='AJAX')
{
	define('PUBLIC_AJAX_MODE', 'Y');
	if($ACTION=='SHOW_MODULE_MESSAGE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?
		call_user_func($moduleShowDemoFunc, true);
		?></div><?
		die();
	}
	
	if($ACTION=='DELETE_TMP_DIRS')
	{
		\Bitrix\EsolAie\Import\Utils::RemoveTmpFiles();
		die();
	}
	
	if($ACTION=='REMOVE_PROCESS_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$oProfile->RemoveProcessedProfile($PROCCESS_PROFILE_ID);
		die();
	}
	
	if($ACTION=='GET_PROCESS_PARAMS')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		echo \Bitrix\EsolAie\Utils::PhpToJSObject($oProfile->GetProccessParams($PROCCESS_PROFILE_ID));
		die();
	}
	
	if($ACTION=='DELETE_PROFILE')
	{
		$oProfile->Delete($_REQUEST['ID']);
		die();
	}
	
	if($ACTION=='COPY_PROFILE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$id = $oProfile->Copy($_REQUEST['ID']);
		echo \Bitrix\EsolAie\Utils::PhpToJSObject(array('id'=>$id));
		die();
	}
	
	if($ACTION=='RENAME_PROFILE')
	{
		$newName = $_REQUEST['NAME'];
		if((!defined('BX_UTF') || !BX_UTF)) $newName = $APPLICATION->ConvertCharset($newName, 'UTF-8', 'CP1251');
		$oProfile->Rename($_REQUEST['ID'], $newName);
		die();
	}
	
	if($ACTION=='APPLY_TO_LISTS')
	{
		$oProfile->ApplyToLists($_REQUEST['PROFILE_ID'], $_REQUEST['LIST_FROM'], $_REQUEST['LIST_TO']);
		die();
	}
}

if ($REQUEST_METHOD == "POST" && $STEP > 1 && check_bitrix_sessid())
{
	if($ACTION) define('PUBLIC_AJAX_MODE', 'Y');
	
	//*****************************************************************//	
	if ($STEP > 1)
	{
		//*****************************************************************//	
		
		if (strlen($strError) <= 0)
		{
			if($STEP==2 && !$notRewriteFile)
			{
				if((!isset($_FILES["DATA_FILE"]) || !$_FILES["DATA_FILE"]["tmp_name"]) && (!isset($_POST['DATA_FILE']) || is_numeric($_POST['DATA_FILE'])))
				{
					if($_POST["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $_POST["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT["EXT_DATA_FILE"]) $_POST['DATA_FILE'] = $SETTINGS_DEFAULT["EXT_DATA_FILE"];
					elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
					{
						$fileId = \Bitrix\EsolAie\Import\SMail::GetNewFile($SETTINGS_DEFAULT['EMAIL_DATA_FILE']);
						if($fileId > 0)
						{
							if($_POST['OLD_DATA_FILE'])
							{
								\Bitrix\EsolAie\Import\Utils::DeleteFile($_POST['OLD_DATA_FILE']);
							}
							$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['DATA_FILE'] = $fileId;
						}
					}
				}
				elseif($SETTINGS_DEFAULT['EMAIL_DATA_FILE'])
				{
					unset($SETTINGS_DEFAULT['EMAIL_DATA_FILE']);
				}
			}
		
			$DATA_FILE_NAME = "";
			if((isset($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["tmp_name"]) || (isset($_POST['DATA_FILE']) && $_POST['DATA_FILE'] && !is_numeric($_POST['DATA_FILE'])))
			{
				$extFile = false;
				$fid = 0;
				if(isset($_FILES["DATA_FILE"]) && is_uploaded_file($_FILES["DATA_FILE"]["tmp_name"]))
				{
					//$fid = \Bitrix\EsolAie\Import\Utils::SaveFile($_FILES["DATA_FILE"]);
					$arFile = \Bitrix\EsolAie\Import\Utils::MakeFileArray($_FILES["DATA_FILE"]);
					$fid = \Bitrix\EsolAie\Import\Utils::SaveFile($arFile);
				}
				elseif(isset($_POST['DATA_FILE']) && strlen($_POST['DATA_FILE']) > 0)
				{
					$extFile = true;
					if(strpos($_POST['DATA_FILE'], '/')===0) 
					{
						$filepath = $_POST['DATA_FILE'];
						if(!file_exists($filepath))
						{
							$filepath = $_SERVER["DOCUMENT_ROOT"].$filepath;
						}
						if(!file_exists($filepath))
						{
							if(defined("BX_UTF")) $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'CP1251');
							else $filepath = $APPLICATION->ConvertCharsetArray($filepath, LANG_CHARSET, 'UTF-8');
						}
					}
					else
					{
						//$extFile = true;
						$filepath = $_POST['DATA_FILE'];
						if($filepath && $_POST['OLD_DATA_FILE'])
						{
							$arOldFile = CFIle::GetFileArray($_POST['OLD_DATA_FILE']);
							$oldFileSize = (int)filesize($_SERVER['DOCUMENT_ROOT'].$arOldFile['SRC']);
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true));
							$newFileSize = 0;
							if(is_callable(array($client, 'head')) && ($headers = $client->head($filepath)) && $client->getStatus()!=404) $newFileSize = (int)$headers->get('content-length');
							if($oldFileSize > 0 && $newFileSize > 0 && $oldFileSize==$newFileSize)
							{
								$fid = $_POST['OLD_DATA_FILE'];
							}
						}
					}
					if(!$fid)
					{
						$arFile = \Bitrix\EsolAie\Import\Utils::MakeFileArray($filepath);
						if($arFile['name'])
						{
							if(strpos($arFile['name'], '.')===false) $arFile['name'] .= '.csv';
							$fid = \Bitrix\EsolAie\Import\Utils::SaveFile($arFile);
						}
					}
				}
				
				if(!$fid)
				{
					$strError.= Loc::getMessage("ESOL_AE_FILE_UPLOAD_ERROR")."<br>";
					if($extFile)
					{
						$SETTINGS_DEFAULT["EXT_DATA_FILE"] = $_POST['DATA_FILE'];
					}
				}
				else
				{
					$SETTINGS_DEFAULT["DATA_FILE"] = $fid;
					if($_POST['OLD_DATA_FILE'] && $_POST['OLD_DATA_FILE']!=$fid)
					{
						\Bitrix\EsolAie\Import\Utils::DeleteFile($_POST['OLD_DATA_FILE']);
					}
					$SETTINGS_DEFAULT["EXT_DATA_FILE"] = ($extFile ? $_POST['DATA_FILE'] : false);
				}
			}
			elseif(isset($_FILES["DATA_FILE"]) && is_array($_FILES["DATA_FILE"]) && $_FILES["DATA_FILE"]["error"]==1)
			{
				$strError.= Loc::getMessage("ESOL_AE_FILE_UPLOAD_ERROR")."<br>";
				$uploadMaxFilesize = \Bitrix\EsolAie\Import\Utils::GetIniAbsVal('upload_max_filesize');
				$postMaxSize = \Bitrix\EsolAie\Import\Utils::GetIniAbsVal('post_max_size');
				if($uploadMaxFilesize > 0 || $postMaxSize > 0)
				{
					$partError = '';
					if($uploadMaxFilesize > 0) $partError .= 'upload_max_filesize = '.($uploadMaxFilesize/(1024*1024)).'Mb<br>';
					if($postMaxSize > 0) $partError .= 'post_max_size = '.($postMaxSize/(1024*1024)).'Mb<br>';
					$strError.= '<br>'.sprintf(Loc::getMessage("ESOL_AE_FILE_UPLOAD_ERROR_MAX_SIZE"), $partError)."<br>";
				}
			}
		}
		
		if(!$SETTINGS_DEFAULT["DATA_FILE"] && $_POST['OLD_DATA_FILE'])
		{
			$SETTINGS_DEFAULT["DATA_FILE"] = $_POST['OLD_DATA_FILE'];
		}
		
		if($SETTINGS_DEFAULT["DATA_FILE"])
		{
			//$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
			$i = 0;
			while($i < 2 && !($arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"])))
			{
				\CFile::CleanCache($SETTINGS_DEFAULT["DATA_FILE"]);
				$i++;
			}
			if(stripos($arFile['SRC'], 'http')===0)
			{
				$arFileUrl = parse_url($arFile['SRC']);
				if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
			}
			$SETTINGS_DEFAULT['URL_DATA_FILE'] = $arFile['SRC'];
		}
		
		if(strlen($PROFILE_ID)==0)
		{
			$strError.= Loc::getMessage("ESOL_AE_PROFILE_NOT_CHOOSE")."<br>";
		}

		if (strlen($strError) <= 0)
		{
			if (strlen($DATA_FILE_NAME) <= 0)
			{
				if (strlen($SETTINGS_DEFAULT['URL_DATA_FILE']) > 0)
				{
					$SETTINGS_DEFAULT['URL_DATA_FILE'] = trim(str_replace("\\", "/", trim($SETTINGS_DEFAULT['URL_DATA_FILE'])) , "/");
					$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$SETTINGS_DEFAULT['URL_DATA_FILE']);
					if (
						(strlen($FILE_NAME) > 1)
						&& ($FILE_NAME === "/".$SETTINGS_DEFAULT['URL_DATA_FILE'])
						&& $io->FileExists($_SERVER["DOCUMENT_ROOT"].$FILE_NAME)
						/*&& ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W")*/
					)
					{
						$DATA_FILE_NAME = $FILE_NAME;
					}
				}
			}

			if (strlen($DATA_FILE_NAME) <= 0)
				$strError.= Loc::getMessage("ESOL_AE_NO_DATA_FILE")."<br>";
			else
				$SETTINGS_DEFAULT['URL_DATA_FILE'] = $DATA_FILE_NAME;
			
			/*if(ToLower(\Bitrix\EsolAie\Import\Utils::GetFileExtension($DATA_FILE_NAME))=='xls' && ini_get('mbstring.func_overload')==2)
			{
				$strError.= Loc::getMessage("ESOL_AE_FUNC_OVERLOAD_XLS")."<br>";
			}*/
			
			if(!in_array(ToLower(\Bitrix\EsolAie\Import\Utils::GetFileExtension($DATA_FILE_NAME)), array('txt', 'csv', 'xls', 'xlsx', 'xlsm'/*, 'dbf'*/)))
			{
				$strError.= Loc::getMessage("ESOL_AE_FILE_NOT_SUPPORT")."<br>";
				if(in_array(ToLower(\Bitrix\EsolAie\Import\Utils::GetFileExtension($DATA_FILE_NAME)), array('xml', 'yml')))
				{
					$htmlError.= Loc::getMessage("ESOL_AE_USE_XML_MODULE")."<br>";
				}
			}
			
			if((!$DATA_FILE_NAME = \Bitrix\EsolAie\Import\Utils::GetFileName($DATA_FILE_NAME)))
			{
				$strError.= Loc::getMessage("ESOL_AE_FILE_NOT_FOUND")."<br>";
			}
			
			if(empty($SETTINGS_DEFAULT['ELEMENT_UID']))
			{
				$strError.= Loc::getMessage("ESOL_AE_NO_ELEMENT_UID")."<br>";
			}
		}
		
		if (strlen($strError) <= 0)
		{
			/*Write profile*/
			if($PROFILE_ID === 'new')
			{
				$PID = $oProfile->Add($entityId, $NEW_PROFILE_NAME);
				if($PID===false)
				{
					if($ex = $APPLICATION->GetException())
					{
						$strError .= $ex->GetString().'<br>';
					}
				}
				else
				{
					$PROFILE_ID = $PID;
				}
			}
			/*/Write profile*/
		}

		if (strlen($strError) > 0)
			$STEP = 1;
		
		if(isset($_POST["saveConfigButton"]) && strlen($_POST["saveConfigButton"]) > 0 && !$notRewriteFile)
			$STEP = 1;
		//*****************************************************************//
	}
	
	if($ACTION == 'SHOW_FULL_LIST')
	{
		try{
			$pparams = $SETTINGS_DEFAULT;
			if(isset($SETTINGS['CSV_PARAMS'])) $pparams['CSV_PARAMS'] = $SETTINGS['CSV_PARAMS'];
			$arWorksheets = \Bitrix\EsolAie\Import\Importer::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams, $COUNT_COLUMNS);
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo Loc::getMessage("ESOL_AE_ERROR").$ex->getMessage();
			die();
		}
		
		$arProfile = $oProfile->GetByID($PROFILE_ID);
		if(is_array($arProfile['SETTINGS']['IMPORT_LINE']))
		{
			$SETTINGS['IMPORT_LINE'] = $arProfile['SETTINGS']['IMPORT_LINE'];
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		foreach($arWorksheets as $k=>$worksheet)
		{
			if($k==$LIST_NUMBER)
			{
				foreach($worksheet['lines'] as $line=>$arLine)
				{
					$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
					ShowTblLine($arLine, $k, $line, $checked);
				}
			}
		}
		die();
	}
	
	if($ACTION == 'SHOW_REVIEW_LIST')
	{
		try{
			$pparams = $SETTINGS_DEFAULT;
			if(isset($SETTINGS['CSV_PARAMS'])) $pparams['CSV_PARAMS'] = $SETTINGS['CSV_PARAMS'];
			$arWorksheets = \Bitrix\EsolAie\Import\Importer::GetPreviewData($DATA_FILE_NAME, $SHOW_FIRST_LINES, $pparams);
		}catch(Exception $ex){
			$APPLICATION->RestartBuffer();
			ob_end_clean();
			echo Loc::getMessage("ESOL_AE_ERROR").$ex->getMessage();
			die();
		}
		
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		
		if(!$arWorksheets) $arWorksheets = array();
		foreach($arWorksheets as $k=>$worksheet)
		{
			$columns = (count($worksheet['lines']) > 0 ? count($worksheet['lines'][0]) : 1) + 1;
			$bEmptyList = empty($worksheet['lines']);
		?>
			<table class="kda-ie-tbl <?if($bEmptyList){echo 'empty';}?>" data-list-index="<?echo $k;?>">
				<tr class="heading">
					<td class="left"><?echo Loc::getMessage("ESOL_AE_LIST_TITLE"); ?> "<?echo $worksheet['title'];?>" <?if($bEmptyList){echo Loc::getMessage("ESOL_AE_EMPTY_LIST");}?></td>
					<td class="right list-settings">
						<?if(count($worksheet['lines']) > 0){?>
							<input type="hidden" name="SETTINGS[ADDITIONAL_SETTINGS][<?echo $k;?>]" value="<?if($SETTINGS['ADDITIONAL_SETTINGS'][$k])echo htmlspecialcharsex(\Bitrix\EsolAie\Utils::PhpToJSObject($SETTINGS['ADDITIONAL_SETTINGS'][$k]));?>">
							<input type="hidden" name="SETTINGS[LIST_LINES][<?echo $k;?>]" value="<?echo $worksheet['lines_count'];?>">
							<input type="hidden" name="SETTINGS[LIST_ACTIVE][<?echo $k;?>]" value="N">
							<input type="checkbox" name="SETTINGS[LIST_ACTIVE][<?echo $k;?>]" id="list_active_<?echo $k;?>" value="Y" <?=(!isset($SETTINGS['LIST_ACTIVE'][$k]) || $SETTINGS['LIST_ACTIVE'][$k]=='Y' ? 'checked' : '')?>> <label for="list_active_<?echo $k;?>"><small><?echo Loc::getMessage("ESOL_AE_DOWNLOAD_LIST"); ?></small></label>
							<a href="javascript:void(0)" class="showlist" onclick="EList.ToggleSettings(this)" title="<?echo Loc::getMessage("ESOL_AE_LIST_SHOW"); ?>"></a>
							<?
							if(is_array($SETTINGS['LIST_SETTINGS'][$k]))
							{
								foreach($SETTINGS['LIST_SETTINGS'][$k] as $k2=>$v2)
								{
									?><input type="hidden" name="SETTINGS[LIST_SETTINGS][<?echo $k;?>][<?echo $k2;?>]" value="<?echo htmlspecialcharsex($v2);?>"><?
								}
							}
							if(is_array($EXTRASETTINGS[$k]))
							{
								foreach($EXTRASETTINGS[$k] as $k2=>$v2)
								{
									if(strpos($k2, '__')===0 && !empty($v2))
									{
										?><div><a href="javascript:void(0)" id="field_settings_<?echo $k;?>_<?echo $k2;?>" onclick="EList.ShowFieldSettings(this);"><input type="hidden" name="EXTRASETTINGS[<?echo $k;?>][<?echo $k2;?>]" value=""><script>EList.SetExtraParams("field_settings_<?echo $k;?>_<?echo $k2;?>", <?echo \Bitrix\EsolAie\Utils::PhpToJSObject($v2);?>)</script></a></div><?
									}
								}
							}
						}?>
					</td>
				</tr>
				<tr class="settings">
					<td colspan="2">
						<div class="copysettings">
							<a href="javascript:void(0)" onclick="EList.ApplyToAllLists(this)"><?echo Loc::getMessage("ESOL_AE_APPLY_TO_ALL_LISTS"); ?></a>
						</div>
						<?/*?>
						<div class="addsettings">
							<a href="javascript:void(0)" class="addsettings_link" onclick="EList.ToggleAddSettingsBlock(this)"><span><?echo Loc::getMessage("ESOL_AE_ADDITIONAL_SETTINGS"); ?></span></a>
							<div class="addsettings_inner">
								<table class="additional">
									<col><col width="400px">
									<?
									$fileExt = \Bitrix\EsolAie\Import\Utils::GetFileExtension($DATA_FILE_NAME);
									$changeCsvParams = (bool)($SETTINGS['CSV_PARAMS']['CHANGE']=='Y');
									if(ToLower($fileExt)=='csv')
									{
									?>
										<tr>
											<td><?echo Loc::getMessage("ESOL_AE_CHANGE_CSV_PARAMS"); ?>:</td>
											<td>
												<input type="hidden" name="SETTINGS[CSV_PARAMS][CHANGE]" value="N">
												<input type="checkbox" name="SETTINGS[CSV_PARAMS][CHANGE]" value="Y" <?if($changeCsvParams){echo 'checked';}?> onchange="EList.ToggleAddSettings(this)">
											</td>
										</tr>

										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo Loc::getMessage("ESOL_AE_CHANGE_CSV_SEPARATOR"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['SEPARATOR']) && strlen(trim($SETTINGS['CSV_PARAMS']['SEPARATOR']) > 0) ? trim($SETTINGS['CSV_PARAMS']['SEPARATOR']) : ';');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][SEPARATOR]" value="<?echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>

										<tr class="subfield" <?if(!$changeCsvParams){echo 'style="display: none;"';}?>>
											<td><?echo Loc::getMessage("ESOL_AE_CHANGE_CSV_ENCLOSURE"); ?>:</td>
											<td>
												<?
												$val = (isset($SETTINGS['CSV_PARAMS']['ENCLOSURE']) && strlen(trim($SETTINGS['CSV_PARAMS']['ENCLOSURE']) > 0) ? trim($SETTINGS['CSV_PARAMS']['ENCLOSURE']) : '"');
												?>
												<input type="text" name="SETTINGS[CSV_PARAMS][ENCLOSURE]" value="<?echo htmlspecialcharsex($val)?>" size="3" maxlength="3">
											</td>
										</tr>
									<?}?>
								</table>
							</div>
						</div>
						<?*/?>
						<div class="set_scroll">
							<div></div>
						</div>
						<div class="set">						
						<table class="list">
						<?
						if(count($worksheet['lines']) > 0)
						{
							?>
								<tr>
									<td>
										<input type="hidden" name="SETTINGS[CHECK_ALL][<?echo $k;?>]" value="0"> 
										<span class="checkall">
											<label for="check_all_<?echo $k;?>"><?echo Loc::getMessage("ESOL_AE_CHECK_ALL"); ?></label><br>
											<input type="checkbox" name="SETTINGS[CHECK_ALL][<?echo $k;?>]" id="check_all_<?echo $k;?>" value="1" <?if(!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k]){echo 'checked';}?>> 
										</span>
										<span class="sandwich" title="<?=Loc::getMessage("ESOL_AE_ACTIONS_BTN")?>" data-type="titles"></span>
										<?$fl->ShowSelectFields('FIELDS_LIST['.$k.']')?>
									</td>
									<?
									$num_rows = count($worksheet['lines'][0]);
									for($i = 0; $i < $num_rows; $i++)
									{
										$arKeys = array($i);
										if(is_array($SETTINGS['FIELDS_LIST'][$k]))
											$arKeys = array_merge($arKeys, preg_grep('/^'.$i.'_\d+$/', array_keys($SETTINGS['FIELDS_LIST'][$k])));
										?>
										<td class="kda-ie-field-select" title="#CELL<?echo ($i+1);?>#">
											<b><?echo \Bitrix\EsolAie\Import\Utils::GetColLetterByIndex($i);?></b>
											<?foreach($arKeys as $j){?>
												<div>
													<?/*$fl->ShowSelectFields( 'SETTINGS[FIELDS_LIST]['.$k.']['.$j.']', $SETTINGS['FIELDS_LIST'][$k][$j])*/?>
													<input type="hidden" name="SETTINGS[FIELDS_LIST][<?echo $k?>][<?echo $j?>]" value="<?echo $SETTINGS['FIELDS_LIST'][$k][$j]?>" >
													<?/*?><input type="text" name="FIELDS_LIST_SHOW[<?echo $k?>][<?echo $j?>]" value="" class="fieldval"><?*/?>
													<span class="fieldval_wrap"><span class="fieldval" id="field-list-show-<?echo $k?>-<?echo $j?>"></span></span>
													<a href="javascript:void(0)" class="field_settings <?=(empty($EXTRASETTINGS[$k][$j]) ? 'inactive' : '')?>" id="field_settings_<?=$k?>_<?=$j?>" title="<?echo Loc::getMessage("ESOL_AE_SETTINGS_FIELD"); ?>" onclick="EList.ShowFieldSettings(this);">
														<input type="hidden" name="EXTRASETTINGS[<?echo $k?>][<?echo $j?>]" value="">
														<?if(!empty($EXTRASETTINGS[$k][$j])){?>
															<script>EList.SetExtraParams("field_settings_<?=$k?>_<?=$j?>", <?echo \Bitrix\EsolAie\Utils::PhpToJSObject($EXTRASETTINGS[$k][$j]);?>)</script>
														<?}?>
													</a>
													<a href="javascript:void(0)" class="field_delete" title="<?echo Loc::getMessage("ESOL_AE_SETTINGS_DELETE_FIELD"); ?>" onclick="EList.DeleteUploadField(this);"></a>
												</div>
											<?}?>
											<div class="kda-ie-field-select-btns">
												<div class="kda-ie-field-select-btns-inner">
													<a href="javascript:void(0)" class="kda-ie-move-fields">
														<span title="<?echo Loc::getMessage("ESOL_AE_SETTINGS_MOVE_FIELDS_LEFT"); ?>" onclick="return EList.ColumnsMoveLeft(this);"></span>
														<span title="<?echo Loc::getMessage("ESOL_AE_SETTINGS_MOVE_FIELDS_RIGHT"); ?>" onclick="return EList.ColumnsMoveRight(this);"></span>
													</a>
													<a href="javascript:void(0)" class="kda-ie-add-load-field" title="<?echo Loc::getMessage("ESOL_AE_SETTINGS_ADD_FIELD"); ?>" onclick="EList.AddUploadField(this);"></a>
												</div>
											</div>
										</td>
										<?
									}
									?>
								</tr>
							<?
							
						}			
						
						foreach($worksheet['lines'] as $line=>$arLine)
						{
							$checked = ((!isset($SETTINGS['IMPORT_LINE'][$k][$line]) && (!isset($SETTINGS['CHECK_ALL'][$k]) || $SETTINGS['CHECK_ALL'][$k])) || $SETTINGS['IMPORT_LINE'][$k][$line]);
							ShowTblLine($arLine, $k, $line, $checked);
						}
						?>
						</table>
						</div>
						<?if($worksheet['show_more']){?>
							<input type="button" value="<?echo Loc::getMessage("ESOL_AE_SHOW_LIST"); ?>" onclick="EList.ShowFull(this);">
						<?}?>
						<br><br>
					</td>
				</tr>
			</table>
		<?
		}
		die();
	}
	
	if($ACTION == 'DO_IMPORT')
	{
		unset($EXTRASETTINGS);
		$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
		$params = array_merge($SETTINGS_DEFAULT, $SETTINGS);
		$stepparams = $_POST['stepparams'];
		$arResult = $moduleRunnerClass::DoImport($entityId, $DATA_FILE_NAME, $params, $EXTRASETTINGS, $stepparams, $PROFILE_ID);
		$APPLICATION->RestartBuffer();
		if(ob_get_contents()) ob_end_clean();
		echo '<!--module_return_data-->'.\Bitrix\EsolAie\Utils::PhpToJSObject($arResult).'<!--/module_return_data-->';
		
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
		die();
	}
	
	/*Profile update*/
	if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!=='new')
	{
		$oProfile->Update($PROFILE_ID, $SETTINGS_DEFAULT, $SETTINGS);
		if(is_array($EXTRASETTINGS)) $oProfile->UpdateExtra($PROFILE_ID, $EXTRASETTINGS);
	}
	/*/Profile update*/
	
	//*****************************************************************//

}

/////////////////////////////////////////////////////////////////////
$APPLICATION->SetTitle(Loc::getMessage("ESOL_AE_PAGE_TITLE").$STEP);
require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
/*********************************************************************/
/********************  BODY  *****************************************/
/*********************************************************************/

if (!call_user_func($moduleDemoExpiredFunc)) {
	call_user_func($moduleShowDemoFunc);
}

$aMenu = array(
	array(
	"TEXT"=>GetMessage("ESOL_AE_SHOW_CRONTAB"),
	"TITLE"=>GetMessage("ESOL_AE_SHOW_CRONTAB"),
		"ONCLICK" => "EProfile.ShowCron();",
		"ICON" => "btn_green",
	)
);
$context = new CAdminContextMenu($aMenu);
$context->Show();

if ($STEP < 2)
{
	$arProfiles = $oProfile->GetProcessedProfiles();
	if(!empty($arProfiles))
	{
		$message = '';
		foreach($arProfiles as $k=>$v)
		{
			$message .= '<div class="kda-proccess-item">'.Loc::getMessage("ESOL_AE_PROCESSED_PROFILE").': '.$v['name'].' ('.Loc::getMessage("ESOL_AE_PROCESSED_PERCENT_LOADED").' '.$v['percent'].'%). &nbsp; &nbsp; &nbsp; &nbsp; <a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$v['key'].')">'.Loc::getMessage("ESOL_AE_PROCESSED_CONTINUE").'</a> &nbsp; <a href="javascript:void(0)" onclick="EProfile.RemoveProccess(this, '.$v['key'].')">'.Loc::getMessage("ESOL_AE_PROCESSED_DELETE").'</a></div>';
		}
		CAdminMessage::ShowMessage(array(
			'TYPE' => 'error',
			'MESSAGE' => Loc::getMessage("ESOL_AE_PROCESSED_TITLE"),
			'DETAILS' => $message,
			'HTML' => true
		));
	}
}

if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y')
{
	CAdminMessage::ShowMessage(array(
		'TYPE' => 'ok',
		'MESSAGE' => Loc::getMessage("ESOL_AE_DELETE_MODE_TITLE"),
		'DETAILS' => Loc::getMessage("ESOL_AE_DELETE_MODE_MESSAGE"),
		'HTML' => true
	));	
}

if(strlen($strError) > 0)
{
	CAdminMessage::ShowMessage(array(
		'MESSAGE' => $strError,
		'DETAILS' => $htmlError,
		'HTML' => true
	));
}
?>

<form method="POST" action="?<?if(strlen($PROFILE_ID) > 0){echo 'PROFILE_ID='.htmlspecialcharsbx($PROFILE_ID).'&';}?>lang=<?echo LANG ?>&entity=<?echo $entityId?>" ENCTYPE="multipart/form-data" name="dataload" id="dataload" class="kda-ie-s1-form">

<?
$arProfile = (strlen($PROFILE_ID) > 0 ? $oProfile->GetFieldsByID($PROFILE_ID) : array());
$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => Loc::getMessage("ESOL_AE_TAB1") ,
		"ICON" => "iblock",
		"TITLE" => Loc::getMessage("ESOL_AE_TAB1_ALT"),
	) ,
	array(
		"DIV" => "edit2",
		"TAB" => Loc::getMessage("ESOL_AE_TAB2") ,
		"ICON" => "iblock",
		"TITLE" => sprintf(Loc::getMessage("ESOL_AE_TAB2_ALT"), (isset($arProfile['NAME']) ? $arProfile['NAME'] : '')),
	) ,
	array(
		"DIV" => "edit3",
		"TAB" => Loc::getMessage("ESOL_AE_TAB3") ,
		"ICON" => "iblock",
		"TITLE" => sprintf(Loc::getMessage("ESOL_AE_TAB3_ALT"), (isset($arProfile['NAME']) ? $arProfile['NAME'] : '')),
	) ,
);

$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();
?>

<?$tabControl->BeginNextTab();
if ($STEP == 1)
{
?>

	<tr class="heading">
		<td colspan="2" class="kda-ie-profile-header">
			<div>
				<?echo Loc::getMessage("ESOL_AE_PROFILE_HEADER"); ?>
			</div>
		</td>
	</tr>

	<tr>
		<td><?echo Loc::getMessage("ESOL_AE_PROFILE"); ?>:</td>
		<td>		
			<?$oProfile->ShowProfileList('PROFILE_ID');?>
			
			<?if(strlen($PROFILE_ID) > 0 && $PROFILE_ID!='new'){?>
				<span class="kda-ie-edit-btns">
					<a href="javascript:void(0)" class="adm-table-btn-edit" onclick="EProfile.ShowRename();" title="<?echo Loc::getMessage("ESOL_AE_RENAME_PROFILE");?>" id="action_edit_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-copy" onclick="EProfile.Copy();" title="<?echo Loc::getMessage("ESOL_AE_COPY_PROFILE");?>" id="action_copy_button"></a>
					<a href="javascript:void(0);" class="adm-table-btn-delete" onclick="if(confirm('<?echo Loc::getMessage("ESOL_AE_DELETE_PROFILE_CONFIRM");?>')){EProfile.Delete();}" title="<?echo Loc::getMessage("ESOL_AE_DELETE_PROFILE");?>" id="action_delete_button"></a>
				</span>
			<?}?>
		</td>
	</tr>
	
	<tr id="new_profile_name">
		<td><?echo Loc::getMessage("ESOL_AE_NEW_PROFILE_NAME"); ?>:</td>
		<td>
			<input type="text" name="NEW_PROFILE_NAME" value="<?echo htmlspecialcharsbx($NEW_PROFILE_NAME)?>" size="50">
		</td>
	</tr>

	<?
	if(strlen($PROFILE_ID) > 0)
	{
	?>
		<tr class="heading">
			<td colspan="2"><?echo Loc::getMessage("ESOL_AE_DEFAULT_SETTINGS"); ?></td>
		</tr>
		
		<tr>
			<td width="40%"><?echo Loc::getMessage("ESOL_AE_URL_DATA_FILE"); ?></td>
			<td width="60%" class="kda-ie-file-choose">
				<!--KDA_IE_CHOOSE_FILE-->
				<?if($SETTINGS_DEFAULT['EMAIL_DATA_FILE']) echo '<input type="hidden" name="SETTINGS_DEFAULT[EMAIL_DATA_FILE]" value="'.htmlspecialcharsbx($SETTINGS_DEFAULT['EMAIL_DATA_FILE']).'">';?>
				<?if($SETTINGS_DEFAULT['EXT_DATA_FILE']) echo '<input type="hidden" name="EXT_DATA_FILE" value="'.htmlspecialcharsbx($SETTINGS_DEFAULT['EXT_DATA_FILE']).'">';?>
				<input type="hidden" name="OLD_DATA_FILE" value="<?echo htmlspecialcharsbx($SETTINGS_DEFAULT['DATA_FILE']); ?>">
				<?
				$arFile = CFile::GetFileArray($SETTINGS_DEFAULT["DATA_FILE"]);
				if(stripos($arFile['SRC'], 'http')===0)
				{
					$arFileUrl = parse_url($arFile['SRC']);
					if($arFileUrl['path']) $arFile['SRC'] = $arFileUrl['path'];
				}
				if($arFile['SRC'])
				{
					if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
					{
						if(defined("BX_UTF")) $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'CP1251');
						else $arFile['SRC'] = $APPLICATION->ConvertCharsetArray($arFile['SRC'], LANG_CHARSET, 'UTF-8');
						if(!file_exists($_SERVER['DOCUMENT_ROOT'].$arFile['SRC']))
						{
							unset($SETTINGS_DEFAULT["DATA_FILE"]);
						}
					}
				}
				else
				{
					unset($SETTINGS_DEFAULT["DATA_FILE"]);
				}
				//Loader::includeModule('fileman');
				echo \Bitrix\EsolAie\CFileInput::Show("DATA_FILE", $SETTINGS_DEFAULT["DATA_FILE"], array(
					"IMAGE" => "N",
					"PATH" => "Y",
					"FILE_SIZE" => "Y",
					"DIMENSIONS" => "N"
				), array(
					'upload' => true,
					'medialib' => false,
					'file_dialog' => true,
					'cloud' => true,
					/*'email' => true,
					'linkauth' => true,*/
					'del' => false,
					'description' => false,
				));
				?>
				<!--/KDA_IE_CHOOSE_FILE-->
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo Loc::getMessage("ESOL_AE_SETTINGS_PROCESSING"); ?></td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_ELEMENT_UID"); ?>: <span id="hint_ELEMENT_UID"></span><script>BX.hint_replace(BX('hint_ELEMENT_UID'), '<?echo Loc::getMessage("ESOL_AE_ELEMENT_UID_HINT"); ?>');</script></td>
			<td>
				<?$fl->ShowSelectUidFields('SETTINGS_DEFAULT[ELEMENT_UID][]', $SETTINGS_DEFAULT['ELEMENT_UID']);?>
			</td>
		</tr>

		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_ONLY_UPDATE_MODE"); ?>: <span id="hint_ONLY_UPDATE_MODE_ELEMENT"></span><script>BX.hint_replace(BX('hint_ONLY_UPDATE_MODE_ELEMENT'), '<?echo Loc::getMessage("ESOL_AE_ONLY_UPDATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_UPDATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_UPDATE_MODE_ELEMENT']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_ONLY_CREATE_MODE"); ?>: <span id="hint_ONLY_CREATE_MODE_ELEMENT"></span><script>BX.hint_replace(BX('hint_ONLY_CREATE_MODE_ELEMENT'), '<?echo Loc::getMessage("ESOL_AE_ONLY_CREATE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_CREATE_MODE']=='Y' || $SETTINGS_DEFAULT['ONLY_CREATE_MODE_ELEMENT']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_DELETE_MODE]'])">
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_ONLY_DELETE_MODE"); ?>: <span id="hint_ONLY_DELETE_MODE"></span><script>BX.hint_replace(BX('hint_ONLY_DELETE_MODE'), '<?echo Loc::getMessage("ESOL_AE_ONLY_DELETE_MODE_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ONLY_DELETE_MODE]" value="Y" <?if($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y'){echo 'checked';}?> onchange="EProfile.RadioChb(this, ['SETTINGS_DEFAULT[ONLY_UPDATE_MODE_ELEMENT]', 'SETTINGS_DEFAULT[ONLY_CREATE_MODE_ELEMENT]'], '<?echo htmlspecialcharsex(Loc::getMessage("ESOL_AE_ONLY_DELETE_MODE_CONFIRM")); ?>')">
			</td>
		</tr>

		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_ELEMENT_MULTIPLE_SEPARATOR"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[ELEMENT_MULTIPLE_SEPARATOR]" size="3" value="<?echo ($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR'] ? htmlspecialcharsbx($SETTINGS_DEFAULT['ELEMENT_MULTIPLE_SEPARATOR']) : ';'); ?>">
			</td>
		</tr>
		
		<tr class="heading">
			<td colspan="2"><?echo Loc::getMessage("ESOL_AE_SETTINGS_FILE_READING"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?echo Loc::getMessage("ESOL_AE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_OPTIMIZE_RAM"); ?>: <span id="hint_OPTIMIZE_RAM"></span><script>BX.hint_replace(BX('hint_OPTIMIZE_RAM'), '<?echo Loc::getMessage("ESOL_AE_OPTIMIZE_RAM_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[OPTIMIZE_RAM]" value="Y" <?if($SETTINGS_DEFAULT['OPTIMIZE_RAM']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_LOAD_IMAGES"); ?>: <span id="hint_ELEMENT_LOAD_IMAGES"></span><script>BX.hint_replace(BX('hint_ELEMENT_LOAD_IMAGES'), '<?echo Loc::getMessage("ESOL_AE_LOAD_IMAGES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_LOAD_IMAGES]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_LOAD_IMAGES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_NOT_LOAD_STYLES"); ?>: <span id="hint_ELEMENT_NOT_LOAD_STYLES"></span><script>BX.hint_replace(BX('hint_ELEMENT_NOT_LOAD_STYLES'), '<?echo Loc::getMessage("ESOL_AE_NOT_LOAD_STYLES_HINT"); ?>');</script></td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_STYLES]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_STYLES']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_NOT_LOAD_FORMATTING"); ?>:</td>
			<td>
				<input type="checkbox" name="SETTINGS_DEFAULT[ELEMENT_NOT_LOAD_FORMATTING]" value="Y" <?if($SETTINGS_DEFAULT['ELEMENT_NOT_LOAD_FORMATTING']=='Y'){echo 'checked';}?>>
			</td>
		</tr>
		
		<tr>
			<td><?echo Loc::getMessage("ESOL_AE_COUNT_LINES_FOR_PREVIEW"); ?>:</td>
			<td>
				<input type="text" name="SETTINGS_DEFAULT[COUNT_LINES_FOR_PREVIEW]" value="<?echo htmlspecialcharsex($SETTINGS_DEFAULT['COUNT_LINES_FOR_PREVIEW'])?>" placeholder="10">
			</td>
		</tr>
		
		<?
		$arEntityOptions = $fl->getEntityOptions();
		if(is_array($arEntityOptions) && count($arEntityOptions) > 0)
		{
			?>
			<tr class="heading">
				<td colspan="2"><?echo Loc::getMessage("ESOL_AE_SETTINGS_ADDITONAL"); ?> <a href="javascript:void(0)" onclick="EProfile.ToggleAdditionalSettings(this)" class="kda-head-more show" id="kda-head-more-link"><?echo Loc::getMessage("ESOL_AE_SETTINGS_ADDITONAL_SHOW_HIDE"); ?></a></td>
			</tr>
			<?
			foreach($arEntityOptions as $arOption)
			{
				?>
				<tr>
					<td><?echo $arOption['NAME']; ?>:</td>
					<td>
						<?if($arOption['TYPE']=='CHECKBOX'){?>
							<input type="checkbox" name="SETTINGS_DEFAULT[<?echo $arOption['CODE'];?>]" value="Y" <?if($SETTINGS_DEFAULT[$arOption['CODE']]=='Y'){echo 'checked';}?>>
						<?}?>
					</td>
				</tr>
				<?
			}
		}
		?>
		
	<?
	}
}
$tabControl->EndTab();
?>

<?$tabControl->BeginNextTab();
if ($STEP == 2)
{
?>
	
	<tr>
		<td colspan="2" id="esol_aie_preview_file">
			<div class="kda-ie-file-preloader">
				<?echo Loc::getMessage("ESOL_AE_PRELOADING"); ?>
			</div>
		</td>
	</tr>
	
	<?
}
$tabControl->EndTab();
?>


<?$tabControl->BeginNextTab();
if ($STEP == 3)
{
?>
	<tr>
		<td id="resblock" class="kda-ie-result">
		 <table width="100%"><tr><td width="50%">
			<div id="progressbar"><span class="pline"></span><span class="presult load"><b>0%</b><span 
				data-prefix="<?echo Loc::getMessage("ESOL_AE_READ_LINES"); ?>" 
				data-import="<?echo Loc::getMessage("ESOL_AE_STATUS_IMPORT"); ?>" 
				data-deactivate_elements="<?echo Loc::getMessage("ESOL_AE_STATUS_DEACTIVATE_ELEMENTS"); ?>" 
				data-deactivate_sections="<?echo Loc::getMessage("ESOL_AE_STATUS_DEACTIVATE_SECTIONS"); ?>" 
			><?echo Loc::getMessage("ESOL_AE_IMPORT_INIT"); ?></span></span></div>

			<div id="block_error_import" style="display: none;">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => Loc::getMessage("ESOL_AE_IMPORT_ERROR_CONNECT"),
					"DETAILS" => '<div>'.(COption::GetOptionString($moduleId, 'AUTO_CONTINUE_IMPORT', 'N')=='Y' ? sprintf(Loc::getMessage("ESOL_AE_IMPORT_AUTO_CONTINUE"), '<span id="kda_ie_auto_continue_time"></span>').'<br>' : '').'<a href="javascript:void(0)" onclick="EProfile.ContinueProccess(this, '.$PROFILE_ID.');" id="kda_ie_continue_link">'.Loc::getMessage("ESOL_AE_PROCESSED_CONTINUE").'</a><!--<br><br>'.sprintf(Loc::getMessage("ESOL_AE_IMPORT_ERROR_CONNECT_COMMENT"), '/bitrix/admin/settings.php?lang=ru&mid='.$moduleId.'&mid_menu=1').'--></div>',
					"HTML" => true,
				))?>
			</div>
			
			<div id="block_error" style="display: none;">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "ERROR",
					"MESSAGE" => Loc::getMessage("ESOL_AE_IMPORT_ERROR"),
					"DETAILS" => '<div id="res_error"></div>',
					"HTML" => true,
				))?>
			</div>
		 </td><td>
			<div class="detail_status">
				<?echo CAdminMessage::ShowMessage(array(
					"TYPE" => "PROGRESS",
					"MESSAGE" => '<!--<div id="res_continue">'.Loc::getMessage("ESOL_AE_AUTO_REFRESH_CONTINUE").'</div><div id="res_finish" style="display: none;">'.Loc::getMessage("ESOL_AE_SUCCESS").'</div>-->',
					"DETAILS" =>

					Loc::getMessage("ESOL_AE_SU_ALL").' <b id="total_line">0</b><br>'
					.Loc::getMessage("ESOL_AE_SU_CORR").' <b id="correct_line">0</b><br>'
					.Loc::getMessage("ESOL_AE_SU_ER").' <b id="error_line">0</b><br>'
					.Loc::getMessage("ESOL_AE_SU_ELEMENT_ADDED").' <b id="element_added_line">0</b><br>'
					.Loc::getMessage("ESOL_AE_SU_ELEMENT_UPDATED").' <b id="element_updated_line">0</b><br>'
					.($SETTINGS_DEFAULT['ONLY_DELETE_MODE']=='Y' ? (Loc::getMessage("ESOL_AE_SU_ELEMENT_DELETED").' <b id="element_removed_line">0</b><br>') : '')
					,
					"HTML" => true,
				))?>
			</div>
		 </td></tr></table>
		</td>
	</tr>
<?
}
$tabControl->EndTab();
?>

<?$tabControl->Buttons();
?>


<?echo bitrix_sessid_post(); ?>
<?
if($STEP > 1)
{
	if(strlen($PROFILE_ID) > 0)
	{
		?><input type="hidden" name="PROFILE_ID" value="<?echo htmlspecialcharsbx($PROFILE_ID) ?>"><?
		?><input type="hidden" name="entity" value="<?echo htmlspecialcharsbx($entityId) ?>"><?
	}
	else
	{
		foreach($SETTINGS_DEFAULT as $k=>$v)
		{
			?><input type="hidden" name="SETTINGS_DEFAULT[<?echo $k?>]" value="<?echo htmlspecialcharsbx($v) ?>"><?
		}
	}
}
?>


<?
if($STEP == 2){ ?>
<input type="submit" name="backButton" value="&lt;&lt; <?echo Loc::getMessage("ESOL_AE_BACK"); ?>">
<?
}

if($STEP == 1 || $STEP == 2){ ?>
<input type="submit" name="saveConfigButton" value="<?echo Loc::getMessage("ESOL_AE_SAVE_CONFIGURATION"); ?>" style="float: right;">
<?
}

if($STEP < 3)
{
?>
	<input type="hidden" name="STEP" value="<?echo $STEP + 1; ?>">
	<input type="submit" value="<?echo ($STEP == 2) ? Loc::getMessage("ESOL_AE_NEXT_STEP_F") : Loc::getMessage("ESOL_AE_NEXT_STEP"); ?> &gt;&gt;" name="submit_btn" class="adm-btn-save">
<? 
}
else
{
?>
	<input type="hidden" name="STEP" value="1">
	<input type="submit" name="backButton2" value="&lt;&lt; <?echo Loc::getMessage("ESOL_AE_2_1_STEP"); ?>" class="adm-btn-save">
<?
}
?>

<?$tabControl->End();
?>

</form>

<script language="JavaScript">
<?if ($STEP < 2): 
	$arFile = \Bitrix\EsolAie\Import\Utils::GetShowFileBySettings($SETTINGS_DEFAULT);
	if($arFile['link'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').attr('target', '_blank').attr('href', '<?echo addslashes($arFile['link'])?>');<?
	}
	if($arFile['path'])
	{
		?>
		$('#bx_file_data_file_cont .adm-input-file-name').text('<?echo addslashes($arFile['path'])?>');<?
	}
?>
tabControl.SelectTab("edit1");
tabControl.DisableTab("edit2");
tabControl.DisableTab("edit3");
<?elseif ($STEP == 2): 
	//$arMenu = $fl->GetLineActions();
?>
tabControl.SelectTab("edit2");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit3");

<?/*?>
var admKDAMessages = {};
admKDAMessages['lineActions'] = <?echo \Bitrix\EsolAie\Utils::PhpToJSObject($arMenu);?>;
<?*/?>
<?elseif ($STEP > 2): ?>
tabControl.SelectTab("edit3");
tabControl.DisableTab("edit1");
tabControl.DisableTab("edit2");

<?
$arPost = $_POST;
unset($arPost['EXTRASETTINGS']);
if(COption::GetOptionString($moduleId, 'SET_MAX_EXECUTION_TIME')=='Y')
{
	$delay = (int)COption::GetOptionString($moduleId, 'EXECUTION_DELAY');
	$stepsTime = (int)COption::GetOptionString($moduleId, 'MAX_EXECUTION_TIME');
	if($delay > 0) $arPost['STEPS_DELAY'] = $delay;
	if($stepsTime > 0) $arPost['STEPS_TIME'] = $stepsTime;
}
else
{
	$stepsTime = intval(ini_get('max_execution_time'));
	if($stepsTime > 0) $arPost['STEPS_TIME'] = $stepsTime;
}

if($_POST['PROCESS_CONTINUE']=='Y'){
?>
	EImport.Init(<?=\Bitrix\EsolAie\Utils::PhpToJSObject($arPost);?>, <?=\Bitrix\EsolAie\Utils::PhpToJSObject($oProfile->GetProccessParams($_POST['PROFILE_ID']));?>);
<?}else{?>
	EImport.Init(<?=\Bitrix\EsolAie\Utils::PhpToJSObject($arPost);?>);
<?}?>
<?endif; ?>
//-->
</script>

<?
require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>
