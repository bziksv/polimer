<?php
namespace Bitrix\EsolAie\Import;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Utils {
	protected static $moduleId = 'esol.allimportexport';
	protected static $moduleSubDir = '';
	protected static $colLetters = array();
	protected static $arAgents = array();
	protected static $countAgents = 0;
	protected static $fileSystemEncoding = null;
	protected static $jsCounter = 0;
	
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
	
	public static function SaveFile($arFile, $strSavePath=false, $bForceMD5=false, $bSkipExt=false)
	{
		if($arFile['type']=='text/html')
		{
			$arFile = self::MakeFileArray($arFile);
		}

		if($strSavePath===false) $strSavePath = static::$moduleId;
		$isUtf = \Bitrix\EsolAie\Utils::IsUtfMode();
		if(\CUtil::DetectUTF8($arFile["name"]))
		{
			if(!$isUtf) $arFile["name"] = \Bitrix\Main\Text\Encoding::convertEncoding($arFile["name"], 'utf-8', LANG_CHARSET);
		}
		else
		{
			if($isUtf) $arFile["name"] = \Bitrix\Main\Text\Encoding::convertEncoding($arFile["name"], 'windows-1251', LANG_CHARSET);
		}
		$strFileName = GetFileName($arFile["name"]);	/* filename.gif */
		if(strpos($strFileName, '.')===0) $strFileName = '_'.$strFileName;

		if(isset($arFile["del"]) && $arFile["del"] <> '')
		{
			\CFile::Delete($arFile["old_file"]);
			if($strFileName == '')
				return "NULL";
		}

		if($arFile["name"] == '')
		{
			if(isset($arFile["description"]) && intval($arFile["old_file"])>0)
			{
				\CFile::UpdateDesc($arFile["old_file"], $arFile["description"]);
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
			$upload_dir = \COption::GetOptionString("main", "upload_dir", "upload");
			$io = \CBXVirtualIo::GetInstance();
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
				$strFileExt = ($bSkipExt == true || ($ext = self::GetFileExtension($strFileName)) == ''? '' : ".".$ext);
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
				\CFile::Delete($arFile["old_file"]);
				return false;
			}

			if(isset($arFile["old_file"]))
				\CFile::Delete($arFile["old_file"]);

			@chmod($strPhysicalFileNameX, BX_FILE_PERMISSIONS);

			//flash is not an image
			$flashEnabled = !\CFile::IsImage($arFile["ORIGINAL_NAME"], $arFile["type"]);

			$imgArray = \CFile::GetImageSize($strDbFileNameX, false, $flashEnabled);

			if(is_array($imgArray))
			{
				$arFile["WIDTH"] = $imgArray[0];
				$arFile["HEIGHT"] = $imgArray[1];

				if($imgArray[2] == IMAGETYPE_JPEG)
				{
					$exifData = \CFile::ExtractImageExif($io->GetPhysicalName($strDbFileNameX));
					if ($exifData  && isset($exifData['Orientation']))
					{
						//swap width and height
						if ($exifData['Orientation'] >= 5 && $exifData['Orientation'] <= 8)
						{
							$arFile["WIDTH"] = $imgArray[1];
							$arFile["HEIGHT"] = $imgArray[0];
						}

						$properlyOriented = \CFile::ImageHandleOrientation($exifData['Orientation'], $io->GetPhysicalName($strDbFileNameX));
						if ($properlyOriented)
						{
							$jpgQuality = intval(\COption::GetOptionString('main', 'image_resize_quality', '95'));
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
			//mock image because we got false from \CFile::GetImageSize()
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
		if (\COption::GetOptionInt("main", "disk_space") > 0)
		{
			\CDiskQuota::updateDiskQuota("file", $arFile["size"], "insert");
		}
		/****************************** QUOTA ******************************/

		$NEW_IMAGE_ID = \CFile::DoInsert(array(
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

		\CFile::CleanCache($NEW_IMAGE_ID);
		return $NEW_IMAGE_ID;
	}
	
	public static function DeleteFile($FILE_ID)
	{
		\CFile::Delete($FILE_ID);
		\Bitrix\EsolAie\Import\ZipArchive::RemoveFileDir($FILE_ID);
	}
	
	public static function CopyFile($FILE_ID, $bRegister = true, $newPath = "")
	{
		global $DB;

		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		$z = \CFile::GetByID($FILE_ID);
		if($zr = $z->Fetch())
		{
			/****************************** QUOTA ******************************/
			if (\COption::GetOptionInt("main", "disk_space") > 0)
			{
				$quota = new \CDiskQuota();
				if (!$quota->checkDiskQuota($zr))
					return false;
			}
			/****************************** QUOTA ******************************/

			$strNewFile = '';
			$bSaved = false;
			$bExternalStorage = false;
			foreach(GetModuleEvents("main", "OnFileCopy", true) as $arEvent)
			{
				if($bSaved = ExecuteModuleEventEx($arEvent, array(&$zr, $newPath)))
				{
					$bExternalStorage = true;
					break;
				}
			}

			$io = \CBXVirtualIo::GetInstance();

			if(!$bExternalStorage)
			{
				$strDirName = $_SERVER["DOCUMENT_ROOT"]."/".(\COption::GetOptionString("main", "upload_dir", "upload"));
				$strDirName = rtrim(str_replace("//","/",$strDirName), "/");

				$zr["SUBDIR"] = trim($zr["SUBDIR"], "/");
				$zr["FILE_NAME"] = ltrim($zr["FILE_NAME"], "/");

				$strOldFile = $strDirName."/".$zr["SUBDIR"]."/".$zr["FILE_NAME"];

				if(strlen($newPath))
					$strNewFile = $strDirName."/".ltrim($newPath, "/");
				else
				{
					$i = 1;
					while(($strNewFile = $strDirName."/".$zr["SUBDIR"]."/".preg_replace('/(\.[^\.]*)$/', '['.$i.']$1', $zr["FILE_NAME"])) && $io->FileExists($strNewFile) && $i<1000)
					{
						$i++;
					}
				}

				$zr["FILE_NAME"] = bx_basename($strNewFile);
				$zr["SUBDIR"] = substr($strNewFile, strlen($strDirName)+1, -(strlen(bx_basename($strNewFile)) + 1));

				if(strlen($newPath))
					CheckDirPath($strNewFile);

				$bSaved = copy($io->GetPhysicalName($strOldFile), $io->GetPhysicalName($strNewFile));
			}

			if($bSaved)
			{
				if($bRegister)
				{
					$arFields = array(
						"TIMESTAMP_X" => $DB->GetNowFunction(),
						"MODULE_ID" => "'".$DB->ForSql($zr["MODULE_ID"], 50)."'",
						"HEIGHT" => intval($zr["HEIGHT"]),
						"WIDTH" => intval($zr["WIDTH"]),
						"FILE_SIZE" => intval($zr["FILE_SIZE"]),
						"ORIGINAL_NAME" => "'".$DB->ForSql($zr["ORIGINAL_NAME"], 255)."'",
						"DESCRIPTION" => "'".$DB->ForSql($zr["DESCRIPTION"], 255)."'",
						"CONTENT_TYPE" => "'".$DB->ForSql($zr["CONTENT_TYPE"], 255)."'",
						"SUBDIR" => "'".$DB->ForSql($zr["SUBDIR"], 255)."'",
						"FILE_NAME" => "'".$DB->ForSql($zr["FILE_NAME"], 255)."'",
						"HANDLER_ID" => $zr["HANDLER_ID"]? intval($zr["HANDLER_ID"]): "null",
						"EXTERNAL_ID" => $zr["EXTERNAL_ID"] != ""? "'".$DB->ForSql($zr["EXTERNAL_ID"], 50)."'": "null",
					);
					$NEW_FILE_ID = $DB->Insert("b_file",$arFields, $err_mess.__LINE__);

					if (\COption::GetOptionInt("main", "disk_space") > 0)
						\CDiskQuota::updateDiskQuota("file", $zr["FILE_SIZE"], "copy");

					\CFile::CleanCache($NEW_FILE_ID);

					return $NEW_FILE_ID;
				}
				else
				{
					if(!$bExternalStorage)
						return substr($strNewFile, strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/")));
					else
						return $bSaved;
				}
			}
			else
			{
				return false;
			}
		}
		return 0;
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
			if(\COption::GetOptionString("main", "translit_original_file_name", "N") == "Y")
			{
				$fileName = \CUtil::translit($fileName, LANGUAGE_ID, array("max_len"=>1024, "safe_chars"=>".", "replace_space" => '-'));
			}

			//replace invalid characters
			if(\COption::GetOptionString("main", "convert_original_file_name", "Y") == "Y")
			{
				$io = \CBXVirtualIo::GetInstance();
				$fileName = $io->RandomizeInvalidFilename($fileName);
			}
		}

		//.jpe is not image type on many systems
		if($bSkipExt == false && strtolower(self::GetFileExtension($fileName)) == "jpe")
		{
			$fileName = substr($fileName, 0, -4).".jpg";
		}

		//double extension vulnerability
		$fileName = RemoveScriptExtension($fileName);

		if(!$originalName)
		{
			//name is md5-generated:
			$fileName = md5(uniqid("", true)).($bSkipExt == true || ($ext = self::GetFileExtension($fileName)) == ''? '' : ".".$ext);
		}

		return $fileName;
	}

	protected static function validateFile($strFileName, $arFile)
	{
		if($strFileName == '')
			return GetMessage("FILE_BAD_FILENAME");

		$io = \CBXVirtualIo::GetInstance();
		if(!$io->ValidateFilenameString($strFileName))
			return GetMessage("MAIN_BAD_FILENAME1");

		if(strlen($strFileName) > 255)
			return GetMessage("MAIN_BAD_FILENAME_LEN");

		//check .htaccess etc.
		if(IsFileUnsafe($strFileName))
			return GetMessage("FILE_BAD_TYPE");

		//nginx returns octet-stream for .jpg
		if(GetFileNameWithoutExtension($strFileName) == '')
			return GetMessage("FILE_BAD_FILENAME");

		if (\COption::GetOptionInt("main", "disk_space") > 0)
		{
			$quota = new \CDiskQuota();
			if (!$quota->checkDiskQuota($arFile))
				return GetMessage("FILE_BAD_QUOTA");
		}

		return "";
	}
	
	public static function GetFilesByExt($path, $arExt=array(), $checkSubdirs=true)
	{
		$arFiles = array();
		$arDirFiles = array_diff(scandir($path), array('.', '..'));
		foreach($arDirFiles as $file)
		{
			if(is_file($path.$file) && (empty($arExt) || preg_match('/\.('.implode('|', $arExt).')$/i', ToLower($file))))
			{
				$arFiles[] = $path.$file;
			}
		}
		if(!empty($arFiles)) return $arFiles;
		if($checkSubdirs===true || $checkSubdirs>0)
		{
			foreach($arDirFiles as $file)
			{
				if(is_dir($path.$file))
				{
					$arFiles = array_merge($arFiles, self::GetFilesByExt($path.$file.'/', $arExt, ($checkSubdirs===true ? $checkSubdirs : $checkSubdirs -1)));
				}
			}
		}
		return $arFiles;
	}
	
	public static function GetFileSystemEncoding()
	{
		if(!isset(static::$fileSystemEncoding))
		{
			$fileSystemEncoding = strtolower(defined("BX_FILE_SYSTEM_ENCODING") ? BX_FILE_SYSTEM_ENCODING : "");

			if (empty($fileSystemEncoding))
			{
				if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
					$fileSystemEncoding =  "windows-1251";
				else
					$fileSystemEncoding = "utf-8";
			}
			static::$fileSystemEncoding = $fileSystemEncoding;
		}
		return static::$fileSystemEncoding;
	}
	
	public static function CorrectEncodingForExtractDir($path)
	{
		$fileSystemEncoding = self::GetFileSystemEncoding();
		$arFiles = array();
		$arDirFiles = array_diff(scandir($path), array('.', '..'));
		foreach($arDirFiles as $file)
		{
			if(preg_match('/[^A-Za-z0-9_\-]/', $file))
			{
				$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($file, $fileSystemEncoding, "cp866");
				$isUtf8 = \CUtil::DetectUTF8($newfile);
				if($isUtf8 && $fileSystemEncoding!='utf-8')
				{
					$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($newfile, 'utf-8', $fileSystemEncoding);
				}
				elseif(!$isUtf8 && $fileSystemEncoding=='utf-8')
				{
					$newfile = \Bitrix\Main\Text\Encoding::convertEncoding($newfile, 'windows-1251', $fileSystemEncoding);
				}
				$res = rename($path.$file, $path.$newfile);
				$file = $newfile;
			}
			if(is_dir($path.$file))
			{
				self::CorrectEncodingForExtractDir($path.$file.'/');
			}
		}
	}
	
	public static function GetDateFormat($m)
	{
		$format = str_replace('_', ' ', $m[1]);
		return ToLower(\CIBlockFormatProperties::DateFormat($format, time()));
	}
	
	public static function MergeCookie(&$arCookies, $arNewCookies)
	{
		if(!is_array($arCookies)) $arCookies = array();
		if(!is_array($arNewCookies)) $arNewCookies = array();
		foreach($arNewCookies as $k=>$v)
		{
			/*if(!isset($arCookies[$k]) || strpos(Tolower($k), 'session')===false)
			{
				$arCookies[$k] = $v;
			}*/
			$arCookies[$k] = $v;
		}
	}
	
	public static function GetNewLocation(&$location, $newLoc)
	{
		$arUrl = parse_url($location);
		$newLoc = trim($newLoc);
		$location = $newLoc;
		if(strlen($newLoc) > 0 && stripos($newLoc, 'http')!==0)
		{
			if(strpos($newLoc, '/')===0)
			{
				$location = $arUrl['scheme'].'://'.$arUrl['host'].$newLoc;
			}
			else
			{
				$location = $arUrl['scheme'].'://'.$arUrl['host'].dirname($arUrl['path']).'/'.$newLoc;
			}
		}
	}
	
	public static function MakeFileArray($path, $maxTime = 0)
	{
		$arExt = array('csv', 'xls', 'xlsx', 'xlsm', 'dbf');
		if(is_array($path))
		{
			$arFile = $path;
			$temp_path = \CFile::GetTempName('', \Bitrix\Main\IO\Path::convertLogicalToPhysical($arFile["name"]));
			CheckDirPath($temp_path);
			if(!copy($arFile["tmp_name"], $temp_path)
				&& !move_uploaded_file($arFile["tmp_name"], $temp_path))
			{
				return false;
			}
			$arFile = \CFile::MakeFileArray($temp_path);
			if(isset($path['type'])) $arFile['type'] = $path['type'];
		}
		else
		{
			$path = trim($path);
			if(strpos($path, '/')===0)
			{
				if(stripos($path, '/etc/')===0 || stripos($path, '/var/log/')===0) return false;
				if(strpos($path, $_SERVER['DOCUMENT_ROOT'])!==0 && !preg_match('/\.('.implode('|', $arExt).')$/i', $path)) return false;
			}
			
			$arCookies = array();
			$arHeaders = array('User-Agent' => 'BitrixSM HttpClient class');
			if(preg_match('/^\{.*\}$/s', $path))
			{
				$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp($path);
				if(isset($arParams['FILELINK']))
				{
					$path = $arParams['FILELINK'];
					
					if(is_array($arParams['VARS']) && $arParams['PAGEAUTH'])
					{
						$redirectCount = 0;
						$location = $arParams['PAGEAUTH'];
						while(strlen($location)>0 && $redirectCount<=5)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
							$client->setCookies($arCookies);
							foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
							$res = $client->get($location);
							$arHeaders['Referer'] = $location;
							self::MergeCookie($arCookies, $client->getCookies()->toArray());
							self::GetNewLocation($location, $client->getHeaders()->get("Location"));
							$status = $client->getStatus();
							if($status != 302 && $status != 303) $location = '';
							$redirectCount++;
						}
						foreach($arParams['VARS'] as $k=>$v)
						{
							if(strlen(trim($v))==0 
								&& preg_match('/<input[^>]*name=[\'"]'.addcslashes($k, '-').'[\'"][^>]*>/Uis', $res, $m1)
								&& preg_match('/value=[\'"]([^\'"]*)[\'"]/Uis', $m1[0], $m2))
							{
									$arParams['VARS'][$k] = $m2[1];
							}
						}
						
						$redirectCount = 0;
						$location = ($arParams['POSTPAGEAUTH'] ? $arParams['POSTPAGEAUTH'] : $arParams['PAGEAUTH']);
						while(strlen($location)>0 && $redirectCount<=5)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
							$client->setCookies($arCookies);
							foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
							$res = $client->post($location, $arParams['VARS']);
							$status = $client->getStatus();
							if($status==404)
							{
								$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'redirect'=>false));
								$client->setCookies($arCookies);
								foreach($arHeaders as $hk=>$hv) $client->setHeader($hk, $hv);
								$res = $client->get($location);
								$status = $client->getStatus();
							}
							$arHeaders['Referer'] = $location;
							self::MergeCookie($arCookies, $client->getCookies()->toArray());
							self::GetNewLocation($location, $client->getHeaders()->get("Location"));
							if($status != 302 && $status != 303) $location = '';
							$redirectCount++;
						}
						
						if(($path==($arParams['POSTPAGEAUTH'] ? $arParams['POSTPAGEAUTH'] : $arParams['PAGEAUTH'])) && preg_match('/<meta[^>]*http\-equiv=[\'"]?refresh[\'"]?[^>]*>/Uis', $res, $m1) && preg_match('/content=[\'"]\d*\s*;\s*url=([^\'"]*)[\'"]/', $m1[0], $m2))
						{
							$path = trim($m2[1]);
						}
					}
					
					if(strlen($arParams['HANDLER_FOR_LINK_BASE64']) > 0) $handler = base64_decode(trim($arParams['HANDLER_FOR_LINK_BASE64']));
					else $handler = trim($arParams['HANDLER_FOR_LINK']);
					if(strlen($handler) > 0)
					{
						$val = '';
						if($path)
						{
							$client = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true));
							$client->setCookies($arCookies);
							$client->setHeader('User-Agent', 'BitrixSM HttpClient class');				
							$val = $client->get($path);
						}
						$res = self::ExecuteFilterExpression($val, $handler, '', $arCookies);
						if(is_array($res))
						{
							if(isset($res['PATH'])) $path = $res['PATH'];
							if(isset($res['COOKIES']) && is_array($res['COOKIES'])) $arCookies = array_merge($arCookies, $res['COOKIES']);
						}
						else
						{
							$path = $res;
						}
					}
				}
			}
			
			$path = preg_replace_callback('/\{DATE_(\S*)\}/', array('\Bitrix\EsolAie\Import\Utils', 'GetDateFormat'), $path);
			if(!$maxTime) $maxTime = min(intval(ini_get('max_execution_time')) - 5, 300);
			if($maxTime<=0) $maxTime = 20;
			$cloud = new \Bitrix\EsolAie\Import\Cloud();
			if($service = $cloud->GetService($path))
			{
				$arFile = $cloud->MakeFileArray($service, $path);
			}
			elseif(($maxTime > 15 || !empty($arCookies)) && preg_match("#^(http[s]?)://#", $path) && class_exists('\Bitrix\Main\Web\HttpClient'))
			{
				$temp_path = '';
				$bExternalStorage = false;
				/*foreach(GetModuleEvents("main", "OnMakeFileArray", true) as $arEvent)
				{
					if(ExecuteModuleEventEx($arEvent, array($path, &$temp_path)))
					{
						$bExternalStorage = true;
						break;
					}
				}*/
				
				if(!$bExternalStorage)
				{
					$urlComponents = parse_url($path);
					if ($urlComponents && strlen($urlComponents["path"]) > 0)
						$temp_path = \CFile::GetTempName('', bx_basename($urlComponents["path"]));
					else
						$temp_path = \CFile::GetTempName('', bx_basename($path));

					$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>$maxTime, 'streamTimeout'=>$maxTime, 'disableSslVerification'=>true));
					$ob->setCookies($arCookies);
					$ob->setHeader('User-Agent', 'BitrixSM HttpClient class');
					$download = false;
					if($ob->download($path, $temp_path) && $ob->getStatus()!=404)
					{
						$download = true;
					}
					elseif($location = $ob->getHeaders()->get('location'))
					{
						if(!\Bitrix\EsolAie\Utils::IsUtfMode() && \CUtil::DetectUTF8($location)) $location = urlencode($GLOBALS['APPLICATION']->ConvertCharset($location, 'UTF-8', 'CP1251'));
						$arUrl = parse_url($location);
						if($arUrl['host'])
						{
							$path = $location;
						}
						else
						{
							if(strpos($location, '/')===0)
							{
								$arUrlPath = parse_url($path);
								$path = $arUrlPath['scheme'].'://'.$arUrlPath['host'].$location;
							}
							else
							{
								//$path = rtrim($path, '/').'/'.$location;
								$path = rtrim(preg_replace('/\/[^\/]*$/', '/', $path), '/').'/'.$location;
							}
						}
						
						$temp_path = \CFile::GetTempName('', bx_basename($path));
						if($ob->download($path, $temp_path))
						{
							$download = true;
						}
					}

					if($download)
					{
						$hcd = $ob->getHeaders()->get('content-disposition');
						$hct = ToLower($ob->getHeaders()->get('content-type'));
						$ext = ToLower(self::GetFileExtension($temp_path));
						if($hcd && stripos($hcd, 'filename=')!==false)
						{
							$hcdParts = array_map('trim', explode(';', $hcd));
							$hcdParts1 = preg_grep('/filename\*=UTF\-8\'\'/i', $hcdParts);
							$hcdParts2 = preg_grep('/filename=/i', $hcdParts);
							if(count($hcdParts1) > 0)
							{
								$hcdParts1 = explode("''", current($hcdParts1));
								$fn = urldecode(trim(end($hcdParts1), '"\' '));
								if(!\Bitrix\EsolAie\Utils::IsUtfMode()) $fn = $GLOBALS['APPLICATION']->ConvertCharset($fn, 'UTF-8', 'CP1251');
								$fn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($fn);
								if(self::IsHtmlFile($temp_path, $fn)) $fn = $fn.'.html';
								if(strpos($temp_path, $fn)===false)
								{
									$temp_path = self::ReplaceFile($temp_path, preg_replace('/\/[^\/]+$/', '/'.$fn, $temp_path));
								}
							}
							elseif(count($hcdParts2) > 0)
							{
								$hcdParts2 = explode('=', current($hcdParts2));
								$fn = trim(end($hcdParts2), '"\' ');
								if(self::IsHtmlFile($temp_path, $fn)) $fn = $fn.'.html';
								if(strpos($temp_path, $fn)===false)
								{
									$temp_path = self::ReplaceFile($temp_path, preg_replace('/\/[^\/]+$/', '/'.$fn, $temp_path));
								}
							}
						}
						elseif(ToLower(substr($temp_path, -4))=='.php' && strpos(ToLower($path), 'csv')!==false)
						{
							$temp_path = self::ReplaceFile($temp_path, substr($temp_path, 0, -4).'.csv');
						}
						elseif((!$ext || $ext=='php' || $ext=='htm' || $ext=='html') && $hct && strpos($hct, 'text/html')!==false && strpos(ToLower($path), 'htm')!==false)
						{
							$siteEncoding = $fileEncoding = self::getSiteEncoding();
							if(preg_match('/charset=(.+)(;|$)/Uis', $hct, $m)) $fileEncoding = ToLower(trim($m[1]));
							$temp_path_new = self::GetNewFile(preg_replace('/\.[^\/\.]*$/Uis', '', $temp_path).'.html');
							$handle = fopen($temp_path, 'r');
							$handle2 = fopen($temp_path_new, 'a');
							$i = 0;
							while(!feof($handle))
							{
								$buffer2 = fread($handle, 1024*1024);
								$buffer2 = preg_replace('/(<\/tr>)\s*(<td)/Uis', '$1<tr>$2', $buffer2);
								if($siteEncoding!=$fileEncoding)
								{
									if($i==0)
									{
										$buffer2 = preg_replace('/(<meta[^>]*charset=)([^\s"\';]*)([^>]*>)/is', '$1'.$siteEncoding.'$3', $buffer2);
									}
									$buffer2 = \Bitrix\Main\Text\Encoding::convertEncoding($buffer2, $fileEncoding, $siteEncoding);
								}
								fwrite($handle2, $buffer2);
								$i++;
							}
							self::RemoveOldFile($temp_path);
							$temp_path = $temp_path_new;
						}
						elseif((!$ext || $ext=='php') && $hct && (strpos($hct, 'text/csv')!==false || strpos($hct, 'text/plain')!==false))
						{
							$temp_path = self::ReplaceFile($temp_path, $temp_path.'.csv');
						}
						elseif((!$ext || $ext=='php') && $hct && in_array($hct, array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')))
						{
							$temp_path = self::ReplaceFile($temp_path, $temp_path.'.xlsx');
						}
						elseif((!$ext || $ext=='php') && $hct && strpos($hct, 'text/html')!==false)
						{
							$content = file_get_contents($temp_path, false, null, 0, 65536);
							if(preg_match('/<meta[^>]*http\-equiv=[\'"]?refresh[\'"]?[^>]*>/Uis', $content, $m1) && preg_match('/content=[\'"]\d*\s*;\s*url=([^\'"]*)[\'"]/', $m1[0], $m2))
							{
								return self::MakeFileArray(trim($m2[1]), $maxTime);
							}
						}
						$arFile = \CFile::MakeFileArray($temp_path);
					}
				}
				elseif($temp_path)
				{
					$arFile = \CFile::MakeFileArray($temp_path);
				}
				
				if(strlen($arFile["type"])<=0)
					$arFile["type"] = "unknown";
			}
			elseif(preg_match('/ftp(s)?:\/\//', $path))
			{
				$sftp = new \Bitrix\EsolAie\Sftp();
				$arFile = $sftp->MakeFileArray($path, $maxTime);
			}
			else
			{
				$arFile = \CFile::MakeFileArray($path);
			}
		}
		
		$ext = ToLower(self::GetFileExtension($arFile['tmp_name']));
		if(in_array($arFile['type'], array('application/zip', 'application/x-zip-compressed', 'application/gzip', 'application/x-gzip', 'application/rar', 'application/x-rar', 'application/x-rar-compressed', 'application/octet-stream')) && !in_array($ext, $arExt))
		{
			$tmpsubdir = dirname($arFile['tmp_name']).'/zip/';
			CheckDirPath($tmpsubdir);	
			if(substr($ext, -3)=='.gz' && $ext!='tar.gz' && function_exists('gzopen'))
			{
				$handle1 = gzopen($arFile['tmp_name'], 'rb');
				$handle2 = fopen($tmpsubdir.substr(basename($arFile['tmp_name']), 0, -3), 'wb');
				while(!gzeof($handle1)) {
					fwrite($handle2, gzread($handle1, 4096));
				}
				fclose($handle2);
				gzclose($handle1);
			}
			elseif($ext=='rar' && class_exists('\RarArchive'))
			{
				$rar = \RarArchive::open($arFile['tmp_name']);
				$entries = $rar->getEntries();
				foreach($entries as $entry)
				{
					$entry->extract($tmpsubdir);
				}
				$rar->close();
			}
			else
			{
				$type = (in_array($ext, array('tar.gz', 'tgz')) ? 'TAR.GZ' : 'ZIP');
				$zipObj = \CBXArchive::GetArchive($arFile['tmp_name'], $type);
				$zipObj->Unpack($tmpsubdir);
			}
			if($arFile['type']=='application/zip' && isset($service) && $service=='yadisk') self::CorrectEncodingForExtractDir($tmpsubdir);
			$arFile = array();
			if(!is_array($path)) $urlComponents = parse_url($path);
			else $urlComponents = array();
			if(isset($urlComponents['fragment']) && strlen($urlComponents['fragment']) > 0)
			{
				$fn = $tmpsubdir.ltrim($urlComponents['fragment'], '/');
				$arFiles = array($fn);
				if((strpos($fn, '*')!==false || (strpos($fn, '{')!==false && strpos($fn, '}')!==false)) && !file_exists($fn))
				{
					$arFiles = glob($fn, GLOB_BRACE);
				}
			}
			else
			{
				$arFiles = self::GetFilesByExt($tmpsubdir, $arExt);
			}
			if(count($arFiles) > 0)
			{
				$tmpfile = current($arFiles);
				$temp_path = \CFile::GetTempName('', bx_basename($tmpfile));
				$dir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
				\Bitrix\Main\IO\Directory::createDirectory($dir);
				copy($tmpfile, $temp_path);
				$arFile = \CFile::MakeFileArray($temp_path);
			}
			DeleteDirFilesEx(substr($tmpsubdir, strlen($_SERVER['DOCUMENT_ROOT'])));
		}
		
		self::CheckHtmlFile($arFile, $path);
		return $arFile;
	}
	
	public static function IsHtmlFile($temp_path, $fn)
	{
		return (bool)(ToLower(self::GetFileExtension($fn))=='xls' && strpos(ToLower(file_get_contents($temp_path, false, null, 0, 1024)), '<html')!==false);
	}
	
	public static function GetNewFile($newName)
	{
		$temp_path = \CFile::GetTempName('', bx_basename($newName));
		$temp_dir = \Bitrix\Main\IO\Path::getDirectory($temp_path);
		\Bitrix\Main\IO\Directory::createDirectory($temp_dir);
		return $temp_path;
	}
	
	public static function RemoveOldFile($old_temp_path)
	{
		unlink($old_temp_path);
		$dir = dirname($old_temp_path);
		if(count(array_diff(scandir($dir), array('.', '..')))==0)
		{
			rmdir($dir);
		}
	}
	
	public static function ReplaceFile($old_temp_path, $newName)
	{
		$temp_path = self::GetNewFile($newName);
		copy($old_temp_path, $temp_path);
		self::RemoveOldFile($old_temp_path);
		return $temp_path;
	}
	
	
	public static function CheckHtmlFile(&$arFile, $path)
	{
		if(is_array($path)) $path = '';
		$ext = ToLower(self::GetFileExtension($arFile['tmp_name']));
		if(in_array($ext, array('htm', 'html')) && class_exists('\DOMDocument'))
		{
			/*Bom UTF-8*/
			$content = file_get_contents($arFile['tmp_name']);
			if(\CUtil::DetectUTF8(substr($content, 0, 10000)) && (substr($content, 0, 3)!="\xEF\xBB\xBF"))
			{
				file_put_contents($arFile['tmp_name'], "\xEF\xBB\xBF".$content);
			}
			/*/Bom UTF-8*/
			
			$doc = new \DOMDocument();
			$doc->preserveWhiteSpace = false;
			$doc->formatOutput = true;
			$doc->loadHTMLFile($arFile['tmp_name']);
			$tbl = $doc->getElementsByTagName('table');
			if($tbl->length > 0)
			{
				$withTags = false;
				$arParams = array();
				$arUrl = parse_url($path);
				if($arUrl['fragment'])
				{
					$arFragments = explode('&', $arUrl['fragment']);
					foreach($arFragments as $fragment)
					{
						$arVar = explode('=', $fragment, 2);
						if(count($arVar)==2)
						{
							$arParams[$arVar[0]] = $arVar[1];
						}
						elseif($fragment=='withtags')
						{
							$withTags = true;
						}
					}
				}
				$find = false;
				if(!empty($arParams))
				{
					$key = 0;
					while(!$find && $key<$tbl->length)
					{
						$tbl1 = $tbl->item($key);
						$subfind = true;
						foreach($arParams as $k=>$v)
						{
							if($tbl1->getAttribute($k)!=$v)
							{
								$subfind = false;
							}
						}
						$find = $subfind;
						if(!$find) $key++;
					}
				}
				if($find) $tbl = $tbl->item($key);
				else $tbl = $tbl->item(0);

				require_once(dirname(__FILE__).'/../../lib/PHPExcel/PHPExcel.php');
				$objKDAPHPExcel = new \KDAPHPExcel();
				$worksheet = $objKDAPHPExcel->getActiveSheet();
				$arCols = range('A', 'Z');
				foreach(range('A', 'Z') as $v1)
				{
					foreach(range('A', 'Z') as $v2)
					{
						$arCols[] = $v1.$v2;
					}
				}
				$row = 1;
				
				foreach($tbl->childNodes as $node1)
				{
					if($node1->nodeName=='tr')
					{
						$col = 0;
						foreach($node1->childNodes as $node2)
						{
							if($node2->nodeName=='td')
							{
								$innerHTML = $node2->nodeValue;
								//value with tags
								if($withTags)
								{
									$innerHTML = '';
									$children = $node2->childNodes;
									foreach($children as $child)
									{
										$innerHTML .= $child->ownerDocument->saveXML($child);
									}
								}
								$worksheet->setCellValueExplicit($arCols[$col++].$row, self::GetCellValueCsv($innerHTML));
							}
						}
						$row++;
					}
				}
				
				$writerType = 'CSV';
				$objWriter = \KDAPHPExcel_IOFactory::createWriter($objKDAPHPExcel, $writerType);
				$objWriter->setDelimiter(';');
				$objWriter->setUseBOM(true);
				
				$arFile['tmp_name'] = $arFile['tmp_name'].'.csv';
				$arFile['name'] = $arFile['name'].'.csv';
				$objWriter->save($arFile['tmp_name']);
			}
		}
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
	
	public static function GetShowFileBySettings($SETTINGS_DEFAULT)
	{
		$path = $link = '';
		if($SETTINGS_DEFAULT["EXT_DATA_FILE"])
		{
			if(preg_match('/^\{.*\}$/s', $SETTINGS_DEFAULT["EXT_DATA_FILE"]))
			{
				$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp($SETTINGS_DEFAULT["EXT_DATA_FILE"]);
				if(isset($arParams['FILELINK']))
				{
					$path = $arParams['FILELINK'];
				}
			}
			else
			{
				$path = $SETTINGS_DEFAULT["EXT_DATA_FILE"];
			}
			if($path) $link = $path;
		}
		elseif($SETTINGS_DEFAULT["EMAIL_DATA_FILE"])
		{
			$json = $SETTINGS_DEFAULT["EMAIL_DATA_FILE"];
			if(strlen($json) > 0 && strpos($json, '{')===false) $json = base64_decode($json);
			$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp($json);
			if(isset($arParams['EMAIL']))
			{
				$path = $arParams['EMAIL'];
			}
			if($SETTINGS_DEFAULT["URL_DATA_FILE"] && ($basename = bx_basename($SETTINGS_DEFAULT["URL_DATA_FILE"])))
			{
				$path = $basename.' <'.$path.'>';
			}
		}
		return array('link'=>$link, 'path'=>$path);
	}
	
	public static function GetCellValueCsv($val)
	{
		if(!\Bitrix\EsolAie\Utils::IsUtfMode() && !\CUtil::DetectUTF8($val))
		{
			$val = $GLOBALS['APPLICATION']->ConvertCharset($val, 'CP1251', 'UTF-8');
		}
		return $val;
	}
	
	public static function AddFileInputActions()
	{
		//AddEventHandler("main", "OnEndBufferContent", Array("\Bitrix\EsolAie\Import\Utils", "AddFileInputActionsHandler"));
	}
	
	public static function AddFileInputActionsHandler(&$content)
	{
		return;
		//if(!function_exists('imap_open')) return;
		
		$comment = 'KDA_IE_CHOOSE_FILE';
		$commentBegin = '<!--'.$comment.'-->';
		$commentEnd = '<!--/'.$comment.'-->';
		$pos1 = strpos($content, $commentBegin);
		$pos2 = strpos($content, $commentEnd);
		if($pos1!==false && $pos2!==false)
		{
			$partContent = substr($content, $pos1, $pos2 + strlen($commentEnd) - $pos1);
			if(preg_match_all('/<script[^>]*>.*<\/script>/Uis', $partContent, $m))
			{
				$arScripts = preg_grep('/BX\.file_input\((\{.*\'bx_file_data_file\'.*\})\)[;<]/Uis', $m[0]);
				while(count($arScripts) > 1)
				{
					$script = array_pop($arScripts);
					if($pos = strrpos($partContent, $script))
					{
						$newPartContent = substr($partContent, 0, $pos).substr($partContent, $pos+strlen($script));
						$content = str_replace($partContent, $newPartContent, $content);
						$partContent = $newPartContent;
					}
				}
			}
			if(preg_match('/BX\.file_input\((\{.*\})\)\s*[:;<]/Us', $partContent, $m))
			{
				$json = $m[1];
				$arConfig = \Bitrix\EsolAie\Utils::JsObjectToPhp($json);
				array_walk_recursive($arConfig, array(__CLASS__, 'String2Boolean'));
				$arConfigEmail = array(
					'TEXT' => GetMessage("KDA_IE_FILE_SOURCE_EMAIL"),
					'GLOBAL_ICON' => 'adm-menu-upload-email',
					'ONCLICK' => 'EProfile.ShowEmailForm();'
				);
				$arConfig['menuNew'][] = $arConfigEmail;
				$arConfig['menuExist'][] = $arConfigEmail;
				$arConfigLinkAuth = array(
					'TEXT' => GetMessage("KDA_IE_FILE_SOURCE_LINKAUTH"),
					'GLOBAL_ICON' => 'adm-menu-upload-linkauth',
					'ONCLICK' => 'EProfile.ShowFileAuthForm();'
				);
				$arConfig['menuNew'][] = $arConfigLinkAuth;
				$arConfig['menuExist'][] = $arConfigLinkAuth;
				$newJson = \Bitrix\EsolAie\Utils::PHPToJSObject($arConfig);
				$newPartContent = str_replace($json, $newJson, $partContent);
				$content = str_replace($partContent, $newPartContent, $content);
			}
		}
	}
	
	public static function GetColLetterByIndex($index)
	{
		if(empty(static::$colLetters))
		{
			$arLetters = range('A', 'Z');
			foreach(range('A', 'Z') as $v1)
			{
				foreach(range('A', 'Z') as $v2)
				{
					$arLetters[] = $v1.$v2;
				}
			}
			foreach(range('A', 'Z') as $v1)
			{
				foreach(range('A', 'Z') as $v2)
				{
					foreach(range('A', 'Z') as $v3)
					{
						$arLetters[] = $v1.$v2.$v3;
					}
				}
			}
			static::$colLetters = $arLetters;
		}
		return static::$colLetters[$index];
	}
	
	public static function ExecuteFilterExpression($val, $expression, $altReturn = true, $arCookies=array())
	{
		$expression = trim($expression);
		try{				
			if(stripos($expression, 'return')===0)
			{
				$command = $expression.';';
				return eval($command);
			}
			elseif(preg_match('/\$val\s*=/', $expression))
			{
				$command = $expression.';';
				eval($command);
				return $val;
			}
			else
			{
				$command = 'return '.$expression.';';
				return eval($command);
			}
		}catch(\Exception $ex){
			return $altReturn;
		}
	}
	
	public static function PrepareJs()
	{
		$curFilename = end(explode('/', $_SERVER['SCRIPT_NAME']));
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/'.static::$moduleId.'/install/admin/'.$curFilename))
		{
			foreach(GetModuleEvents("main", "OnEndBufferContent", true) as $eventKey=>$arEvent)
			{
				if(!isset($arEvent['TO_MODULE_ID']) || $arEvent['TO_MODULE_ID']=='security')
				{
					RemoveEventHandler($arEvent['FROM_MODULE_ID'], $arEvent['MESSAGE_ID'], $eventKey);
				}
			}
			AddEventHandler("main", "OnEndBufferContent", Array("\Bitrix\EsolAie\Import\Utils", "PrepareJsDirect"));
		}
	}
	
	public static function PrepareJsDirect(&$content)
	{
		static::$jsCounter = 0;
		$content = preg_replace_callback('/<script[^>]+src="[^"]*\/js\/main\/jquery\/jquery\-[\d\.]+(\.min)+\.js[^"]*"[^>]*>\s*<\/script>/Uis', Array("\Bitrix\EsolAie\Import\Utils", "DeleteExcellJs"), $content);
	}
	
	public static function DeleteExcellJs($m)
	{
		if(static::$jsCounter++==0) return $m[0];
		else return '';
	}
	
	public static function RemoveTmpFiles($maxTime = 5)
	{
		$oProfile = \Bitrix\EsolAie\Import\Profile::getInstance();
		$timeBegin = time();
		$docRoot = $_SERVER["DOCUMENT_ROOT"];
		$tmpDir = $docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		$arOldDirs = array();
		$arActDirs = array('_archives');
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
					$arParams = $oProfile->GetProfileParamsByFile($tmpDir.$file);
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
		
		$tmpDir = $docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir.'_archives/';
		if(file_exists($tmpDir) && ($dh = opendir($tmpDir))) 
		{
			while(($file = readdir($dh)) !== false) 
			{
				if(in_array($file, array('.', '..'))) continue;
				if(is_dir($tmpDir.$file))
				{
					if((time() - filemtime($tmpDir.$file) > 2*24*60*60))
					{
						$arOldDirs[] = $file;
					}
				}
			}
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
	
	public static function GetIniAbsVal($param)
	{
		$val = ToUpper(ini_get($param));
		if(substr($val, -1)=='K') $val = (float)$val*1024;
		elseif(substr($val, -1)=='M') $val = (float)$val*1024*1024;
		elseif(substr($val, -1)=='G') $val = (float)$val*1024*1024*1024;
		else $val = (float)$val;
		return $val;
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
	
	public static function GetUserAgent()
	{
		if(empty(self::$arAgents))
		{
			self::$arAgents = array(
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:62.0) Gecko/20100101 Firefox/62.0',
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0',
				'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0',
				'Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0',
				'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:46.0) Gecko/20100101 Firefox/46.0',
			);
			self::$countAgents = count(self::$arAgents);
		}
		return self::$arAgents[rand(0, self::$countAgents - 1)];
	}
	
	public static function WordWithNum($num, $word)
	{
		list($word1, $word2, $word3) = array_map('trim', explode(',', $word));
		if($num%10==0 || $num%10>4 || ($num%100>10 && $num%100<20)) return $word3;
		elseif($num%10==1) return $word1;
		else return $word2;
	}
	
	public static function String2Boolean(&$n, $k)
	{
		if($n=="true"){$n=true;}elseif($n=="false"){$n=false;}
	}
	
	public static function GetBoolValue($val, $numReturn = false, $defaultValue = false)
	{
		$trueVals = array_map('trim', explode(',', Loc::getMessage("ESOL_AE_FIELD_VAL_Y")));
		$falseVals = array_map('trim', explode(',', Loc::getMessage("ESOL_AE_FIELD_VAL_N")));
		if(in_array(ToLower($val), $trueVals))
		{
			return ($numReturn ? 1 : 'Y');
		}
		elseif(in_array(ToLower($val), $falseVals))
		{
			return ($numReturn ? 0 : 'N');
		}
		else
		{
			return $defaultValue;
		}
	}
}
?>