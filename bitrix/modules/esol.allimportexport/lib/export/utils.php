<?php
namespace Bitrix\EsolAie\Export;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Utils {
	protected static $moduleId = 'esol.allimportexport';
	protected static $moduleSubDir = 'export/';
	protected static $currencyRates = null;
	protected static $zipArchiveOption = 'ZIPARCHIVE_WRITE_MODE';
	protected static $lastDataRow = 0;
	
	public static function GetOfferIblock($IBLOCK_ID, $retarray=false)
	{
		if(!$IBLOCK_ID || !Loader::includeModule('catalog')) return false;
		$dbRes = CCatalog::GetList(array(), array('IBLOCK_ID'=>$IBLOCK_ID));
		$arFields = $dbRes->Fetch();
		if(!$arFields['OFFERS_IBLOCK_ID'])
		{
			$dbRes = CCatalog::GetList(array(), array('PRODUCT_IBLOCK_ID'=>$IBLOCK_ID));
			if($arFields2 = $dbRes->Fetch())
			{
				$arFields = array_merge($arFields2, array(
					'IBLOCK_ID' => $arFields2['PRODUCT_IBLOCK_ID'],
					'YANDEX_EXPORT' => $arFields2['YANDEX_EXPORT'],
					'SUBSCRIPTION' => $arFields2['SUBSCRIPTION'],
					'VAT_ID' => $arFields2['VAT_ID'],
					'PRODUCT_IBLOCK_ID' => 0,
					'SKU_PROPERTY_ID' => 0,
					'OFFERS_PROPERTY_ID' => $arFields2['SKU_PROPERTY_ID'],
					'OFFERS_IBLOCK_ID' => $arFields2['IBLOCK_ID'],
					'ID' => $arFields2['PRODUCT_IBLOCK_ID'],
				));
			}
		}
		if(!$arFields['OFFERS_IBLOCK_ID'])
		{
			$arFields = array();
			foreach(GetModuleEvents(static::$moduleId, "OnGetOfferIblock", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arFields, $IBLOCK_ID));
			}
		}
		if($arFields['OFFERS_IBLOCK_ID'])
		{
			if($retarray) return $arFields;
			else return $arFields['OFFERS_IBLOCK_ID'];
		}
		return false;
	}
	
	public static function GetFileName($fn)
	{
		global $APPLICATION;
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$fn)) return $fn;
		
		if(\Bitrix\EsolAie\Utils::IsUtfMode()) $tmpfile = $APPLICATION->ConvertCharsetArray($fn, LANG_CHARSET, 'CP1251');
		else $tmpfile = $APPLICATION->ConvertCharsetArray($fn, LANG_CHARSET, 'UTF-8');
		
		if(file_exists($_SERVER['DOCUMENT_ROOT'].$tmpfile)) return $tmpfile;
		
		return false;
	}
	
	public static function Win1251Utf8($str)
	{
		global $APPLICATION;
		return $APPLICATION->ConvertCharset($str, "Windows-1251", "UTF-8");
	}
	
	public static function GetFileLinesCount($fn)
	{
		if(!file_exists($fn)) return 0;
		
		$cnt = 0;
		$handle = fopen($fn, 'r');
		while (!feof($handle)) {
			$buffer = trim(fgets($handle));
			if($buffer) $cnt++;
		}
		fclose($handle);
		return $cnt;
	}
	
	public static function SortFileIds($fn)
	{
		if(!file_exists($fn)) return 0;

		$arIds = array();
		$handle = fopen($fn, 'r');
		while (!feof($handle)) {
			$buffer = trim(fgets($handle, 128));
			if($buffer) $arIds[] = (int)$buffer;
		}
		fclose($handle);
		sort($arIds, SORT_NUMERIC);

		unlink($fn);

		$handle = fopen($fn, 'a');
		$cnt = count($arIds);
		$step = 10000;
		for($i=0; $i<$cnt; $i+=$step)
		{
			fwrite($handle, implode("\r\n", array_slice($arIds, $i, $step))."\r\n");
		}
		fclose($handle);
		
		if($cnt > 0) return end($arIds);
		else return 0;
	}
	
	public static function GetPartIdsFromFile($fn, $min)
	{
		if(!file_exists($fn)) return array();

		$cnt = 0;
		$maxCnt = 5000;
		$arIds = array();
		$handle = fopen($fn, 'r');
		while (!feof($handle) && $maxCnt>$cnt) {
			$buffer = (int)trim(fgets($handle, 128));
			if($buffer > $min)
			{
				$arIds[] = (int)$buffer;
				$cnt++;
			}
		}
		fclose($handle);
		return $arIds;
	}
	
	public static function GetFileArray($id)
	{
		if(class_exists('\Bitrix\Main\FileTable'))
		{
			if($arFile = \Bitrix\Main\FileTable::getList(array('filter'=>array('ID'=>$id)))->fetch())
			{
				if(is_callable(array($arFile['TIMESTAMP_X'], 'toString'))) $arFile['TIMESTAMP_X'] = $arFile['TIMESTAMP_X']->toString();
				$arFile['SRC'] = \CFile::GetFileSRC($arFile, false, false);
			}
		}
		else
		{
			$arFile = \CFile::GetFileArray($id);
		}
		return $arFile;
	}
	
	function SaveFile($arFile, $strSavePath, $bForceMD5=false, $bSkipExt=false)
	{
		$strFileName = GetFileName($arFile["name"]);	/* filename.gif */

		if(isset($arFile["del"]) && $arFile["del"] <> '')
		{
			CFile::Delete($arFile["old_file"]);
			if($strFileName == '')
				return "NULL";
		}

		if($arFile["name"] == '')
		{
			if(isset($arFile["description"]) && intval($arFile["old_file"])>0)
			{
				CFile::UpdateDesc($arFile["old_file"], $arFile["description"]);
			}
			return false;
		}

		if (isset($arFile["content"]))
		{
			if (!isset($arFile["size"]))
			{
				$arFile["size"] = self::BinStrlen($arFile["content"]);
			}
		}
		else
		{
			try
			{
				$file = new \Bitrix\Main\IO\File(\Bitrix\Main\IO\Path::convertPhysicalToLogical($arFile["tmp_name"]));
				$arFile["size"] = $file->getSize();
			}
			catch(\Bitrix\Main\IO\IoException $e)
			{
				$arFile["size"] = 0;
			}
		}

		$arFile["ORIGINAL_NAME"] = $strFileName;

		//translit, replace unsafe chars, etc.
		$strFileName = self::transformName($strFileName, $bForceMD5, $bSkipExt);

		//transformed name must be valid, check disk quota, etc.
		if (self::validateFile($strFileName, $arFile) !== "")
		{
			return false;
		}

		if($arFile["type"] == "image/pjpeg" || $arFile["type"] == "image/jpg")
		{
			$arFile["type"] = "image/jpeg";
		}

		$bExternalStorage = false;
		/*foreach(GetModuleEvents("main", "OnFileSave", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFile, $strFileName, $strSavePath, $bForceMD5, $bSkipExt)))
			{
				$bExternalStorage = true;
				break;
			}
		}*/

		if(!$bExternalStorage)
		{
			$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
			$io = CBXVirtualIo::GetInstance();
			if($bForceMD5 != true)
			{
				$dir_add = '';
				$i=0;
				while(true)
				{
					$dir_add = substr(md5(uniqid("", true)), 0, 3);
					if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$dir_add."/".$strFileName))
					{
						break;
					}
					if($i >= 25)
					{
						$j=0;
						while(true)
						{
							$dir_add = substr(md5(mt_rand()), 0, 3)."/".substr(md5(mt_rand()), 0, 3);
							if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$dir_add."/".$strFileName))
							{
								break;
							}
							if($j >= 25)
							{
								$dir_add = substr(md5(mt_rand()), 0, 3)."/".md5(mt_rand());
								break;
							}
							$j++;
						}
						break;
					}
					$i++;
				}
				if(substr($strSavePath, -1, 1) <> "/")
					$strSavePath .= "/".$dir_add;
				else
					$strSavePath .= $dir_add."/";
			}
			else
			{
				$strFileExt = ($bSkipExt == true || ($ext = GetFileExtension($strFileName)) == ''? '' : ".".$ext);
				while(true)
				{
					if(substr($strSavePath, -1, 1) <> "/")
						$strSavePath .= "/".substr($strFileName, 0, 3);
					else
						$strSavePath .= substr($strFileName, 0, 3)."/";

					if(!$io->FileExists($_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/".$strFileName))
						break;

					//try the new name
					$strFileName = md5(uniqid("", true)).$strFileExt;
				}
			}

			$arFile["SUBDIR"] = $strSavePath;
			$arFile["FILE_NAME"] = $strFileName;
			$strDirName = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/".$strSavePath."/";
			$strDbFileNameX = $strDirName.$strFileName;
			$strPhysicalFileNameX = $io->GetPhysicalName($strDbFileNameX);

			CheckDirPath($strDirName);

			if(is_set($arFile, "content"))
			{
				$f = fopen($strPhysicalFileNameX, "ab");
				if(!$f)
					return false;
				if(fwrite($f, $arFile["content"]) === false)
					return false;
				fclose($f);
			}
			elseif(
				!copy($arFile["tmp_name"], $strPhysicalFileNameX)
				&& !move_uploaded_file($arFile["tmp_name"], $strPhysicalFileNameX)
			)
			{
				CFile::Delete($arFile["old_file"]);
				return false;
			}

			if(isset($arFile["old_file"]))
				CFile::Delete($arFile["old_file"]);

			@chmod($strPhysicalFileNameX, BX_FILE_PERMISSIONS);

			//flash is not an image
			$flashEnabled = !CFile::IsImage($arFile["ORIGINAL_NAME"], $arFile["type"]);

			$imgArray = CFile::GetImageSize($strDbFileNameX, false, $flashEnabled);

			if(is_array($imgArray))
			{
				$arFile["WIDTH"] = $imgArray[0];
				$arFile["HEIGHT"] = $imgArray[1];

				if($imgArray[2] == IMAGETYPE_JPEG)
				{
					$exifData = CFile::ExtractImageExif($io->GetPhysicalName($strDbFileNameX));
					if ($exifData  && isset($exifData['Orientation']))
					{
						//swap width and height
						if ($exifData['Orientation'] >= 5 && $exifData['Orientation'] <= 8)
						{
							$arFile["WIDTH"] = $imgArray[1];
							$arFile["HEIGHT"] = $imgArray[0];
						}

						$properlyOriented = CFile::ImageHandleOrientation($exifData['Orientation'], $io->GetPhysicalName($strDbFileNameX));
						if ($properlyOriented)
						{
							$jpgQuality = intval(COption::GetOptionString('main', 'image_resize_quality', '95'));
							if($jpgQuality <= 0 || $jpgQuality > 100)
								$jpgQuality = 95;
							imagejpeg($properlyOriented, $io->GetPhysicalName($strDbFileNameX), $jpgQuality);
						}
					}
				}
			}
			else
			{
				$arFile["WIDTH"] = 0;
				$arFile["HEIGHT"] = 0;
			}
		}

		if($arFile["WIDTH"] == 0 || $arFile["HEIGHT"] == 0)
		{
			//mock image because we got false from CFile::GetImageSize()
			if(strpos($arFile["type"], "image/") === 0)
			{
				$arFile["type"] = "application/octet-stream";
			}
		}

		if($arFile["type"] == '' || !is_string($arFile["type"]))
		{
			$arFile["type"] = "application/octet-stream";
		}

		/****************************** QUOTA ******************************/
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			CDiskQuota::updateDiskQuota("file", $arFile["size"], "insert");
		}
		/****************************** QUOTA ******************************/

		$NEW_IMAGE_ID = CFile::DoInsert(array(
			"HEIGHT" => $arFile["HEIGHT"],
			"WIDTH" => $arFile["WIDTH"],
			"FILE_SIZE" => $arFile["size"],
			"CONTENT_TYPE" => $arFile["type"],
			"SUBDIR" => $arFile["SUBDIR"],
			"FILE_NAME" => $arFile["FILE_NAME"],
			"MODULE_ID" => $arFile["MODULE_ID"],
			"ORIGINAL_NAME" => $arFile["ORIGINAL_NAME"],
			"DESCRIPTION" => isset($arFile["description"])? $arFile["description"]: '',
			"HANDLER_ID" => isset($arFile["HANDLER_ID"])? $arFile["HANDLER_ID"]: '',
			"EXTERNAL_ID" => isset($arFile["external_id"])? $arFile["external_id"]: md5(mt_rand()),
		));

		CFile::CleanCache($NEW_IMAGE_ID);
		return $NEW_IMAGE_ID;
	}
	
	protected static function transformName($name, $bForceMD5 = false, $bSkipExt = false)
	{
		//safe filename without path
		$fileName = GetFileName($name);

		$originalName = ($bForceMD5 != true);
		if($originalName)
		{
			//transforming original name:

			//transliteration
			if(COption::GetOptionString("main", "translit_original_file_name", "N") == "Y")
			{
				$fileName = CUtil::translit($fileName, LANGUAGE_ID, array("max_len"=>1024, "safe_chars"=>".", "replace_space" => '-'));
			}

			//replace invalid characters
			if(COption::GetOptionString("main", "convert_original_file_name", "Y") == "Y")
			{
				$io = CBXVirtualIo::GetInstance();
				$fileName = $io->RandomizeInvalidFilename($fileName);
			}
		}

		//.jpe is not image type on many systems
		if($bSkipExt == false && strtolower(GetFileExtension($fileName)) == "jpe")
		{
			$fileName = substr($fileName, 0, -4).".jpg";
		}

		//double extension vulnerability
		$fileName = RemoveScriptExtension($fileName);

		if(!$originalName)
		{
			//name is md5-generated:
			$fileName = md5(uniqid("", true)).($bSkipExt == true || ($ext = GetFileExtension($fileName)) == ''? '' : ".".$ext);
		}

		return $fileName;
	}

	protected static function validateFile($strFileName, $arFile)
	{
		if($strFileName == '')
			return Loc::getMessage("FILE_BAD_FILENAME");

		$io = CBXVirtualIo::GetInstance();
		if(!$io->ValidateFilenameString($strFileName))
			return Loc::getMessage("MAIN_BAD_FILENAME1");

		if(strlen($strFileName) > 255)
			return Loc::getMessage("MAIN_BAD_FILENAME_LEN");

		//check .htaccess etc.
		if(IsFileUnsafe($strFileName))
			return Loc::getMessage("FILE_BAD_TYPE");

		//nginx returns octet-stream for .jpg
		if(GetFileNameWithoutExtension($strFileName) == '')
			return Loc::getMessage("FILE_BAD_FILENAME");

		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$quota = new CDiskQuota();
			if (!$quota->checkDiskQuota($arFile))
				return Loc::getMessage("FILE_BAD_QUOTA");
		}

		return "";
	}
	
	public static function ShowFilter($arFields, $sTableID, $listIndex, $SETTINGS, $SETTINGS_DEFAULT)
	{
		$arFieldVals = (is_array($SETTINGS['FILTER'][$listIndex]) ? $SETTINGS['FILTER'][$listIndex] : array());
		?>
		<div class="find_form_inner">
		<?
		$arFindFields = Array();
		foreach($arFields as $k=>$v)
		{
			$arFindFields[$k] = $v['title'];
			if(isset($v['rel_class']) && $v['rel_class']=='\Bitrix\Sale\Internals\StatusLangTable')
			{
				$arFindFields[$k.'_custom'] = Loc::getMessage('KDA_EE_F_SALE_ORDER_STATUS');
				$arList = array();
				$dbRes = \Bitrix\Sale\Internals\StatusLangTable::getList(array('filter'=>array('LID'=>LANGUAGE_ID), 'select'=>array('STATUS_ID', 'NAME')));
				while($arr = $dbRes->Fetch())
				{
					$arList[$arr['STATUS_ID']] = '['.$arr['STATUS_ID'].'] '.$arr['NAME'];
				}
				$arFields[$k]['EXTRAFIELD'] = array(
					'title' => Loc::getMessage('KDA_EE_F_SALE_ORDER_STATUS'),
					'list' => $arList
				);
			}
			elseif(strpos($k, 'IE_PRODUCT_UTS.UF_PRODUCT_GROUP_TBL.UF_NAME')!==false && Loader::includeModule('highloadblock') && ($hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('NAME'=>'ProductMarkingCodeGroup')))->fetch()))
			{
				$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
				$entityDataClass = $entity->getDataClass();
				$arList = array();
				$dbRes = $entityDataClass::getList(array('select'=>array('UF_NAME')));
				while($arr = $dbRes->Fetch())
				{
					$arList[$arr['UF_NAME']] = $arr['UF_NAME'];
				}
				
				$arFindFields[$k.'_custom'] = Loc::getMessage('KDA_EE_F_UF_PRODUCT_GROUP');
				$arFields[$k]['EXTRAFIELD'] = array(
					'title' => Loc::getMessage('KDA_EE_F_UF_PRODUCT_GROUP'),
					'list' => $arList
				);
			}
		}
		
		$oFilter = new \CAdminFilter($sTableID."_filter", $arFindFields);
		
		$oFilter->Begin();
		
		foreach($arFields as $k=>$v)
		{
			$key = ToLower($k);
			if($v['primary'])
			{
				$val = '';
				if(isset($arFieldVals['find_entity_'.$key]) && strlen($arFieldVals['find_entity_'.$key]) > 0) 
				{
					$val = $arFieldVals['find_entity_'.$key];
				}
				elseif(!$arFieldVals['find_entity_'.$key.'_comp'] && ((isset($arFieldVals['find_entity_'.$key.'_start']) && strlen($arFieldVals['find_entity_'.$key.'_start']) > 0) || (isset($arFieldVals['find_entity_'.$key.'_end']) && strlen($arFieldVals['find_entity_'.$key.'_end']) > 0)))
				{
					$val = $arFieldVals['find_entity_'.$key.'_start'].' - '.$arFieldVals['find_entity_'.$key.'_end'];
					$arFieldVals['find_entity_'.$key.'_comp'] = 'from_to';
				}
			?>
			<tr>
				<td><?echo $v['title']?>:</td>
				<td nowrap>
					<select name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?=$key?>_comp]">
						<option value="eq" <?if($arFieldVals['find_entity_'.$key.'_comp']=='eq'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_EQ')?></option>
						<option value="neq" <?if($arFieldVals['find_entity_'.$key.'_comp']=='neq'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_NEQ')?></option>
						<option value="from_to" <?if($arFieldVals['find_entity_'.$key.'_comp']=='from_to'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_FROM_TO')?></option>
					</select>
					<input type="text" name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?echo $key;?>]" size="30" value="<?echo htmlspecialcharsex($val)?>">
				</td>
			</tr>
			<?
			}
			elseif($v['data_type']=='date' || $v['data_type']=='datetime')
			{
				$GLOBALS["SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_from]_FILTER_PERIOD"] = $arFieldVals['find_entity_'.$key.'_from_FILTER_PERIOD'];
				$GLOBALS["SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_from]_FILTER_DIRECTION"] = $arFieldVals['find_entity_'.$key.'_from_FILTER_DIRECTION'];
			?>
			<tr>
				<td><?echo $v['title']?>:</td>
				<td data-filter-period="<?echo htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_from_FILTER_PERIOD'])?>" data-filter-last-days="<?echo htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_from_FILTER_LAST_DAYS'])?>">
					<?echo CalendarPeriod("SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_from]", htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_from']), "SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_to]", htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_to']), "dataload", "Y")?></font>
					
					<?/*?>
					<select name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?=$key?>_comp]"><option value=""><?echo Loc::getMessage("KDA_EE_IS_VALUE_FROM_TO")?></option><option value="empty"<?if($arFieldVals['find_entity_'.$key.'_comp']=='empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_EE_IS_EMPTY")?></option><option value="not_empty"<?if($arFieldVals['find_entity_'.$key.'_comp']=='not_empty'){echo ' selected';}?>><?echo Loc::getMessage("KDA_EE_IS_NOT_EMPTY")?></option></select></select>
					<?echo CalendarPeriod("SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_from]", htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_from']), "SETTINGS[FILTER][".$listIndex."][find_entity_".$key."_to]", htmlspecialcharsex($arFieldVals['find_entity_'.$key.'_to']), "dataload")?>
					<?*/?>
				</td>
			</tr>
			<?
			}
			else
			{
			?>
			<tr>
				<td><?echo $v['title']?>:</td>
				<td>
					<select name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?=$key?>_comp]">
						<option value="eq" <?if($arFieldVals['find_entity_'.$key.'_comp']=='eq'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_EQ')?></option>
						<option value="neq" <?if($arFieldVals['find_entity_'.$key.'_comp']=='neq'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_NEQ')?></option>
						<option value="contain" <?if($arFieldVals['find_entity_'.$key.'_comp']=='contain'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_CONTAIN')?></option>
						<option value="not_contain" <?if($arFieldVals['find_entity_'.$key.'_comp']=='not_contain'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_COMPARE_NOT_CONTAIN')?></option>
						<option value="empty" <?if($arFieldVals['find_entity_'.$key.'_comp']=='empty'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_IS_EMPTY')?></option>
						<option value="not_empty" <?if($arFieldVals['find_entity_'.$key.'_comp']=='not_empty'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_IS_NOT_EMPTY')?></option>
						<option value="logical" <?if($arFieldVals['find_entity_'.$key.'_comp']=='logical'){echo 'selected';}?>><?=Loc::getMessage('KDA_EE_IS_LOGICAL')?></option>
					</select>
					<input type="text" name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?=$key?>]" value="<?echo htmlspecialcharsex($arFieldVals["find_entity_".$key])?>" size="30">
				</td>
			</tr>
			<?
				if(isset($v['EXTRAFIELD']) && !empty($v['EXTRAFIELD']['list']))
				{
				?>
				<tr>
					<td><?echo $v['EXTRAFIELD']['title']?>:</td>
					<td>
						<select name="SETTINGS[FILTER][<?=$listIndex?>][find_entity_<?=$key?>|list][]" value="<?echo htmlspecialcharsex($arFieldVals["find_entity_".$key])?>" size="4" multiple>
							<?
							foreach($v['EXTRAFIELD']['list'] as $ek=>$ev)
							{
								echo '<option value="'.htmlspecialcharsbx($ek).'"'.($arFieldVals["find_entity_".$key."|list"]==$ek ? ' selected' : '').'>'.htmlspecialcharsbx($ev).'</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<?
				}
			}
		}
		
		$oFilter->Buttons();
		?><span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="set_filter" value="<? echo Loc::getMessage("admin_lib_filter_set_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_set_butt_title"); ?>" onClick="return EList.ApplyFilter(this);"></span>
		<span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="del_filter" value="<? echo Loc::getMessage("admin_lib_filter_clear_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_clear_butt_title"); ?>" onClick="return EList.DeleteFilter(this);"></span>
		<?
		$oFilter->End();

		?>
		<!--</form>-->
		</div>
		<?
	}
	
	public static function ShowFilterHighload($sTableID, $listIndex, $SETTINGS, $SETTINGS_DEFAULT)
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		CJSCore::Init('file_input');
		$HLBL_ID = $SETTINGS_DEFAULT['HIGHLOADBLOCK_ID'];
		
		$arFields = (is_array($SETTINGS['FILTER'][$listIndex]) ? $SETTINGS['FILTER'][$listIndex] : array());
		
		$ufEntityId = 'HLBLOCK_'.$HLBL_ID;
		
		?>
		<!--<form method="GET" name="find_form" id="find_form" action="">-->
		<div class="find_form_inner">
		<?
			
		$filterValues = array();
		$arFindFields = array('ID');
		
		$USER_FIELD_MANAGER->AdminListAddFilterFields($ufEntityId, $filterFields);
		$USER_FIELD_MANAGER->AddFindFields($ufEntityId, $arFindFields);

		
		$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);
		
		$oFilter->Begin();
		
		?>
		<tr>
			<td>ID</td>
			<td><input type="text" name="SETTINGS[FILTER][<?=$listIndex?>][find_ID]" size="47" value="<?echo htmlspecialcharsbx($arFields['find_ID'])?>"><?=ShowFilterLogicHelp()?></td>
		</tr>
		<?
		//$USER_FIELD_MANAGER->AdminListShowFilter($ufEntityId);
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufEntityId, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME=>$arUserField)
		{
			if($arUserField["SHOW_FILTER"]!="N" && $arUserField["USER_TYPE"]["BASE_TYPE"]!="file")
			{
				echo $USER_FIELD_MANAGER->GetFilterHTML($arUserField, 'SETTINGS[FILTER]['.$listIndex.'][find_'.$FIELD_NAME.']', $arFields['find_'.$FIELD_NAME]);
			}
		}
	
		$oFilter->Buttons();
		?><span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="set_filter" value="<? echo Loc::getMessage("admin_lib_filter_set_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_set_butt_title"); ?>" onClick="return EList.ApplyFilter(this);"></span>
		<span class="adm-btn-wrap"><input type="submit"  class="adm-btn" name="del_filter" value="<? echo Loc::getMessage("admin_lib_filter_clear_butt"); ?>" title="<? echo Loc::getMessage("admin_lib_filter_clear_butt_title"); ?>" onClick="return EList.DeleteFilter(this);"></span>
		<?
		$oFilter->End();

		?>
		<!--</form>-->
		</div>
		<?
	}
	
	public static function ShowGroupPropertyField2($name, $property_fields, $values)
	{
		if(!is_array($values)) $values = Array();

		$res = "";
		$result = "";
		$bWas = false;
		$sections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$property_fields["LINK_IBLOCK_ID"]), array("ID", "NAME", "DEPTH_LEVEL"));
		while($ar = $sections->GetNext())
		{
			$res .= '<option value="'.$ar["ID"].'"';
			if(in_array($ar["ID"], $values))
			{
				$bWas = true;
				$res .= ' selected';
			}
			$res .= '>'.str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"].'</option>';
		}
		$result .= '<select name="'.$name.'[]" size="'.($property_fields["MULTIPLE"]=="Y" ? "5":"1").'" '.($property_fields["MULTIPLE"]=="Y"?"multiple":"").'>';
		$result .= '<option value=""'.(!$bWas?' selected':'').'>'.Loc::getMessage("IBLOCK_ELEMENT_EDIT_NOT_SET").'</option>';
		$result .= $res;
		$result .= '</select>';
		return $result;
	}
	
	public static function GetCellStyleFormatted($arStyles = array(), $arParams = array())
	{
		if(!is_array($arStyles)) $arStyles = array();
		//if(empty($arStyles)) return '';
		$style = '';
		if(!$arStyles['FONT_FAMILY'] && $arParams['FONT_FAMILY']) $arStyles['FONT_FAMILY'] = $arParams['FONT_FAMILY'];
		if(!$arStyles['FONT_SIZE'] && $arParams['FONT_SIZE']) $arStyles['FONT_SIZE'] = $arParams['FONT_SIZE'];
		if(!$arStyles['FONT_COLOR'] && $arParams['FONT_COLOR']) $arStyles['FONT_COLOR'] = $arParams['FONT_COLOR'];
		if(!$arStyles['STYLE_BOLD'] && $arParams['STYLE_BOLD']) $arStyles['STYLE_BOLD'] = $arParams['STYLE_BOLD'];
		if(!$arStyles['STYLE_ITALIC'] && $arParams['STYLE_ITALIC']) $arStyles['STYLE_ITALIC'] = $arParams['STYLE_ITALIC'];
		
		if($arStyles['FONT_FAMILY']) $style .= 'font-family:'.htmlspecialcharsex($arStyles['FONT_FAMILY']).';';
		if((int)$arStyles['FONT_SIZE'] > 0) $style .= 'font-size:'.((int)$arStyles['FONT_SIZE'] + 2).'px;';
		if($arStyles['FONT_COLOR']) $style .= 'color:'.htmlspecialcharsex($arStyles['FONT_COLOR']).';';
		if($arStyles['STYLE_BOLD']=='Y') $style .= 'font-weight:bold;';
		if($arStyles['STYLE_ITALIC']=='Y') $style .= 'font-style:italic;';
		if($arStyles['BACKGROUND_COLOR']) $style .= 'background-color:'.htmlspecialcharsex($arStyles['BACKGROUND_COLOR']).';';
		if($arStyles['INDENT']) $style .= 'padding-left:'.(intval($arStyles['INDENT'])*15).'px;';
		
		$textAlign = ToLower($arStyles['TEXT_ALIGN'] ? $arStyles['TEXT_ALIGN'] : $arParams['DISPLAY_TEXT_ALIGN']);
		if(!$textAlign) $textAlign = 'left';
		$style .= 'text-align:'.htmlspecialcharsex($textAlign).';';
		$verticalAlign = ToLower($arStyles['VERTICAL_ALIGN'] ? $arStyles['VERTICAL_ALIGN'] : $arParams['DISPLAY_VERTICAL_ALIGN']);
		if(!$verticalAlign) $verticalAlign = 'top';
		$style .= 'vertical-align:'.htmlspecialcharsex($verticalAlign).';';
		
		if(strlen($style) > 0) $style = 'style="'.$style.'"';
		return $style;
	}
	
	public static function PrepareTextRows(&$rows, $arParams=array(), $arStepParams=array())
	{
		if(is_array($rows))
		{
			foreach($rows as $listIndex=>$arRows)
			{
				if(is_array($rows[$listIndex]))
				{
					$rowsCount = (int)$arStepParams['rows2'][$listIndex];
					if(is_array($arParams['TEXT_ROWS_TOP'])) $rowsCount += count($arParams['TEXT_ROWS_TOP']);
					if($arParams['HIDE_COLUMN_TITLES']!='Y') $rowsCount += 1;
					if(is_array($arParams['TEXT_ROWS_TOP2'])) $rowsCount += count($arParams['TEXT_ROWS_TOP2']);
					
					foreach($rows[$listIndex] as $k=>$row)
					{
						$row = str_replace('{MAX_ROW_NUM}', $rowsCount, $row);
						$row = preg_replace_callback('/\{DATE_(\S*)\}/', array('\Bitrix\EsolAie\Export\Utils', 'GetDateFormat'), $row);
						$row = preg_replace_callback('/\{RATE_SITE\.(\S*)\}/', array('\Bitrix\EsolAie\Export\Utils', 'GetCurrenyRateSite'), $row);
						$row = preg_replace_callback('/\{RATE_CBR\.(\S*)\}/', array('\Bitrix\EsolAie\Export\Utils', 'GetCurrenyRateCbr'), $row);
						$rows[$listIndex][$k] = $row;
					}
				}
			}
		}
	}
	
	public static function PrepareTextRows2(&$rows, $lastDataRow)
	{
		static::$lastDataRow = $lastDataRow;
		if(is_array($rows))
		{
			foreach($rows as $k=>$row)
			{
				$rows[$k] = preg_replace_callback('/\{MAX_ROW_NUM([+-]\d+)?\}/', array(__CLASS__, 'PrepareTextRows2Callback'), $row);
			}
		}
	}
	
	public static function PrepareTextRows2Callback($m)
	{
		$cnt = static::$lastDataRow; 
		if(isset($m[1])) $cnt += (int)$m[1]; 
		return max(0, $cnt);
	}
	
	public static function PrepareExportFileName($name, $arParams=array())
	{
		$name = str_replace('{ELEMENT_ID}', $arParams['ELEMENT_ID'], $name);
		return preg_replace_callback('/\{DATE_(\S*)\}/', array('\Bitrix\EsolAie\Export\Utils', 'GetDateFormat'), $name);
	}
	
	public static function GetDateFormat($m)
	{
		$format = str_replace('_', ' ', $m[1]);
		$time = time();
		if(preg_match_all('/([jdmyY])([\-+]\d+)/', $format, $m2))
		{
			foreach($m2[1] as $k=>$key)
			{
				if($key=='j' || $key=='d') $time = mktime((int)date('h', $time), (int)date('i', $time), (int)date('s', $time), (int)date('n', $time), (int)date('j', $time) + (int)$m2[2][$k], (int)date('Y', $time));
				elseif($key=='m') $time = mktime((int)date('h', $time), (int)date('i', $time), (int)date('s', $time), (int)date('n', $time) + (int)$m2[2][$k], (int)date('j', $time), (int)date('Y', $time));
				elseif($key=='y' || $key=='Y') $time = mktime((int)date('h', $time), (int)date('i', $time), (int)date('s', $time), (int)date('n', $time), (int)date('j', $time), (int)date('Y', $time) + (int)$m2[2][$k]);
				$format = str_replace($m2[0][$k], $key, $format);
			}
		}
		if(Loader::includeModule("iblock"))
		{
			return ToLower(\CIBlockFormatProperties::DateFormat($format, $time));
		}
		else return date($format, $time);
	}
	
	public static function GetCurrenyRateSite($m)
	{
		if(Loader::includeModule("currency"))
		{
			$dbRes = \CCurrencyRates::GetList(($by="date"), ($order="desc"), array("CURRENCY" => $m[1]));
			if($arr = $dbRes->Fetch())
			{
				return $arr['RATE'];
			}
		}
		return '';
	}
	
	public static function GetFileExtension($filename)
	{
		$filename = end(explode('/', $filename));
		$arParts = explode('.', $filename);
		if(count($arParts) > 1) 
		{
			$ext = trim(array_pop($arParts));
			if(strlen($ext)==0 || strlen($ext)>4 || preg_match('/^(\d+)$/', $ext)) return '';
			if(ToLower($ext)=='gz' && count($arParts) > 1)
			{
				$ext = array_pop($arParts).'.'.$ext;
			}
			return $ext;
		}
		else return '';
	}
	
	public static function GetCurrenyRateCbr($m)
	{
		$arRates = static::GetCurrencyRates();
		if(isset($arRates[$m[1]])) return $arRates[$m[1]];
		return '';
	}
	
	public static function GetCurrencyRates()
	{
		if(!isset(static::$currencyRates))
		{
			$arRates = \Bitrix\EsolAie\Utils::Unserialize(\Bitrix\Main\Config\Option::get(static::$moduleId, 'CURRENCY_RATES', ''));
			if(!is_array($arRates)) $arRates = array();
			if(!isset($arRates['TIME']) || $arRates['TIME'] < time() - 6*60*60)
			{
				$arRates2 = array();
				$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20));
				$res = $client->get('http://www.cbr.ru/scripts/XML_daily.asp');
				if($res)
				{
					$xml = simplexml_load_string($res);
					if($xml->Valute)
					{
						foreach($xml->Valute as $val)
						{
							$numVal = static::GetFloatVal((string)$val->Value);
							if($numVal > 0)$arRates2[(string)$val->CharCode] = (string)$numVal;
						}
					}
				}
				if(count($arRates2) > 1)
				{
					$arRates = $arRates2;
					$arRates['TIME'] = time();
					\Bitrix\Main\Config\Option::set(static::$moduleId, 'CURRENCY_RATES', serialize($arRates));
				}
			}
			if(Loader::includeModule('currency'))
			{
				if(!isset($arRates['USD'])) $arRates['USD'] = CCurrencyRates::ConvertCurrency(1, 'USD', 'RUB');
				if(!isset($arRates['EUR'])) $arRates['EUR'] = CCurrencyRates::ConvertCurrency(1, 'EUR', 'RUB');
			}
			static::$currencyRates = $arRates;
		}
		return static::$currencyRates;
	}
	
	public static function GetFloatVal($val, $precision=0)
	{
		$val = floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
		if($precision > 0) $val = round($val, $precision);
		return $val;
	}
	
	public static function GetStringOperation(&$val, $op)
	{
		if($op=='eq') return '=';
		elseif($op=='neq') return '!=';
		elseif($op=='contain') return '%';
		elseif($op=='not_contain') return '!%';
		elseif($op=='logical') return '?';
		elseif($op=='empty')
		{
			$val = false;
			return '';
		}
		elseif($op=='not_empty')
		{
			$val = false;
			return '!';
		}
		else return '';
	}
	
	public static function AddDateFilter(&$arFilter, $arAddFilter, $field, $addField)
	{
		if($arAddFilter[$addField.'_from_FILTER_PERIOD']=='last_days'
			&& isset($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']) && strlen(trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS'])) > 0)
		{
			$days = (int)trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']);
			$arFilter['>='.$field] = ConvertTimeStamp(time()-$days*24*60*60, "FULL");
		}
		else
		{
			Loader::includeModule("iblock");
			if(!empty($arAddFilter[$addField.'_from'])) $arFilter['>='.$field] = $arAddFilter[$addField.'_from'];
			if(!empty($arAddFilter[$addField.'_to'])) $arFilter['<='.$field] = \CIBlock::isShortDate($arAddFilter[$addField.'_to'])? ConvertTimeStamp(AddTime(MakeTimeStamp($arAddFilter[$addField.'_to']), 1, "D"), "FULL"): $arAddFilter[$addField.'_to'];
		}
	}
	
	public static function RemoveTmpFiles($maxTime = 5)
	{
		$timeBegin = time();
		$docRoot = $_SERVER["DOCUMENT_ROOT"];
		$tmpDir = $docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		$arOldDirs = array();
		$arActDirs = array();
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(in_array($file, array('.', '..'))) continue;
				if(is_dir($tmpDir.$file))
				{
					if(!in_array($file, $arActDirs) && (time() - filemtime($tmpDir.$file) > 24*60*60))
					{
						$arOldDirs[] = $file;
					}
				}
				elseif(substr($file, -4)=='.txt')
				{
					$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp(file_get_contents($tmpDir.$file));
					if(is_array($arParams) && isset($arParams['tmpdir']))
					{
						$actDir = preg_replace('/^.*\/([^\/]+)$/', '$1', trim($arParams['tmpdir'], '/'));
						$arActDirs[] = $actDir;
					}
				}
			}
			$arOldDirs = array_diff($arOldDirs, $arActDirs);
			foreach($arOldDirs as $subdir)
			{
				$oldDir = substr($tmpDir, strlen($docRoot)).$subdir;
				DeleteDirFilesEx($oldDir);
				if(($maxTime > 0) && (time() - $timeBegin >= $maxTime)) return;
			}
			closedir($dh);
		}
		
		$tmpDir = $docRoot.'/upload/tmp/';
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(!preg_match('/^[0-9a-z]{3}$/', $file) && !preg_match('/^[0-9a-z]{32}$/', $file)) continue;
				$subdir = $tmpDir.$file;
				if(is_dir($subdir))
				{
					$subdir .= '/';
					if(time() - filemtime($subdir) > 24*60*60)
					{
						if($dh2 = opendir($subdir))
						{
							$emptyDir = true;
							while(($file2 = readdir($dh2)) !== false)
							{
								if(in_array($file2, array('.', '..'))) continue;
								if(time() - filemtime($subdir) > 24*60*60)
								{
									if(is_dir($subdir.$file2))
									{
										$oldDir = substr($subdir.$file2, strlen($docRoot));
										DeleteDirFilesEx($oldDir);
									}
									else
									{
										unlink($subdir.$file2);
									}
								}
								else
								{
									$emptyDir = false;
								}
							}
							closedir($dh2);
							if($emptyDir)
							{
								unlink($subdir);
							}
						}
						
						if(($maxTime > 0) && (time() - $timeBegin >= $maxTime)) return;
					}
				}
			}
			closedir($dh);
		}
	}
	
	public static function CheckZipArchive()
	{
		$optionName = static::$zipArchiveOption;
		if(class_exists('\ZipArchive'))
		{
			$tmpDir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
			CheckDirPath($tmpDir);
			$tempPathZip = $tmpDir.'test.zip';
			$tempPathTxt = $tmpDir.'test.txt';
			file_put_contents($tempPathTxt, 'test');
			\Bitrix\Main\Config\Option::set(static::$moduleId, $optionName, 'NONE');
			if(($zipObj = new \ZipArchive()) && $zipObj->open($tempPathZip, \ZipArchive::OVERWRITE|\ZipArchive::CREATE)===true)
			{
				$zipObj->addFile($tempPathTxt, 'test.txt');
				$zipObj->close();
				if(file_exists($tempPathZip))
				{
					\Bitrix\Main\Config\Option::set(static::$moduleId, $optionName, 'OVERWRITE_CREATE');
					unlink($tempPathZip);
				}
			}
			unlink($tempPathTxt);
		}
	}
	
	public static function CanUseZipArchive()
	{
		if(!class_exists('\ZipArchive')) return false;
		$optionName = static::$zipArchiveOption;
		if(\Bitrix\Main\Config\Option::get(static::$moduleId, $optionName)=='NONE') return false;
		return true;
	}
	
	public static function getUtfModifier()
	{
		if(self::getSiteEncoding()=='utf-8') return 'u';
		else return '';
	}
	
	public static function BinStrlen($val)
	{
		return mb_strlen($val, 'latin1');
	}
	
    public static function BinStrpos($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, 'latin1');
    }
	
    public static function BinSubstr($buf, $start, $length=null)
    {
		return mb_substr($buf, $start, ($length===null ? 2000000000 : $length), 'latin1');
    }
	
	public static function getSiteEncoding()
	{
		if (\Bitrix\EsolAie\Utils::IsUtfMode())
			$logicalEncoding = "utf-8";
		elseif (defined("SITE_CHARSET") && (strlen(SITE_CHARSET) > 0))
			$logicalEncoding = SITE_CHARSET;
		elseif (defined("LANG_CHARSET") && (strlen(LANG_CHARSET) > 0))
			$logicalEncoding = LANG_CHARSET;
		elseif (defined("BX_DEFAULT_CHARSET"))
			$logicalEncoding = BX_DEFAULT_CHARSET;
		else
			$logicalEncoding = "windows-1251";

		return strtolower($logicalEncoding);
	}
	
	public static function getfileSystemEncoding()
	{
		$fileSystemEncoding = strtolower(defined("BX_FILE_SYSTEM_ENCODING") ? BX_FILE_SYSTEM_ENCODING : "");

		if (empty($fileSystemEncoding))
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
				$fileSystemEncoding =  "windows-1251";
			else
				$fileSystemEncoding = "utf-8";
		}

		return $fileSystemEncoding;
	}
}
?>