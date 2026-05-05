<?php
namespace Bitrix\EsolAie\Export;
require_once(dirname(__FILE__).'/../PHPExcel/PHPExcel.php');
use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Exporter {
	protected static $moduleId = 'esol.allimportexport';
	protected static $moduleSubDir = 'export/';
	protected static $instances = array();
	private $fl = null;
	private $pid = false;
	private $filesForMove = array();
	private $arProfileFields = array();
	
	public static function getInstance($entity='false', $p1=array(), $p2=array(), $fparams=array(), $stepparams=false, $pid = false)
	{
		$hash = $entity;
		if($pid!==false) $hash = $hash.'_'.md5(serialize(array($p1, $p2, $fparams, $stepparams, $pid)));
		if (!isset(static::$instances[$hash]))
			static::$instances[$hash] = new static($entity, $p1, $p2, $fparams, $stepparams, $pid);

		return static::$instances[$hash];
	}
	
	public function __construct($entity, $p1=array(), $p2=array(), $fparams=array(), $stepparams=false, $pid = false)
	{
		$this->fl = FieldList::getInstance($entity);
		$this->profile = Profile::getInstance($entity);
		
		if($pid!==false)
		{
			$this->setParams(array_merge($p1, $p2), $fparams, $stepparams, $pid, $p2);
		}
	}
	
	public function setParams($params=array(), $fparams=array(), $stepparams=false, $pid = false, $s=array())
	{
		$this->params = $params;
		$this->fparams = $fparams;
		$this->maxReadRows = 100;
		$this->maxReadRowsWOffers = 20;
		$this->errors = array();
		$this->stepparams = array();
		$this->docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
		$this->pid = $pid;
		
		if($pid!==false && strlen($pid) > 0 && !empty($s))
		{
			$this->arProfileFields = $s['FIELDS_LIST'];
		}
		else
		{
			$sd = $s = null;
			$oProfile = \Bitrix\EsolAie\Export\Profile::getInstance();
			$oProfile->Apply($sd, $s, $pid);
			$this->arProfileFields = $s['FIELDS_LIST'];
		}
		
		$this->fparamsByName = array();
		if(is_array($this->params['FIELDS_LIST']))
		{
			foreach($this->params['FIELDS_LIST'] as $listIndex=>$arFields)
			{
				foreach($arFields as $key=>$field)
				{
					$this->fparamsByName[$listIndex][$field] = $this->fparams[$listIndex][$key];
				}
			}
		}
		if(strlen($this->params['ELEMENT_MULTIPLE_SEPARATOR']))
		{
			$this->params['ELEMENT_MULTIPLE_SEPARATOR'] = strtr($this->params['ELEMENT_MULTIPLE_SEPARATOR'], array('\r'=>"\r", '\n'=>"\n", '\t'=>"\t"));
		}

		if(is_array($stepparams))
		{
			$this->stepparams = $stepparams;
			$this->stepparams['list_number'] = (strlen($this->stepparams['list_number']) > 0 ? intval($this->stepparams['list_number']) : '');
			$this->stepparams['list_current_page'] = intval($this->stepparams['list_current_page']);
			$this->stepparams['list_last_page'] = intval($this->stepparams['list_last_page']);
			$this->stepparams['total_read_line'] = intval($this->stepparams['total_read_line']);
			$this->stepparams['total_file_line'] = intval($this->stepparams['total_file_line']);
			$this->stepparams['image_cnt'] = intval($this->stepparams['image_cnt']);
			if(!isset($this->stepparams['string_lengths']) && $this->params['FILE_EXTENSION']=='dbf') $this->stepparams['string_lengths'] = array();
			
			if(!isset($this->params['MAX_EXECUTION_TIME']) || $this->params['MAX_EXECUTION_TIME']!==0)
			{
				if(Option::get(static::$moduleId, 'SET_MAX_EXECUTION_TIME')=='Y' && is_numeric(Option::get(static::$moduleId, 'MAX_EXECUTION_TIME')))
				{
					$this->params['MAX_EXECUTION_TIME'] = intval(Option::get(static::$moduleId, 'MAX_EXECUTION_TIME'));
					if(ini_get('max_execution_time') && $this->params['MAX_EXECUTION_TIME'] > ini_get('max_execution_time') - 5) $this->params['MAX_EXECUTION_TIME'] = ini_get('max_execution_time') - 5;
					if($this->params['MAX_EXECUTION_TIME'] < 5) $this->params['MAX_EXECUTION_TIME'] = 5;
					if($this->params['MAX_EXECUTION_TIME'] > 300) $this->params['MAX_EXECUTION_TIME'] = 300;
				}
				else
				{
					/*$this->params['MAX_EXECUTION_TIME'] = intval(ini_get('max_execution_time')) - 10;
					if($this->params['MAX_EXECUTION_TIME'] < 10) $this->params['MAX_EXECUTION_TIME'] = 10;
					if($this->params['MAX_EXECUTION_TIME'] > 50) $this->params['MAX_EXECUTION_TIME'] = 30;*/
					$this->params['MAX_EXECUTION_TIME'] = 10;
				}
			}
			
			/*Temp folders*/
			$dir = $this->SetTmpFolders($pid);
			
			$this->tmpfile = $this->tmpdir.'params.txt';
			$this->profile->SetExportParams($pid);
			/*/Temp folders*/
			
			if(file_exists($this->tmpfile))
			{
				$this->stepparams = array_merge($this->stepparams, \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($this->tmpfile)));
			}
			
			if(!isset($this->stepparams['curstep'])) $this->stepparams['curstep'] = 'export';
		
			if($pid!==false)
			{
				$this->procfile = $dir.$pid.'.txt';
				if((int)$this->stepparams['export_started'] < 1)
				{
					$this->profile->OnStartExport();
				
					if(file_exists($this->procfile)) unlink($this->procfile);
					if($this->params['EXPORT_FILES_IN_ARCHIVE']=='Y' && strlen($this->params['FILES_ARCHIVE_PATH']) > 0)
					{
						$archivePath = $this->docRoot. preg_replace('/\.zip\s*$/U', '', '/'.ltrim($this->params['FILES_ARCHIVE_PATH'], '/'));
						for($suffix=0; $suffix<501; $suffix++)
						{
							$zipFile = $archivePath.($suffix > 0 ? '_'.$suffix : '').'.zip';
							if(file_exists($zipFile)) unlink($zipFile);
						}
					}
				}
			}
		}
		elseif($pid!==false)
		{
			$this->SetTmpFolders($pid, '_preview');
		}
	}
	
	public function SetTmpFolders($pid, $suffix='')
	{
		$dir = $this->docRoot.'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		CheckDirPath($dir);
		if(!isset($this->stepparams) || !is_array($this->stepparams)) $this->stepparams = array();
		if(!$this->stepparams['tmpdir'])
		{
			if($pid!==false)
			{
				$tmpdir = $dir.'p'.$pid.$suffix.'/';
				if(file_exists($tmpdir))
				{
					DeleteDirFilesEx(substr($tmpdir, strlen($this->docRoot)));
				}
			}
			else
			{
				$i = 0;
				while(($tmpdir = $dir.$i.'/') && file_exists($tmpdir)){$i++;}
			}
			$this->stepparams['tmpdir'] = $tmpdir;
			CheckDirPath($tmpdir);
		}
		$this->tmpdir = $this->stepparams['tmpdir'];
		$this->imagedir = $this->stepparams['tmpdir'].'images/';
		CheckDirPath($this->imagedir);
		return $dir;
	}
	
	public function GetPublicImagePath()
	{
		return substr($this->imagedir, strlen($this->docRoot));
	}
	
	public function GetProfileId()
	{
		return $this->pid;
	}
	
	public function CheckTimeEnding()
	{
		return ($this->params['MAX_EXECUTION_TIME'] && (time()-$this->timeBegin >= $this->params['MAX_EXECUTION_TIME']));
	}
	
	public function OpenTmpdataHandler($listIndex, $mode = 'a')
	{
		$this->CloseTmpdataHandler();
		$this->tmpdatafile = $this->tmpdir.'data_'.$listIndex.'.txt';
		$this->tmpdatafilehandler = fopen($this->tmpdatafile, $mode);
	}
	
	public function CloseTmpdataHandler()
	{
		if($this->tmpdatafilehandler)
		{
			fclose($this->tmpdatafilehandler);
		}
		$this->tmpdatafilehandler = false;
	}
	
	public function WriteTmpdata($arElement)
	{
		fwrite($this->tmpdatafilehandler, base64_encode(serialize($arElement))."\r\n");
	}
	
	public function Export()
	{
		$this->stepparams['export_started'] = 1;
		$this->SaveStatusImport();
		$this->timeBegin = time();
		
		$arListIndexes = array(0);
		if(is_array($this->params['LIST_NAME']) && count($this->params['LIST_NAME']) > 0)
		{
			$arListIndexes = array_keys($this->params['LIST_NAME']);
		}

		//$listIndex = 0;
		$listIndex = $this->stepparams['list_number'];
		if(!in_array($listIndex, $arListIndexes, true)) $listIndex = (int)current($arListIndexes);
		//$maxListIndex = max($arListIndexes);
		$lastListIndex = end($arListIndexes);
		
		$page = max(1, $this->stepparams['list_current_page']);
		$lastPage = $this->stepparams['list_last_page'];
		$arFields = $this->GetFieldList($listIndex);
		
		$break = ($lastPage > 0 && $page > $lastPage && ($listIndex == $lastListIndex));
		if(!$break) $this->OpenTmpdataHandler($listIndex);
		while(!$break)
		{
			$arRes = $this->GetExportData($listIndex, $this->maxReadRows, $page);
			$arData = $arRes['DATA'];
			$lastPage = $arRes['PAGE_COUNT'];
			$recordCount = $arRes['RECORD_COUNT'];
			
			foreach($arData as $arElement)
			{
				$this->WriteTmpdata($arElement);
				$this->stepparams['total_read_line']++;
				if(!isset($this->stepparams['rows'][$listIndex])) $this->stepparams['rows'][$listIndex] = 0;
				$this->stepparams['rows'][$listIndex]++;
				if(!isset($this->stepparams['rows2'][$listIndex])) $this->stepparams['rows2'][$listIndex] = 0;
				$this->stepparams['rows2'][$listIndex] += ((isset($arElement['ROWS_COUNT']) && (int)$arElement['ROWS_COUNT'] > 0) ? (int)$arElement['ROWS_COUNT'] : 1);
			}
			
			$page++;
			$break = (bool)(($lastPage > 0 && $page > $lastPage) || empty($arData));
			if($break)
			{
				$break = ($break /*&& ($listIndex >= $maxListIndex)*/ && ($listIndex == $lastListIndex));
				if(!$break)
				{
					$lastPage = $page = 1;
					reset($arListIndexes);
					$next = current($arListIndexes);
					while($next!=$listIndex && (($next = next($arListIndexes)) || $next!==false)){}
					$next = next($arListIndexes);
					$listIndex = $next;
					$this->OpenTmpdataHandler($listIndex);
				}
			}
			
			if($page > $lastPage)
			{
				//this line leads to an infinite loop
				//$page = 1;
			}
			
			$this->stepparams['list_number'] = $listIndex;
			$this->stepparams['list_current_page'] = $page;
			$this->stepparams['list_last_page'] = $lastPage;
			$this->stepparams['total_file_line'] = $recordCount;
			$this->SaveStatusImport();
			if($this->CheckTimeEnding())
			{
				return $this->GetBreakParams();
			}
		}
		
		$this->CloseTmpdataHandler();
		
		Utils::PrepareTextRows($this->params['TEXT_ROWS_TOP'], $this->params, $this->stepparams);
		Utils::PrepareTextRows($this->params['TEXT_ROWS_TOP2'], $this->params, $this->stepparams);

		$filePath = Utils::PrepareExportFileName($this->params['FILE_PATH'], $this->params);
		$outputFile = $this->docRoot.$filePath;
		$dir = dirname($filePath);
		if(strlen($dir) > 1 && $dir!='/upload' && file_exists($dir) && is_writable($dir))
		{
			$outputFile = $filePath;
		}
		else
		{
			CheckDirPath(dirname($outputFile).'/');
		}
		
		$arWriterParams = array(
			'OUTPUTFILE' => $outputFile,
			'TMPDIR' => $this->tmpdir,
			'IMAGEDIR' => $this->imagedir,
			'LIST_INDEXES' => $arListIndexes,
			'ROWS' => $this->stepparams['rows'],
			'STRING_LENGTHS' => $this->stepparams['string_lengths'],
			'EXTRAPARAMS' => $this->fparams,
			'PARAMS' => $this->params,
			'LISTINDEX' => $listIndex
		);
		if($this->params['FILE_EXTENSION']=='xlsx')
		{
			$objWriter = false;
			if(isset($this->stepparams['WRITER_FILE_PARAMS']) && file_exists($this->stepparams['WRITER_FILE_PARAMS']))
			{
				$objWriter = \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($this->stepparams['WRITER_FILE_PARAMS']), 'Bitrix\EsolAie\Export\WriterXlsx');
				if(is_callable(array($objWriter, 'SetEObject')))
				{
					$objWriter->SetEObject($this);
				}
			}
			if(!is_object($objWriter))
			{
				$objWriter = new WriterXlsx($arWriterParams, $this);
			}
			if(false===$objWriter->Save()/* && $this->CheckTimeEnding()*/)
			{
				$writerFileParams = $this->tmpdir.'writer_params.txt';
				file_put_contents($writerFileParams, serialize($objWriter));
				$this->stepparams['WRITER_FILE_PARAMS'] = $writerFileParams;
				return $this->GetBreakParams();
			}
		}
		elseif($this->params['FILE_EXTENSION']=='csv')
		{
			$objWriter = false;
			if(isset($this->stepparams['WRITER_FILE_PARAMS']) && file_exists($this->stepparams['WRITER_FILE_PARAMS']))
			{
				$objWriter = \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($this->stepparams['WRITER_FILE_PARAMS']), 'Bitrix\EsolAie\Export\WriterCsv');
				if(is_callable(array($objWriter, 'SetEObject')))
				{
					$objWriter->SetEObject($this);
				}
			}
			if(!is_object($objWriter))
			{
				$objWriter = new WriterCsv($arWriterParams, $this);
			}
			if(false===$objWriter->Save()/* && $this->CheckTimeEnding()*/)
			{
				$writerFileParams = $this->tmpdir.'writer_params.txt';
				file_put_contents($writerFileParams, serialize($objWriter));
				$this->stepparams['WRITER_FILE_PARAMS'] = $writerFileParams;
				return $this->GetBreakParams();
			}
		}
		elseif($this->params['FILE_EXTENSION']=='dbf')
		{
			$dir = dirname(__FILE__).'/../../lib/PHPExcel/PHPExcel/Reader/XBase/';
			require_once($dir.'Table.php');
			require_once($dir.'WritableTable.php');
			require_once($dir.'Column.php');
			require_once($dir.'Record.php');
			require_once($dir.'Memo.php');
		
			$objWriter = false;
			if(isset($this->stepparams['WRITER_FILE_PARAMS']) && file_exists($this->stepparams['WRITER_FILE_PARAMS']))
			{
				$objWriter = \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($this->stepparams['WRITER_FILE_PARAMS']), 'Bitrix\EsolAie\Export\WriterDbf');
				if(is_callable(array($objWriter, 'SetEObject')))
				{
					$objWriter->SetEObject($this);
				}
			}
			if(!is_object($objWriter))
			{
				$objWriter = new WriterDbf($arWriterParams, $this);
			}
			if(false===$objWriter->Save()/* && $this->CheckTimeEnding()*/)
			{
				$writerFileParams = $this->tmpdir.'writer_params.txt';
				file_put_contents($writerFileParams, serialize($objWriter));
				$this->stepparams['WRITER_FILE_PARAMS'] = $writerFileParams;
				return $this->GetBreakParams();
			}
		}
		else
		{
			$writerType = 'CSV';
			if($this->params['FILE_EXTENSION']=='xlsx') $writerType = 'Excel2007';
			elseif($this->params['FILE_EXTENSION']=='xls') $writerType = 'Excel5';
			
			$objPHPExcel = new \KDAPHPExcel();
			$arCols = range('A', 'Z');
			foreach(range('A', 'Z') as $v1)
			{
				foreach(range('A', 'Z') as $v2)
				{
					$arCols[] = $v1.$v2;
				}
			}
			
			$row = 1;
			foreach($arListIndexes as $listIndex)
			{
				$arFields = $this->GetFieldList($listIndex);
				if($listIndex == 0) $worksheet = $objPHPExcel->getActiveSheet();
				else
				{
					if($writerType != 'CSV')
					{
						$worksheet = $objPHPExcel->createSheet();
						$row = 1;
					}
				}

				if($this->params['LIST_NAME'][$listIndex])
				{
					$worksheet->setTitle($this->GetCellValue($this->params['LIST_NAME'][$listIndex]));
				}
				
				if(isset($this->params['TEXT_ROWS_TOP'][$listIndex]))
				{
					foreach($this->params['TEXT_ROWS_TOP'][$listIndex] as $k=>$v)
					{
						$worksheet->setCellValueExplicit($arCols[0].$row, $this->GetCellValue($v));
						$row++;
					}
				}

				if($this->params['HIDE_COLUMN_TITLES'][$listIndex]!='Y')
				{
					$col = 0;
					$fNames = array();
					if(isset($this->params['FIELDS_LIST_NAMES'][$listIndex]))
					{
						$fNames = $this->params['FIELDS_LIST_NAMES'][$listIndex];
					}
					foreach($arFields as $k=>$field)
					{
						$width = 200;
						if(isset($this->fparams[$listIndex][$col]['DISPLAY_WIDTH']) && (int)$this->fparams[$listIndex][$col]['DISPLAY_WIDTH'] > 0) $width = (int)$this->fparams[$listIndex][$col]['DISPLAY_WIDTH'];
						$worksheet->getColumnDimension($arCols[$col])->setWidth($width / 9.7);
						$worksheet->setCellValueExplicit($arCols[$col].$row, $this->GetCellValue($fNames[$k]));
						$col++;
					}
					$row++;
				}
				
				if(isset($this->params['TEXT_ROWS_TOP2'][$listIndex]))
				{
					foreach($this->params['TEXT_ROWS_TOP2'][$listIndex] as $k=>$v)
					{
						$worksheet->setCellValueExplicit($arCols[0].$row, $this->GetCellValue($v));
						$row++;
					}
				}
				
				$this->OpenTmpdataHandler($listIndex, 'r');
				while(!feof($this->tmpdatafilehandler)) 
				{
					$buffer = trim(fgets($this->tmpdatafilehandler));
					if(strlen($buffer) < 1) continue;
					$arElement = \Bitrix\EsolAie\Utils::Unserialize(base64_decode($buffer));
					if(empty($arElement)) continue;
					
					if(isset($arElement['RTYPE']) && ($arElement['RTYPE']=='SECTION_PATH' || preg_match('/^SECTION_\d+$/', $arElement['RTYPE'])))
					{
						$worksheet->setCellValueExplicit($arCols[0].$row, $this->GetCellValue($arElement['NAME']));
					}
					else
					{
						$col = 0;
						foreach($arFields as $k=>$field)
						{
							$worksheet->setCellValueExplicit($arCols[$col++].$row, $this->GetCellValue((array_key_exists($field.'_'.$k, $arElement) ? $arElement[$field.'_'.$k] : $arElement[$field])));
						}
					}
					$row++;
				}
				$this->CloseTmpdataHandler();
			}
			
			$objWriter = \KDAPHPExcel_IOFactory::createWriter($objPHPExcel, $writerType);
			if($writerType == 'CSV')
			{
				//$objWriter->setExcelCompatibility(true);
				$delimiter = ($this->params['CSV_SEPARATOR'] ? $this->params['CSV_SEPARATOR'] : ';');
				$objWriter->setDelimiter($delimiter);
				$enclosure = ($this->params['CSV_ENCLOSURE'] ? $this->params['CSV_ENCLOSURE'] : '"');
				$objWriter->setEnclosure($enclosure);
				if($this->params['CSV_ENCODING']=='UTF-8')
				{
					$objWriter->setUseBOM(true);
				}
			}
			$objWriter->save($outputFile);
		}
		$this->SaveStatusImport(true);
		
		$this->CheckExtServices($outputFile);
		
		$arEventData = $this->profile->OnEndExport();
		
		return $this->GetBreakParams('finish');
	}
	
	public function CheckExtServices($outputFile)
	{
		if(strlen(trim($this->params['UPLOAD_ON_FTP'])) > 0)
		{
			$ftpPath = preg_replace_callback('/\{DATE_(\S*)\}/', array(__CLASS__, 'GetDateFromPath'), $this->params['UPLOAD_ON_FTP']);
			$sftp = new \Bitrix\EsolAie\Sftp();
			$sftp->Upload($ftpPath, $outputFile);
		}
		
		if($this->params['EXPORT_TO_BX24']=="Y" && $this->params['BX24_REST_URL'] && $this->params['BX24_FOLDER_ID'])
		{
			$url = trim($this->params['BX24_REST_URL']);
			if(substr($url, -1)!='/') $url .= '/';
			$folderType = 'storage';
			$folderId = trim($this->params['BX24_FOLDER_ID']);
			if(preg_match('/_\d+$/', $folderId, $m))
			{
				$folderType = ToLower(substr($folderId, 0, -strlen($m[0])));
				$folderId = substr($m[0], 1);
			}
			$fileName = basename($outputFile);
			$fileContent = base64_encode(file_get_contents($outputFile));
			if(in_array($folderType, array('folder', 'storage')))
			{
				$client = new \Bitrix\Main\Web\HttpClient();
				$res = $client->post($url.'disk.'.$folderType.'.getchildren', array('id' => $folderId, 'filter' => array('TYPE' => 'file', 'NAME'=>$fileName)));
				$arResult = \Bitrix\EsolAie\Utils::JsObjectToPhp($res);
				if($arResult['total'] > 0 && $arResult['result'][0]['ID'])
				{
					$fileId = $arResult['result'][0]['ID'];
					if($this->params['BX24_MODE']=='REPLACE')
					{
						$client = new \Bitrix\Main\Web\HttpClient();
						$res = $client->post($url.'disk.file.delete', array('id' => $fileId));
						$client = new \Bitrix\Main\Web\HttpClient();
						$res = $client->post($url.'disk.'.$folderType.'.uploadfile', array('id' => $folderId, 'data' => array('NAME' => $fileName), 'fileContent'=>$fileContent));
					}
					else
					{
						$client = new \Bitrix\Main\Web\HttpClient();
						$res = $client->post($url.'disk.file.uploadversion', array('id' => $fileId, 'fileContent'=>$fileContent));
					}
				}
				else
				{
					$client = new \Bitrix\Main\Web\HttpClient();
					$res = $client->post($url.'disk.'.$folderType.'.uploadfile', array('id' => $folderId, 'data' => array('NAME' => $fileName), 'fileContent'=>$fileContent));
				}
			}
		}
		
		if($this->params['EXPORT_TO_GOOGLE_SPREADSHEETS']=="Y" && $this->params['GOOGLE_TOKEN'] && $this->params['GOOGLE_SID'] && function_exists('json_encode'))
		{
			$tblSheetId = false;
			$tblId = $this->params['GOOGLE_SID'];
			if(preg_match('/\:(\d+)$/', $tblId, $m))
			{
				$tblId = mb_substr($tblId, 0, -mb_strlen($m[1]) - 1);
				$tblSheetId = $m[1];
			}
			$refreshToken = $this->params['GOOGLE_TOKEN'];
			$accessToken = '';
			$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
			$res = $ob->post('https://esolutions.su/marketplace/oauth.php', array('refresh_token'=> $refreshToken));
			$arRes = \Bitrix\EsolAie\Utils::JsObjectToPhp($res);
			if($arRes['access_token'])
			{
				$accessToken = $arRes['access_token'];
			}

			if($accessToken)
			{
				$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
				$ob->setHeader('Authorization', "Bearer ".$accessToken);
				$res = $ob->get('https://sheets.googleapis.com/v4/spreadsheets/'.$tblId);
				$arRes = \Bitrix\EsolAie\Utils::JsObjectToPhp($res);
				$arSheets = $arRes['sheets'];
				$tblSheet = 0;
				if($tblSheetId!==false)
				{
					$tblSheet = false;
					foreach($arSheets as $k=>$v)
					{
						if($v['properties']['sheetId']==$tblSheetId) $tblSheet = $k;
					}
				}
				if($tblSheet!==false)
				{
					foreach($this->params['LIST_NAME'] as $listIndex=>$listName)
					{
						$dataFile = $this->tmpdir.'data_'.$listIndex.'.txt';
						if(file_exists($dataFile) && isset($arSheets[$listIndex+$tblSheet]))
						{
							$domain = '';
							if($host = $this->GetDomain()) $domain = '//'.$host;
							
							$arSheet = $arSheets[$listIndex+$tblSheet];
							$listTitle = $arSheet['properties']['title'];
							
							/*Clear old data*/
							$maxRow = max(1, $arSheet['properties']['gridProperties']['rowCount']);
							$maxCol = max(1, $arSheet['properties']['gridProperties']['columnCount']);
							$maxColLetter = $maxCol;
							$arLNumbers = array();
							while($maxCol > 26)
							{
								$arLNumbers[] = ($maxCol-1)%26;
								$maxCol = ($maxCol-1)/26;
							}
							$arLNumbers[] = ($maxCol-1)%26;
							$arLNumbers = array_reverse($arLNumbers);
							$maxCol = '';
							foreach($arLNumbers as $n)
							{
								$maxCol .= range('A', 'Z')[$n];
							}
							$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
							$ob->setHeader('Authorization', "Bearer ".$accessToken);
							$res = $ob->post('https://sheets.googleapis.com/v4/spreadsheets/'.$tblId.'/values/'.(strlen($listTitle) > 0 ? "'".urlencode($listTitle)."'!" : '').'A1:'.$maxCol.$maxRow.':clear');
							/*/Clear old data*/
							
							/*Write new data*/
							$bFormula = false;
							$arData = $arDataFormula = $arImgHeights = array();
							$arFields = $this->GetFieldList($listIndex);
							if($this->params['HIDE_COLUMN_TITLES'][$listIndex]!='Y')
							{
								$arDataRow = array();
								foreach($this->params['FIELDS_LIST_NAMES'][$listIndex] as $k=>$field)
								{
									$arDataRow[$k] = $this->GetGoogleCellValue($field);
								}
								$arData[] = $arDataRow;
								$arDataFormula[] = array();
							}
							$arFieldParams = (isset($this->fparams[$listIndex]) && is_array($this->fparams[$listIndex]) ? $this->fparams[$listIndex] : array());
							
							$firstRow = 1;
							$handle = fopen($dataFile, 'r');
							while(!feof($handle)) 
							{
								$buffer = trim(fgets($handle));
								if(strlen($buffer) < 1) continue;
								$arElement = \Bitrix\EsolAie\Utils::Unserialize(base64_decode($buffer));
								if(empty($arElement)) continue;
								if(isset($arElement['RTYPE']) && ($arElement['RTYPE']=='SECTION_PATH' || preg_match('/^SECTION_(\d+)$/', $arElement['RTYPE'], $m)))
								{
									$arData[] = array($this->GetGoogleCellValue($arElement['NAME']));
									$arDataFormula[] = array();
								}
								else
								{
									$level = 1;
									if($this->currentSectionLevel > 0) $level = $this->currentSectionLevel + 1;
									$rowParams = array();
									if($this->params['EXPORT_GROUP_OPEN']!='Y') $rowParams['hidden'] = 1;
									if($this->params['EXPORT_GROUP_SUBSECTIONS']=='Y') $rowParams['outlineLevel'] = $level;
									else $rowParams['outlineLevel'] = 1;
									
									/*Multicell*/
									$arVals = $fullCells = array();
									$cellKey = 0;
									foreach($arFields as $k=>$field)
									{
										$arSettings = (isset($this->fparams[$listIndex][$k]) ? $this->fparams[$listIndex][$k] : array());
										if(!is_array($arSettings)) $arSettings = array();
										$valIndex = 0;
										$val = (array_key_exists($field.'_'.$k, $arElement) ? $arElement[$field.'_'.$k] : $arElement[$field]);
										if(is_array($val) && isset($val['TYPE']) && $val['TYPE']=='MULTICELL')
										{
											foreach($val as $kVal=>$vVal)
											{
												if(!is_numeric($kVal) && $kVal=='TYPE') continue;
												if(is_array($vVal) && isset($vVal['VALUE'])) $arVals[$valIndex][$k] = (string)$vVal['VALUE'];
												elseif(!is_array($vVal)) $arVals[$valIndex][$k] = (string)$vVal;
												else $arVals[$valIndex][$k] = '';
												foreach($arFields as $k2=>$field2)
												{
													if(!isset($arVals[$valIndex][$k2])) $arVals[$valIndex][$k2] = '';
												}
												$fullCells[$cellKey] = $valIndex;
												$valIndex++;
											}								
										}
										if($valIndex==0)
										{
											if($arSettings['INSERT_PICTURE']=='Y' && isset($arElement[$field]))
											{
												$val = $arElement[$field];
												if(isset($arElement[$field.'_'.$k.'_ORIG'])) $val = $arElement[$field.'_'.$k.'_ORIG'];
												if(preg_match('#^(.*/)([^/]+)$#', rawurldecode($val), $m)) $val = $m[1].rawurlencode($m[2]);
												if(strpos($val, '/')===0 && strpos($val, '//')!==0)
												{
													if($domain) $val = $domain.$val;
												}
												if(strlen($val) > 0) $val = '=IMAGE("'.$val.'";1)';
												if(isset($arElement[$field.'_'.$k.'_MAXHEIGHT']) && $arElement[$field.'_'.$k.'_MAXHEIGHT'] > 20)
												{
													$rowIndex = count($arData);
													$arImgHeights[$rowIndex] = max((int)$arImgHeights[$rowIndex], (int)$arElement[$field.'_'.$k.'_MAXHEIGHT']);
												}
											}
											$arVals[$valIndex][$k] = $val;
											$fullCells[$cellKey] = $valIndex;
										}
										$cellKey++;
									}
									/*/Multicell*/
									
									
									foreach($arVals as $valIndex=>$arValue)
									{
										$arDataRow = $arDataFormulaRow = array();
										foreach($arFields as $k=>$field)
										{
											$arDataRow[$k] = $this->GetGoogleCellValue($arValue[$k], $arFieldParams[$k]);
											$arDataFormulaRow[$k] = null;
											if(preg_match('/^=[A-Z][A-Z0-9\.]+\(/', $arDataRow[$k]))
											{
												$arDataFormulaRow[$k] = preg_replace('/([A-Z])0([^A-Z0-9]|$)/', '${1}'.(count($arDataFormula)+1).'${2}', $arDataRow[$k]);
												$bFormula = true;
											}
										}
										$arData[] = $arDataRow;
										$arDataFormula[] = $arDataFormulaRow;
									}
								}
							}
							fclose($handle);
							
							$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
							$ob->setHeader('Authorization', "Bearer ".$accessToken);
							$ob->query('PUT', 'https://sheets.googleapis.com/v4/spreadsheets/'.$tblId.'/values/'.(strlen($listTitle) > 0 ? "'".urlencode($listTitle)."'!" : '').'A'.$firstRow.'?valueInputOption=RAW', json_encode(array('values'=>$arData)/*, JSON_UNESCAPED_UNICODE*/));
							//$res = $ob->getResult();
							
							if($bFormula)
							{
								$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
								$ob->setHeader('Authorization', "Bearer ".$accessToken);
								$ob->query('PUT', 'https://sheets.googleapis.com/v4/spreadsheets/'.$tblId.'/values/'.(strlen($listTitle) > 0 ? "'".urlencode($listTitle)."'!" : '').'A'.$firstRow.'?valueInputOption=USER_ENTERED', json_encode(array('values'=>$arDataFormula)/*, JSON_UNESCAPED_UNICODE*/));
								$res = $ob->getResult();
							}
							/*/Write new data*/
							
							if(count($arImgHeights) > 0)
							{
								$arRequests = array();
								foreach($arImgHeights as $rowIndex=>$rowHeight)
								{
									$arRequests[] = array(
										'updateDimensionProperties' => array(
											'properties' => array('pixelSize'=>$rowHeight), 
											'fields' => 'pixelSize',
											'range'=>array('sheetId'=>$listID, 'dimension'=>'ROWS', 'startIndex'=>$rowIndex, 'endIndex'=>$rowIndex + 1)
										),
										/*'updateCells' => array(
											'rows' => array('values'=>array('userEnteredFormat'=>array('backgroundColor'))), 
											'fields' => 'pixelSize',
											'range'=>array('sheetId'=>$listID, 'dimension'=>'ROWS', 'startIndex'=>$rowIndex, 'endIndex'=>$rowIndex + 1)
										),*/
									);
								}
								$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
								$ob->setHeader('Authorization', "Bearer ".$accessToken);
								$ob->setHeader('Content-type', 'application/json');
								$ob->query('POST', 'https://sheets.googleapis.com/v4/spreadsheets/'.$tblId.':batchUpdate', json_encode(
									array('requests' => $arRequests)
								));
								$res = $ob->getResult();
							}
							
							/*
							$ob = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
							$ob->setHeader('Authorization', "Bearer ".$accessToken);
							$ob->setHeader('Content-type', 'application/json');
							$ob->query('POST', 'https://sheets.googleapis.com/v4/spreadsheets/'.$tblId.':batchUpdate', json_encode(
								array('requests' => array(
									array(
										'repeatCell' => array(
											'range' => array(
												'sheetId' => $listID,
												'startRowIndex' => 0,
												'endRowIndex' => 3,
												'startColumnIndex' => 0,
												'endColumnIndex' => 29
											),
											'cell' => array(
												'userEnteredFormat' => array(
													'backgroundColor' => array(
													  "red" => 1,
													  "green" => 0.0,
													  "blue" => 0.0
													)
												)
											),
											'fields' => 'userEnteredFormat(backgroundColor)'
										)
									),
								))
							));
							$res = $ob->getResult();
							*/
						}
					}
				}
			}
		}
		
		if($this->params['EXPORT_TO_EMAIL']=="Y" && (int)$this->params['MAIL_TEMPLATE_ID'] > 0 && $this->stepparams['total_read_line'] > 0)
		{
			$arProfile = $this->profile->GetFieldsByID($this->pid);
			$arMailFields = array(
				'DATE' => date('d.m.Y'),
				'DATETIME' => date('d.m.Y H:i:s'),
				'EMAIL_TO' => $this->params['MAIL_TEMPLATE_EMAIL'],
				'PROFILE_NAME' => $arProfile['NAME'],
				'EXPORT_START_DATETIME' => (is_callable(array($arProfile['DATE_START'], 'toString')) ? $arProfile['DATE_START']->toString() : ''),
				'EXPORT_FINISH_DATETIME' => ConvertTimeStamp(false, 'FULL')
			);
			\CEvent::Send('ESOL_ALLEXPORT_SEND_FILE', $this->GetDefaultSiteId(), $arMailFields, 'Y', $this->params['MAIL_TEMPLATE_ID'], array($outputFile));
		}
	}
	
	public function GetGoogleCellValue($val, $arParams=array())
	{
		$val = mb_substr($val, 0, 32767);
		//if(isset($arParams['NUMBER_FORMAT']) && in_array($arParams['NUMBER_FORMAT'], array(1, 2, 3, 4))) return (float)$val;
		if(is_numeric($val) && strlen($val)<ini_get('precision')) return (float)$val;
		return (string)$val;
	}
	
	public function GetCellValue($val)
	{
		if($this->params['FILE_EXTENSION']=='csv' && $this->params['CSV_ENCODING']=='CP1251')
		{
			if(\Bitrix\EsolAie\Utils::IsUtfMode())
			{
				$val = $GLOBALS['APPLICATION']->ConvertCharset($val, 'UTF-8', 'CP1251');
			}
		}
		elseif(!\Bitrix\EsolAie\Utils::IsUtfMode())
		{
			$val = $GLOBALS['APPLICATION']->ConvertCharset($val, 'CP1251', 'UTF-8');
		}
		return $val;
	}
	
	public function GetBreakParams($action = 'continue')
	{
		$arStepParams = array(
			'params'=> $this->stepparams,
			'action' => $action,
			'errors' => $this->errors,
			'sessid' => bitrix_sessid()
		);
		
		if($action == 'continue')
		{
			$this->CloseTmpdataHandler();
			file_put_contents($this->tmpfile, serialize($arStepParams['params']));
			/*if(file_exists($this->imagedir))
			{
				DeleteDirFilesEx(substr($this->imagedir, strlen($this->docRoot)));
			}*/
		}
		elseif(file_exists($this->tmpdir))
		{
			DeleteDirFilesEx(substr($this->tmpdir, strlen($this->docRoot)));
			unlink($this->procfile);
		}
		
		return $arStepParams;
	}
	
	public function ExecuteFilterExpression($val, $expression, $altReturn = true)
	{
		$expression = trim($expression);
		if(strlen($expression)==0) return $val;
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
		}catch(\Exception | \Error $ex){
			//return $altReturn;
			return $ex->getMessage();
		}
	}
	
	public function ExecuteOnAfterSaveHandler($handler, $ID)
	{
		try{				
			$command = $handler.';';
			eval($command);
		}catch(\Exception $ex){}
	}
	
	public function SaveStatusImport($end = false)
	{
		if($this->procfile)
		{
			$writeParams = $this->stepparams;
			$writeParams['action'] = ($end ? 'finish' : 'continue');
			file_put_contents($this->procfile, \Bitrix\EsolAie\Utils::PhpToJSObject($writeParams));
		}
	}
	
	public function GetFileArray($file)
	{
		if(is_numeric($file))
		{
			if($arFile = \Bitrix\EsolAie\Export\Utils::GetFileArray($file))
			{
				$file = $arFile['SRC'];
			}
			else return array();
		}
		elseif(strlen($file)==0) return array();
		
		$file = \Bitrix\Main\IO\Path::convertLogicalToPhysical(trim($file));
		if(strpos($file, '/')===0)
		{
			if(file_exists($this->docRoot.$file))
			{
				$arFile = \CFile::MakeFileArray($file);
				$ext = '.jpg';
				if(preg_match('/\.[^\.]{2,5}$/', $arFile['name'], $m))
				{
					$ext = ToLower($m[0]);
				}
				if($ext=='.webp')
				{
					if(stripos($arFile['type'], 'webp')!==false)
					{
						$file = $this->imagedir.'image'.(++$this->stepparams['image_cnt']).'.png';
						if(function_exists('imagecreatefromwebp') && function_exists('imagepng'))
						{
							$img = imagecreatefromwebp($arFile['tmp_name']);
							imagepng($img, $file, 9);
							imagedestroy($img);
						}
					}
					elseif(preg_match('/^image\//i', $arFile['type'], $m))
					{
						$file = $this->imagedir.'image'.(++$this->stepparams['image_cnt']).'.'.substr($arFile['type'], 6);
						copy($arFile['tmp_name'], $file);
					}
				}
				else
				{
					$file = $this->imagedir.'image'.(++$this->stepparams['image_cnt']).$ext;
					copy($arFile['tmp_name'], $file);
				}
			}
			else
			{
				$arFile = array();
			}
		}
		elseif(preg_match('/http(s)?:\/\//', $file))
		{
			$arUrl = parse_url($file);
			//Cyrillic domain
			if(preg_match('/[^A-Za-z0-9\-\.]/', $arUrl['host']))
			{
				if(!class_exists('idna_convert')) require_once(dirname(__FILE__).'/../../lib/idna_convert.class.php');
				if(class_exists('idna_convert'))
				{
					$idn = new \idna_convert();
					$oldHost = $arUrl['host'];
					if(!\CUtil::DetectUTF8($oldHost)) $oldHost = Utils::Win1251Utf8($oldHost);
					$file = str_replace($arUrl['host'], $idn->encode($oldHost), $file);
				}
			}
		}
		$arFile = \CFile::MakeFileArray($file);
		if(!$arFile['name'] && !\CUtil::DetectUTF8($file))
		{
			$file = Utils::Win1251Utf8($file);
			$arFile = \CFile::MakeFileArray($file);
		}
		if(strpos($arFile['type'], 'image/')===0)
		{
			$ext = ToLower(str_replace('image/', '', $arFile['type']));
			if(mb_substr($arFile['name'], -(mb_strlen($ext) + 1))!='.'.$ext)
			{
				if($ext!='jpeg' || (($ext='jpg') && mb_substr($arFile['name'], -(mb_strlen($ext) + 1))!='.'.$ext))
				{
					$arFile['name'] = $arFile['name'].'.'.$ext;
				}
			}
		}
		return $arFile;
	}
	
	public function GetFieldValue($arProp, $val)
	{
		if($arProp['data_type']=='file')
		{
			$val = $this->GetFileValue($val);
		}
		elseif($arProp['data_type']=='iblock_element')
		{
			$val = $this->GetFieldElementValue($arProp, $val);
		}
		elseif($arProp['data_type']=='iblock_section')
		{
			$val = $this->GetFieldSectionValue($arProp, $val);
		}
		elseif($arProp['data_type']=='hlblock')
		{
			$val = $this->GetHighloadBlockValue($arProp, $val);
		}
		elseif($arProp['data_type']=='enumeration')
		{
			$val = $this->GetFieldEnumValue($arProp, $val);
		}
		
		if($arProp['uf_user_type'])
		{
			if($arProp['uf_user_type']=='hlblock')
			{
				$val = $this->GetHighloadBlockValue($arProp, $val);
			}
			elseif($arProp['uf_user_type']=='boolean')
			{
				$val = $this->GetBoolValue($val);
			}
			elseif($arProp['uf_user_type']=='file')
			{
				$val = $this->GetFileValue($val);
			}
			elseif($arProp['uf_user_type']=='enumeration')
			{
				$val = $this->GetFieldEnumValue($arProp, $val);
			}
			elseif($arProp['uf_user_type']=='iblock_element')
			{
				$val = $this->GetFieldElementValue($arProp, $val);
			}
			elseif($arProp['uf_user_type']=='iblock_section')
			{
				$val = $this->GetFieldSectionValue($arProp, $val);
			}
		}
		
		if($arProp['serialized']==1)
		{
			$val = serialize($val);
		}
		
		return $val;
	}
	
	public function GetBoolValue($val)
	{
		$trueVals = array_map('trim', explode(',', Loc::getMessage("ESOL_AIE_FIELD_VAL_Y")));
		$falseVals = array_map('trim', explode(',', Loc::getMessage("ESOL_AIE_FIELD_VAL_N")));
		if(in_array(ToLower($val), $trueVals))
		{
			return Loc::getMessage("ESOL_AIE_SHOW_VAL_Y");
		}
		elseif(in_array(ToLower($val), $falseVals))
		{
			return Loc::getMessage("ESOL_AIE_SHOW_VAL_N");
		}
		else
		{
			return false;
		}
	}
	
	public function GetFileValue($val, $key=false)
	{
		if($val)
		{
			$arFile = \CFile::GetFileArray($val);
			if($arFile)
			{
				$val = $arFile['SRC'];
			}
			else
			{
				$val = '';
			}
			
			if($this->params['EXPORT_FILES_IN_ARCHIVE']=='Y' && strlen($this->params['FILES_ARCHIVE_PATH']) > 0 && strlen($val) > 0 && file_exists($this->docRoot.$val))
			{
				if($key!==false && !empty($this->fparamsByName[$this->listIndex][$key]['CONVERSION']))
				{
					$this->filesForMove[] = array('path'=>$val, 'conv'=>$this->fparamsByName[$this->listIndex][$key]['CONVERSION']);
				}
				else
				{
					$this->PutFileToArchive($val);
				}
			}
		}
		return $val;
	}
	
	public function ProcessMoveFiles($arElementData)
	{
		if(empty($this->filesForMove)) return;
		$parentDir = $this->tmpdir.'tmpimages/';
		foreach($this->filesForMove as $arFile)
		{
			$newPath = $this->ApplyConversions($arFile['path'], $arFile['conv'], $arElementData);
			$newPath = trim(trim(preg_replace('/[\x01-\x1F'.preg_quote("\\:*?\"'<>|~#&;", "/").']+/', '', $newPath)), '/');
			if(preg_match('/^\s*https?:\/\//i', $newPath)) continue;
			$newPath = $parentDir.$newPath;
			$io = \CBXVirtualIo::GetInstance();
			$io->copy($this->docRoot.$arFile['path'], $newPath);
			$this->PutFileToArchive(substr($newPath, strlen($this->docRoot)), $parentDir);
		}
		DeleteDirFilesEx(substr($parentDir, strlen($this->docRoot)));
		$this->filesForMove = array();
	}
	
	public function PutFileToArchive($val, $removePath='')
	{
		if($this->stepparams['curstep'] != 'export') return;
		$zipFile = '';
		$suffix = 0;
		$archivePath = $this->docRoot. preg_replace('/\.zip\s*$/U', '', '/'.ltrim($this->params['FILES_ARCHIVE_PATH'], '/'));
		while(strlen($zipFile)==0 || (file_exists($zipFile) && filesize($zipFile)>1024*1024*100))
		{
			$zipFile = $archivePath.($suffix > 0 ? '_'.$suffix : '').'.zip';
			$suffix++;
		}
		$siteEncoding = Utils::getSiteEncoding();
		$fsEncoding = Utils::getfileSystemEncoding();
		
		if(class_exists('ZipArchive') && ($zipObj = new \ZipArchive()) && $zipObj->open($zipFile, \ZipArchive::CREATE)===true)
		{
			$f1 = $this->docRoot.$val;
			if($siteEncoding!=$fsEncoding) $f1 = \Bitrix\Main\Text\Encoding::convertEncoding($f1, $siteEncoding, $fsEncoding);
			$f2 = \Bitrix\Main\Text\Encoding::convertEncoding(ltrim($val, '/'), $siteEncoding, 'cp866');
			if(strlen($removePath) > 0) $f2 = \Bitrix\Main\Text\Encoding::convertEncoding(ltrim(substr($this->docRoot.$val, strlen($removePath)), '/'), $siteEncoding, 'cp866');
			$zipObj->addFile($f1, $f2);
			$zipObj->close();
		}
		else
		{
			$zipObj = \CBXArchive::GetArchive($zipFile, 'ZIP');
			$zipObj->Add($this->docRoot.$val, array("add_path" => false, "remove_path" => (strlen($removePath) > 0 ? $removePath : $this->docRoot.'/')));
		}
	}
	
	public function GetFileDescription($val)
	{
		if($val)
		{
			$arFile = \CFile::GetFileArray($val);
			if($arFile)
			{
				$val = $arFile['DESCRIPTION'];
			}
			else
			{
				$val = '';
			}
		}
		return $val;
	}
	
	public function GetFieldEnumValue($arProp, $val)
	{
		if(!isset($this->enumVals[$val]))
		{
			$val = (int)$val;
			$dbRes = \CUserFieldEnum::Getlist(array(), array('ID'=>$val));
			if($arr = $dbRes->Fetch())
			{
				$this->enumVals[$val] = $arr['VALUE'];
			}
			else
			{
				$this->enumVals[$val] = '';
			}
		}
		return $this->enumVals[$val];
	}
	
	public function GetHighloadBlockValue($arProp, $val)
	{
		if($val && Loader::includeModule('highloadblock') && $arProp['uf_settings']['HLBLOCK_ID'] && $arProp['uf_settings']['HLFIELD_ID'])
		{
			$htblId = (int)$arProp['uf_settings']['HLBLOCK_ID'];
			if(!isset($this->htblPropVals[$htblId][$val]))
			{
				$fieldId = (int)$arProp['uf_settings']['HLFIELD_ID'];
				if(!isset($this->hlblFields[$fieldId]))
				{
					$dbRes = \CUserTypeEntity::GetList(array(), array('ID'=>$fieldId));
					if($arr = $dbRes->Fetch())
					{
						$this->hlblFields[$fieldId] = $arr['FIELD_NAME'];
					}
					else
					{
						$this->hlblFields[$fieldId] = 'UF_NAME';
					}
				}
				$fieldName = $this->hlblFields[$fieldId];
				
				if(!$this->hlbl[$arProp['ID']])
				{
					$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('ID'=>$htblId)))->fetch();
					$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
					$this->hlbl[$arProp['ID']] = $entity->getDataClass();
				}
				$entityDataClass = $this->hlbl[$arProp['ID']];
				
				$dbRes2 = $entityDataClass::GetList(array('filter'=>array("ID"=>$val), 'select'=>array('ID', $fieldName), 'limit'=>1));
				if($arr2 = $dbRes2->Fetch())
				{
					$this->htblPropVals[$htblId][$val] = $arr2[$fieldName];
				}
				else
				{
					$this->htblPropVals[$htblId][$val] = '';
				}
			}
			return $this->htblPropVals[$htblId][$val];
		}
		return $val;
	}
	
	public function GetFieldElementValue($arProp, $val, $relField='')
	{
		if($val && Loader::includeModule('iblock'))
		{
			$selectField = 'NAME';
			if($relField)
			{
				if(strpos($relField, 'IE_')===0)
				{
					$selectField = substr($relField, 3);
				}
				elseif(strpos($relField, 'IP_PROP')===0)
				{
					$selectField = 'PROPERTY_'.substr($relField, 7);
				}
			}
			
			if(!isset($this->elemPropVals[$val][$selectField]))
			{
				$dbRes = \CIBlockElement::GetList(array(), array("ID"=>$val), false, false, array($selectField));
				if($arElem = $dbRes->GetNext())
				{
					$selectedField = $selectField;
					if(strpos($selectedField, 'PROPERTY_')===0) $selectedField .= '_VALUE';
					$this->elemPropVals[$val][$selectField] = $arElem[$selectedField];
				}
				else
				{
					$this->elemPropVals[$val][$selectField] = '';
				}
			}
			$val = $this->elemPropVals[$val][$selectField];
		}
		return $val;
	}
	
	public function GetFieldSectionValue($arProp, $val, $relField='')
	{
		if($val && Loader::includeModule('iblock'))
		{
			$selectField = 'NAME';
			if($relField)
			{
				$selectField = $relField;
			}
			
			if(!isset($this->sectPropVals[$val][$selectField]))
			{
				$dbRes = \CIBlockSection::GetList(array(), array("ID"=>$val), false, array($selectField));
				if($arElem = $dbRes->GetNext())
				{
					$this->sectPropVals[$val][$selectField] = $arElem[$selectField];
				}
				else
				{
					$this->sectPropVals[$val][$selectField] = '';
				}
			}
			$val = $this->sectPropVals[$val][$selectField];
		}
		return $val;
	}
	
	public function GetHTMLValue($arProp, $val)
	{
		if(isset($val['TEXT'])) return $val['TEXT'];
		else return $val;
	}
	
	public function ConversionReplaceValues($m)
	{
		$value = '';
		$paramName = $m[0];
		$quot = "'";
		$isVar = false;
		if(preg_match('/^\$\{([\'"])(.*)[\'"]\}?$/', $paramName, $m2))
		{
			$quot = $m2[1];
			$paramName = $m2[2];
			$isVar = true;
		}
		
		$k = substr($paramName, 1, -1);		
		if(1 || isset($this->currentItemValues[$k]))
		{
			$value = $this->currentItemValues[$k];
			if($this->currentFieldKey!==false && is_array($value) && array_key_exists($this->currentFieldKey, $value)) $value = $value[$this->currentFieldKey];
			if(is_array($value)) $value = implode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $value);
			if(preg_match('/^(OFFER_)?(PURCHASING_PRICE|ICAT_PRICE\d+_PRICE(_DISCOUNT)?)$/', $this->currentFieldName)
				&& preg_match('/^(OFFER_)?(PURCHASING_PRICE|ICAT_PRICE\d+_PRICE(_DISCOUNT)?)$/', $k))
			{
				$currKey = preg_replace('/_PRICE(_DISCOUNT)?$/', '_CURRENCY', $k);
				
			}
		}

		if($isVar)
		{
			$this->extraConvParams[$paramName] = $value;
			return '$this->extraConvParams['.$quot.$paramName.$quot.']';
		}
		else return $value;
	}
	
	public function ApplyConversions($val, $arConv, $arItem, $field=false, $iblockFields=array())
	{
		$fieldName = $fieldKey = false;
		if(!is_array($field))
		{
			$fieldName = $field;
		}
		else
		{
			if(array_key_exists('NAME', $field)) $fieldName = $field['NAME'];
			if(array_key_exists('KEY', $field)) $fieldKey = $field['KEY'];
		}
		
		if(is_array($arConv))
		{
			$execConv = false;
			$this->currentItemValues = $arItem;
			$prefixPattern = '/(\$\{[\'"])?(#[A-Za-z0-9\_\.]+#)([\'"]\})?/';
			foreach($arConv as $k=>$v)
			{
				$condVal = $val;
				if(strlen($v['CELL']) > 0 && !in_array($v['CELL'], array('ELSE')))
				{
					$condVal = $arItem[$v['CELL']];
				}
				if(is_array($condVal)) $condVal = implode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $condVal);
				if(strlen($v['FROM']) > 0) $v['FROM'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['FROM']);
				if($v['CELL']=='ELSE') $v['WHEN'] = '';
				if(($v['CELL']=='ELSE' && !$execConv)
					|| ($v['WHEN']=='EQ' && ($condVal==$v['FROM'] && strlen($condVal)==strlen($v['FROM'])))
					|| ($v['WHEN']=='NEQ' && ($condVal!=$v['FROM'] || strlen($condVal)!=strlen($v['FROM'])))
					|| ($v['WHEN']=='GT' && $condVal > $v['FROM'])
					|| ($v['WHEN']=='LT' && $condVal < $v['FROM'])
					|| ($v['WHEN']=='GEQ' && $condVal >= $v['FROM'])
					|| ($v['WHEN']=='LEQ' && $condVal <= $v['FROM'])
					|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
					|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
					|| ($v['WHEN']=='REGEXP' && preg_match('/'.ToLower($v['FROM']).'/i'.Utils::getUtfModifier(), ToLower($condVal)))
					|| ($v['WHEN']=='NOT_REGEXP' && !preg_match('/'.ToLower($v['FROM']).'/i'.Utils::getUtfModifier(), ToLower($condVal)))
					|| ($v['WHEN']=='EMPTY' && strlen($condVal)==0)
					|| ($v['WHEN']=='NOT_EMPTY' && strlen($condVal) > 0)
					|| ($v['WHEN']=='ANY'))
				{
					$this->currentFieldKey = $fieldKey;
					$this->currentFieldName = $fieldName;
					if(strlen($v['TO']) > 0) $v['TO'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['TO']);
					if($v['THEN']=='REPLACE_TO') $val = $v['TO'];
					elseif($v['THEN']=='REMOVE_SUBSTRING' && strlen($v['TO']) > 0) $val = str_replace($v['TO'], '', $val);
					elseif($v['THEN']=='REPLACE_SUBSTRING_TO' && strlen($v['FROM']) > 0)
					{
						if($v['WHEN']=='REGEXP')
						{
							if(preg_match('/'.$v['FROM'].'/i'.Utils::getUtfModifier(), $val)) $val = preg_replace('/'.$v['FROM'].'/i'.Utils::getUtfModifier(), $v['TO'], $val);
							else $val = preg_replace('/'.ToLower($v['FROM']).'/i'.Utils::getUtfModifier(), $v['TO'], $val);
						}
						else $val = str_replace($v['FROM'], $v['TO'], $val);
					}
					elseif($v['THEN']=='ADD_TO_BEGIN') $val = $v['TO'].$val;
					elseif($v['THEN']=='ADD_TO_END') $val = $val.$v['TO'];
					elseif($v['THEN']=='MATH_ROUND') $val = round(doubleval(str_replace(',', '.', $val)));
					elseif($v['THEN']=='MATH_MULTIPLY') $val = doubleval(str_replace(',', '.', $val)) * doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_DIVIDE') $val = doubleval(str_replace(',', '.', $val)) / doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_ADD') $val = doubleval(str_replace(',', '.', $val)) + doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='MATH_SUBTRACT') $val = doubleval(str_replace(',', '.', $val)) - doubleval(str_replace(',', '.', $v['TO']));
					elseif($v['THEN']=='SKIP_LINE') $val = false;
					elseif($v['THEN']=='EXPRESSION') $val = $this->ExecuteFilterExpression($val, $v['TO'], '');
					elseif($v['THEN']=='STRIP_TAGS') $val = strip_tags($val);
					elseif($v['THEN']=='CLEAR_TAGS') $val = preg_replace('/<([a-z][a-z0-9:]*)[^>]*(\/?)>/i','<$1$2>', $val);
					elseif($v['THEN']=='ADD_LINK') $val = '<a class="kda-ee-conversion-link" href="'.$v['TO'].'">'.$val.'</a>';
					elseif($v['THEN']=='TRANSLIT')
					{
						$arParams = array();
						if($fieldName && !empty($iblockFields))
						{
							$paramName = '';
							if($fieldName=='IE_CODE') $paramName = 'CODE';
							if(preg_match('/^ISECT\d+_CODE$/', $fieldName)) $paramName = 'SECTION_CODE';
							if($paramName && $iblockFields[$paramName]['DEFAULT_VALUE']['TRANSLITERATION']=='Y')
							{
								$arParams = $iblockFields['SECTION_CODE']['DEFAULT_VALUE'];
							}
						}
						$val = $this->Str2Url($val, $arParams);
					}
					$execConv = true;
				}
			}
		}
		return $val;
	}
	
	public function GetExportData($listIndex, $limit=10, $page=1)
	{
		$this->listIndex = $listIndex;
		$arNavParams = false;
		if(is_numeric($limit) && $limit > 0)
		{
			$arNavParams['limit'] = (int)$limit;
			$arNavParams['offset'] = $arNavParams['limit'] * (max(1, $page)-1);
		}
		
		$entityDataClass = $this->fl->GetEntityClass();
		$arFields = $this->GetFieldList($listIndex);

		$arEntityFilterFields = $this->fl->GetEntityFieldsForFilter();	
		$arFilter = array();
		if(!isset($this->filters)) $this->filters = array();
		if(!isset($this->filters[$listIndex]))
		{
			$arFilter = array();
			if($this->params['USE_NEW_FILTER']=='Y')
			{
				/*new filter*/					
				if($this->params['EFILTER'][$listIndex])
				{
					$eFilter = new \Bitrix\EsolAie\Export\Filter();
					$eFilter->SetFilter($arFilter, $this->params['EFILTER'][$listIndex], $this->fl);
				}
				/*/new filter*/
			}
			elseif($this->params['FILTER'][$listIndex])
			{
				$arAddFilter = $this->params['FILTER'][$listIndex];
				foreach($arEntityFilterFields as $k=>$v)
				{
					$key = ToLower($k);
					if($v['primary'])
					{
						if(isset($arAddFilter['find_entity_'.$key]) && strlen($arAddFilter['find_entity_'.$key]) > 0)
						{
							$value = $arAddFilter['find_entity_'.$key];
							$comp = $arAddFilter['find_entity_'.$key.'_comp'];
							if($comp=='from_to')
							{
								list($filterFrom, $filterTo) = explode('-', $value, 2);
								if(is_numeric(trim($filterFrom))) $arFilter[">=".$k] = (int)trim($filterFrom);
								if(is_numeric(trim($filterTo))) $arFilter["<=".$k] = (int)trim($filterTo);
							}
							else
							{
								if(strlen($value) > 0 && $value!==false)
								{
									$value = array_diff(preg_split('/[\s,;]+/', $value), array(''));
									if(count($value)==1) $value = current($value);
								}
								$op = $this->GetStringOperation($value, $comp);
								if(is_array($value) || strlen($value) > 0 || $value===false)
								{
									$arFilter[$op.$k] = $value;
								}
							}
						}
						else
						{
							if(!empty($arAddFilter['find_entity_'.$key.'_start'])) $arFilter[">=".$k] = $arAddFilter['find_entity_'.$key.'_start'];
							if(!empty($arAddFilter['find_entity_'.$key.'_end'])) $arFilter["<=".$k] = $arAddFilter['find_entity_'.$key.'_end'];
						}
					}
					elseif($v['data_type']=='integer' || $v['data_type']=='float')
					{
						$value = $arAddFilter['find_entity_'.$key];
						if(strlen($value) > 0 && $value!==false)
						{
							$value = array_diff(preg_split('/[\s,;]+/', $value), array(''));
							if(count($value)==1) $value = current($value);
						}
						$comp = $arAddFilter['find_entity_'.$key.'_comp'];
						$op = $this->GetStringOperation($value, $comp);
						if(is_array($value) || strlen($value) > 0 || $value===false)
						{
							$arFilter[$op.$k] = $value;
						}
					}
					elseif($v['data_type']=='date' || $v['data_type']=='datetime')
					{
						$this->AddDateFilter($arFilter, $arAddFilter, $k, 'find_entity_'.$key);
						/*$valueFrom = $arAddFilter['find_entity_'.$key.'_from'];
						$valueTo = $arAddFilter['find_entity_'.$key.'_to'];
						$comp = $arAddFilter['find_entity_'.$key.'_comp'];
						if($comp=='empty') $arFilter[$k] = false;
						elseif($comp=='not_empty') $arFilter['!'.$k] = false;
						else
						{	
							if(strlen($valueFrom) > 0)
							{
								$arFilter[$op.$k] = $value;
							}
						}*/
					}
					elseif(isset($arAddFilter['find_entity_'.$key.'|list']) && !empty($arAddFilter['find_entity_'.$key.'|list']))
					{
						$arFilter['='.$k] = $arAddFilter['find_entity_'.$key.'|list'];
					}
					else
					{
						$value = $arAddFilter['find_entity_'.$key];
						$comp = $arAddFilter['find_entity_'.$key.'_comp'];
						$op = $this->GetStringOperation($value, $comp);
						if(strlen($value) > 0 || $value===false)
						{
							$arFilter[$op.$k] = $value;
						}
					}
				}
			}
			if(isset($this->params['ELEMENT_ID']) && $this->params['ELEMENT_ID']) $arFilter = array('ID' => $this->params['ELEMENT_ID']);
			if(is_callable(array($entityDataClass, 'PrepareExportFilter')))
			{
				$entityDataClass::PrepareExportFilter($arFilter);
			}
			$this->filters[$listIndex] = $arFilter;
		}
		else
		{
			$arFilter = $this->filters[$listIndex];
		}		
				
		
		$this->customFieldSettings = array();
		$arFieldsAdded = array();
		if(is_array($this->fparams[$listIndex]))
		{
			foreach($this->fparams[$listIndex] as $fieldIndex=>$arSettings)
			{
				$field = $arFields[$fieldIndex];
				$this->customFieldSettings[$field] = $arSettings;
				if(isset($arSettings['CONVERSION']) && is_array($arSettings['CONVERSION']) && $field)
				{
					foreach($arSettings['CONVERSION'] as $k=>$v)
					{
						if($v['CELL'] && $v['CELL']!='ELSE' && !in_array($v['CELL'], $arFieldsAdded))
						{
							$arFieldsAdded[] = $v['CELL'];
						}
						if(preg_match_all('/#(\S+)#/', $v['FROM'], $m))
						{
							foreach($m[1] as $key)
							{
								if(!in_array($key, $arFields) && !in_array($key, $arFieldsAdded))
								{
									$arFieldsAdded[] = $key;
								}
							}
						}
						if(preg_match_all('/#(\S+)#/', $v['TO'], $m))
						{
							foreach($m[1] as $key)
							{
								if(!in_array($key, $arFields) && !in_array($key, $arFieldsAdded))
								{
									$arFieldsAdded[] = $key;
								}
							}
						}
					}
				}
			}
		}
		
		$arAllFields = array_merge($arFields, $arFieldsAdded);
		
		$arData = array();
		$arParams = array(
			'FILTER' => $arFilter,
			'NAV_PARAMS' => $arNavParams,
			'FIELDS' => $arAllFields
		);
		$arResElements = $this->GetElementsData($arData, $arParams);
		$this->navRecordCount = $arResElements['navRecordCount'];
		
		$entityDataClass = $this->fl->GetEntityClass();
		$arEntityFields = $this->fl->GetEntityFields();
		$arFieldClasses = array();
		foreach($arFields as $field)
		{
			$arFieldClasses[$field] = $entityDataClass;
			$arFieldParts = explode('.', $field);
			$arEntityFields2 = $arEntityFields;
			while(count($arFieldParts) > 1 && ($entity = array_shift($arFieldParts)) && isset($arEntityFields2[$entity]) && isset($arEntityFields2[$entity]['class']))
			{
				$arFieldClasses[$field] = $arEntityFields2[$entity]['class'];
				$arEntityFields2 = $arEntityFields2[$entity]['items'];
			}
		}

		$arMultiRows = array();
		foreach($arData as $k=>$arElementData)
		{
			$skipLine = false;
			$arFieldSettings = array();
			if(is_array($this->fparams[$listIndex]))
			{
				foreach($this->fparams[$listIndex] as $fieldIndex=>$arSettings)
				{
					$field = $arFields[$fieldIndex];
					$arFieldSettings[$field] = $arSettings;
					$arFieldSettings[$field.'_'.$fieldIndex] = $arSettings;
					if(array_key_exists($field, $arElementData))
					{
						$fVal = $arElementData[$field];
						$flPart = end(explode('.', $field));
						if(is_callable(array($arFieldClasses[$field], 'PrepareExportField_'.$flPart)))
						{
							$fVal = $arData[$k][$field.'_'.$fieldIndex] = $arElementData[$field.'_'.$fieldIndex] = call_user_func(array($arFieldClasses[$field], 'PrepareExportField_'.$flPart), $flPart, $fVal, $arSettings, $arElementData);
						}
						elseif(is_callable(array($arFieldClasses[$field], 'PrepareExportFieldShare')))
						{
							$fVal = $arData[$k][$field.'_'.$fieldIndex] = $arElementData[$field.'_'.$fieldIndex] = call_user_func(array($arFieldClasses[$field], 'PrepareExportFieldShare'), $field, $fVal, $arSettings, $arElementData);
						}
						if(isset($arSettings['CONVERSION']) && is_array($arSettings['CONVERSION']) && $field)
						{
							if(is_array($fVal))
							{
								foreach($fVal as $k2=>$val)
								{
									$arData[$k][$field.'_'.$fieldIndex][$k2] = $arElementData[$field.'_'.$fieldIndex][$k2] = $newVal = $this->ApplyConversions($val, $arSettings['CONVERSION'], $arElementData, ($fVal['TYPE']=='MULTICELL' ? array('KEY'=>$k2) : false));
									if($newVal===false)
									{
										if(isset($arSettings['MULTIPLE_SEPARATE_BY_ROWS']) && $arSettings['MULTIPLE_SEPARATE_BY_ROWS']=='Y')
										{
											unset($arData[$k][$field.'_'.$fieldIndex][$k2]);
										}
										else $skipLine = true;
									}
								}
							}
							else
							{
								$arData[$k][$field.'_'.$fieldIndex] = $arElementData[$field.'_'.$fieldIndex] = $newVal = $this->ApplyConversions($fVal, $arSettings['CONVERSION'], $arElementData);
								if($newVal===false) $skipLine = true;
							}
							
							if(is_array($arElementData[$field.'_'.$fieldIndex]) && isset($arElementData[$field.'_'.$fieldIndex]['TYPE']) && $arElementData[$field.'_'.$fieldIndex]['TYPE']=='MULTIROW')
							{
								$arMultiRows[$field.'_'.$fieldIndex] = $field.'_'.$fieldIndex;
							}
						}
					}

					if(isset($arSettings['INSERT_PICTURE']) && $arSettings['INSERT_PICTURE']=='Y' && $this->imagedir && $this->IsPictureField($field) && $this->params['FILE_EXTENSION']=='xlsx')
					{
						if(isset($arData[$k][$field.'_'.$fieldIndex]))
						{
							$arVals = $arData[$k][$field.'_'.$fieldIndex];
						}
						else
						{
							$arVals = $arData[$k][$field];
						}
						if(!is_array($arVals)) $arVals = array($arVals);
						$arVals2 = array();
						foreach($arVals as $key=>$val)
						{
							if($key==='TYPE') continue;
							$arFile = $this->GetFileArray($val);
							if($arFile['tmp_name'])
							{
								$maxWidth = ((int)$arSettings['PICTURE_WIDTH'] > 0 ? (int)$arSettings['PICTURE_WIDTH'] : 100);
								$maxHeight = ((int)$arSettings['PICTURE_HEIGHT'] > 0 ? (int)$arSettings['PICTURE_HEIGHT'] : 100);
								$filePath = $arFile['tmp_name'];
								$loop = 0;
								while(!\CFile::ResizeImage($arFile, array("width" => $maxWidth, "height" => $maxHeight)) && $loop < 10)
								{
									usleep(1000);
									$loop++;
								}
								if($filePath != $arFile['tmp_name'])
								{
									copy($arFile['tmp_name'], $filePath);
								}
								$arVals[$key] = substr($filePath, strlen($this->imagedir));
							}
							else
							{
								$arVals[$key] = '';
							}
							$arVals2[$key] = $this->GetFileValue($val);
						}
						
						$arVals = array_diff($arVals, array(''));
						$arVals2 = array_diff($arVals2, array(''));
						if(count($arVals) > 1)
						{
							$arData[$k][$field.'_'.$fieldIndex] = $arElementData[$field.'_'.$fieldIndex] = $arVals;
							$arData[$k][$field.'_'.$fieldIndex.'_ORIG'] = $arElementData[$field.'_'.$fieldIndex.'_ORIG'] = $arVals2;
						}
						else
						{
							$arData[$k][$field.'_'.$fieldIndex] = $arElementData[$field.'_'.$fieldIndex] = implode('', $arVals);
							$arData[$k][$field.'_'.$fieldIndex.'_ORIG'] = $arElementData[$field.'_'.$fieldIndex.'_ORIG'] = implode('', $arVals2);
						}
					}
				}
			}

			foreach($arElementData as $k2=>$val)
			{
				if(is_array($val) && (!isset($val['TYPE']) || !in_array($val['TYPE'], array('MULTICELL', 'MULTIROW'))))
				{
					if(isset($arFieldSettings[$k2]) && isset($arFieldSettings[$k2]['CHANGE_MULTIPLE_SEPARATOR']) && $arFieldSettings[$k2]['CHANGE_MULTIPLE_SEPARATOR']=='Y') $separator = $arFieldSettings[$k2]['MULTIPLE_SEPARATOR'];
					else $separator = $this->params['ELEMENT_MULTIPLE_SEPARATOR'];
					$arData[$k][$k2] = implode($separator, $val);
				}
				elseif(is_object($val))
				{
					if(is_callable(array($val, 'toString'))) $arData[$k][$k2] = $val->toString();
					else $arData[$k][$k2] = '';
				}
			}
			
			if($skipLine)
			{
				unset($arData[$k]);
				continue;
			}
			
			if(isset($this->stepparams['string_lengths']))
			{
				foreach($arFields as $fk=>$fv)
				{
					$val = isset($arElementData[$fv.'_'.$fk]) ? $arElementData[$fv.'_'.$fk] : $arElementData[$fv];
					$this->stepparams['string_lengths'][$listIndex][$fk] = max(0, (int)$this->stepparams['string_lengths'][$listIndex][$fk], strlen(is_array($val) ? serialize($val) : $val));
				}
			}
		}
		
		if(!empty($arMultiRows))
		{
			$arDataNew = array();
			foreach($arData as $k=>$v)
			{
				$arRows = array($v);
				foreach($arMultiRows as $v4)
				{
					if(is_array($arRows[0][$v4]) && isset($arRows[0][$v4]['TYPE']) && $arRows[0][$v4]['TYPE']=='MULTIROW') $arRows[0][$v4] = '';
				}
				foreach($v as $k2=>$v2)
				{
					if(is_array($v2) && isset($v2['TYPE']) && $v2['TYPE']=='MULTIROW')
					{
						$i = 0;
						foreach($v2 as $k3=>$v3)
						{
							if($k3==='TYPE') continue;
							if(!isset($arRows[$i]))
							{
								$arRows[$i] = $v;
								foreach($arMultiRows as $v4)
								{
									if(is_array($arRows[$i][$v4]) && isset($arRows[$i][$v4]['TYPE']) && $arRows[$i][$v4]['TYPE']=='MULTIROW') $arRows[$i][$v4] = '';
								}
							}
							$arRows[$i][$k2] = $v3;
							$i++;
						}
					}
				}
				$arDataNew = array_merge($arDataNew, $arRows);
			}
			$arData = $arDataNew;
		}
		
		return array(
			'FIELDS' => $arFields,
			'DATA' => $arData,
			'PAGE_COUNT' => $arResElements['navPageCount'],
			'RECORD_COUNT' => $arResElements['navRecordCount']
		);
	}
	
	public function GetFieldList($listIndex)
	{
		$arEntityFields = $this->fl->GetEntityFields();
		$availFieldKeys = array_keys($this->fl->GetEntityFieldsForFilter());
		
		$arFields = array();
		if(isset($this->arProfileFields[$listIndex]))
		{
			foreach($this->arProfileFields[$listIndex] as $k=>$v)
			{
				if(($k2 = array_search($v, $availFieldKeys))!==false) $arFields[$k] = $availFieldKeys[$k2];
				else $arFields[$k] = '';
			}
		}
		/*if(isset($this->params['FIELDS_LIST'][$listIndex]))
		{
			$arFields = $this->params['FIELDS_LIST'][$listIndex];
		}*/
		
		if(!is_array($arFields) || count($arFields)==0)
		{
			$arFields = array();
			foreach($arEntityFields as $k=>$v)
			{
				if(isset($v['items']) || isset($v['expression']) || strpos($k, 'IE_')===0 || strpos($k, 'UF_')===0 || $k=='PASSWORD') continue;
				$arFields[] = $k;
			}
			$entityDataClass = $this->fl->GetEntityClass();
			if(is_callable(array($entityDataClass, 'prepareDefaultFields')))
			{
				$entityDataClass::prepareDefaultFields($arFields);
			}
		}
		return $arFields;
	}
	
	public function GetElementsData(&$arData, $arParams)
	{
		$arFilter = $arParams['FILTER'];
		$arNavParams = (is_array($arParams['NAV_PARAMS']) ? $arParams['NAV_PARAMS'] : false);
		$arAllFields = $arParams['FIELDS'];
		
		$entityDataClass = $this->fl->GetEntityClass();
		
		$dbResCnt = 0;
		$limit = (int)$arNavParams['limit'] > 0 ? (int)$arNavParams['limit'] : 10;
		$offset = (int)$arNavParams['offset'];
		
		$arSelectField = array();
		$arSelectRelField = array();
		$arStructEntityFields = $this->fl->GetEntityFields();
		$arEntityFields = $this->fl->GetEntityFieldsForFilter();
		foreach($arAllFields as $fieldName)
		{
			if(isset($arEntityFields[$fieldName]))
			{
				if(strpos($fieldName, '.')===false)
				{
					$arSelectField[] = $fieldName;
				}
				else
				{
					$relFieldName = str_replace('.', '/', $fieldName);
					$arSelectField[$relFieldName] = $fieldName;
					$arSelectRelField[$relFieldName] = $fieldName;
				}
			}
		}
		if(!in_array('ID', $arSelectField) && array_key_exists('ID', $arEntityFields))
		{
			$arSelectField[] = 'ID';
		}

		$arFilterCnt = $arFilter;
		$arOrder = $this->GetElementOrder();
		$arElements = array();
		
		if($this->params['GROUP_BY_BASE_TABLE']!='Y' && $this->params['MERGE_CELLS_BY_BASE_TABLE']!='Y')
		{
			$arPrefixes = array_map(array(__CLASS__, 'GetFieldPrfixes'), preg_grep('/\./', array_keys($arFilterCnt)));
			foreach($arSelectField as $k=>$v)
			{
				if(strpos($v, '.')!==false)
				{
					$prefix = preg_replace('/\.[^\.]*$/', '', $v);
					if(!in_array($prefix, $arPrefixes) && (!is_callable(array($entityDataClass, 'CheckCntFilterFields')) || $entityDataClass::CheckCntFilterFields($v)))
					{
						$v2 = preg_replace('/\.[^\.]*$/', '.ID', $v);
						if(array_key_exists($v2, $arEntityFields)) $v = $v2;
						$arPrefixes[] = $prefix;
						$arFilterCnt[] = array('LOGIC'=>'OR', array($v=>false), array('!'.$v=>false));
					}
				}
			}
			
			$getListParams = array(
				'filter' => $arFilter,
				'order' => $arOrder,
				'select' => $arSelectField,
				'limit' => $limit,
				'offset' => $offset
			);

			$dbResElements = $this->GetListForClass($entityDataClass, $getListParams);
			while($arElement = $dbResElements->Fetch())
			{
				if(is_callable(array($entityDataClass, 'PrepareFieldsForExport')))
				{
					$entityDataClass::PrepareFieldsForExport($arElement, $this->params['ELEMENT_MULTIPLE_SEPARATOR']);
				}
				foreach($arElement as $k=>$v)
				{
					$arFieldParams = $arEntityFields[$k];

					if(is_array($v) && $arFieldParams['serialized']!=1)
					{
						$arVals = array();
						foreach($v as $k2=>$v2)
						{
							$arVals[] = $this->GetFieldValue($arFieldParams, $v2);
						}
						$arElement[$k] = $arVals;
					}
					else
					{
						$arElement[$k] = $this->GetFieldValue($arFieldParams, $v);
					}
				}
				$arElements[] = $arElement;
			}
		}
		else
		{
			$arVisibleColumns = $arSelectField;
			$arRels = array();
			foreach($arVisibleColumns as $col)
			{
				if(strpos($col, '.')!==false)
				{
					$arRels[] = preg_replace('/\.[^\.]*$/', '', $col);
				}
			}
			$arGroup = array();
			$arSelect = array();
			$arSelectBase = array();
			$arRelEntities = array();
			foreach($arEntityFields as $k=>$v)
			{
				if(strpos($k, '.')===false && (!isset($v['multiple_rels']) || $v['multiple_rels']!='Y'))
				{
					$arSelect[] = $k;
					$arSelectBase[] = $k;
					if(isset($v['primary']) && $v['primary'])
					{
						$arGroup[] = $k;
					}
				}
				else
				{
					$key = str_replace('.', '/', $k);
					$base = preg_replace('/\.[^\.]*$/', '', $k);
					if(in_array($k, $arVisibleColumns) || (in_array($base, $arRels) && isset($v['primary']) && $v['primary']))
					{
						
						$arSelect[$key] = $k;
						if(!isset($arRelEntities[$base]))
						{
							$arRelEntities[$base] = array('primaries'=>array(), 'fields'=>array(), 'singles'=>array());
						}
						if(isset($v['primary']) && $v['primary'])
						{
							$arRelEntities[$base]['primaries'][] = $key;
						}
						$arRelEntities[$base]['fields'][] = $key;
						if(array_key_exists($k, $arStructEntityFields) && (!isset($arStructEntityFields[$k]['multiple_rels']) || $arStructEntityFields[$k]['multiple_rels']!='Y')) $arRelEntities[$base]['singles'][] = $key;
					}
				}
			}
			
			$getListParams = array(
				'filter' => $arFilter,
				'order' => $arOrder,
				'select' => $arSelect,
				'limit' => $limit,
				'offset' => $offset
			);

			$arGroup2 = $arGroup;
			foreach($getListParams['order'] as $k=>$v)
			{
				if(!in_array($k, $arGroup))
				{
					if(strpos($k, '.')===false)
					{
						$arGroup2[] = $k;
					}
					else unset($getListParams['order'][$k]);
				}
				foreach($arGroup as $k)
				{
					if(!isset($getListParams['order'][$k])) $getListParams['order'][$k] = 'ASC';
				}
			}
			
			$dbRes = $this->GetListForClass($entityDataClass, array_merge($getListParams, array('select'=>array_merge($arGroup2, $arSelectBase))));

			$arElements = array();
			$arFilter2 = array();
			if($dbRes->getSelectedRowsCount() > 0)
			{
				foreach($arFilter as $k=>$v)
				{
					if(strpos($k, '.')!==false) $arFilter2[$k] = $v;
				}
			}
			while($arRecord = $dbRes->Fetch())
			{
				if(count($arGroup) > 1)
				{
					$arFilter2['LOGIC'] = 'OR';
					$arFilterPart = array();
					foreach($arGroup as $key)
					{
						$arFilterPart[$key] = $arRecord[$key];
					}
					$arFilter2[] = $arFilterPart;
				}
				elseif(count($arGroup) == 1)
				{
					foreach($arGroup as $key)
					{
						if(!isset($arFilter2[$key])) $arFilter2[$key] = array();
						$arFilter2[$key][] = $arRecord[$key];
					}
				}
				
				if(1)
				{
					if(is_callable(array($entityDataClass, 'PrepareFieldsForExport')))
					{
						$entityDataClass::PrepareFieldsForExport($arElement, $this->params['ELEMENT_MULTIPLE_SEPARATOR']);
					}
					foreach($arRecord as $key=>$val)
					{
						$key2 = str_replace('/', '.', $key);
						$arFieldParams = (isset($arEntityFields[$key2]) ? $arEntityFields[$key2] : array());
						if(is_array($val))
						{
							$arVals = array();
							foreach($val as $k2=>$v2)
							{
								$arVals[] = $this->GetFieldValue($arFieldParams, $v2);
							}
							$arRecord[$key] = $arVals;
						}
						else
						{
							$arRecord[$key] = $this->GetFieldValue($arFieldParams, $val);
						}
					}
					$arKeys = array();
					foreach($arGroup as $key)
					{
						$arKeys[$key] = $arRecord[$key];
					}
					$dataKey = serialize($arKeys);
					if(!isset($arElements[$dataKey]))
					{
						$arElements[$dataKey] = $arRecord;
					}
				}
			}

			if(!empty($arFilter2))
			{
				//$dbRes = $entityDataClass::getList(array_merge($getListParams, array('filter'=>$arFilter2, 'limit'=>null, 'offset'=>null)));
				$dbRes = $this->GetListForClass($entityDataClass, array_merge($getListParams, array('filter'=>$arFilter2, 'limit'=>null, 'offset'=>null)));
				while($arRecord = $dbRes->Fetch())
				{
					if(is_callable(array($entityDataClass, 'PrepareFieldsForExport')))
					{
						$entityDataClass::PrepareFieldsForExport($arRecord, $this->params['ELEMENT_MULTIPLE_SEPARATOR']);
					}
					foreach($arRecord as $key=>$val)
					{
						$key2 = str_replace('/', '.', $key);
						$arFieldParams = (isset($arEntityFields[$key2]) ? $arEntityFields[$key2] : array());
						if(is_array($val))
						{
							$arVals = array();
							foreach($val as $k2=>$v2)
							{
								$arVals[] = $this->GetFieldValue($arFieldParams, $v2);
							}
							$arRecord[$key] = $arVals;
						}
						else
						{
							$arRecord[$key] = $this->GetFieldValue($arFieldParams, $val);
						}
					}
					
					$arKeys = array();
					foreach($arGroup as $key)
					{
						$arKeys[$key] = $arRecord[$key];
					}
					$dataKey = serialize($arKeys);
					if(!isset($arElements[$dataKey]))
					{
						$arElements[$dataKey] = $arRecord;
					}
					else
					{
						foreach($arRelEntities as $relEntity)
						{
							$needAdd = false;
							if(count($relEntity['primaries'])==0) $needAdd = true;
							else
							{
								foreach($relEntity['primaries'] as $prField)
								{
									if(!isset($arElements[$dataKey][$prField]))
									{
										foreach($relEntity['fields'] as $rField)
										{
											$arElements[$dataKey][$rField] = $arRecord[$rField];
										}
									}
									elseif((is_array($arElements[$dataKey][$prField]) && (is_array($arRecord[$prField]) || !in_array($arRecord[$prField], $arElements[$dataKey][$prField]))) 
										|| (!is_array($arElements[$dataKey][$prField]) && $arElements[$dataKey][$prField]!=$arRecord[$prField])) 
									{
										$needAdd = true;
									}
								}
							}
							if($needAdd)
							{
								foreach($relEntity['fields'] as $rField)
								{
									if(in_array($rField, $relEntity['singles']))
									{
										$arElements[$dataKey][$rField] = $arRecord[$rField];
										continue;
									}
									if(!isset($arElements[$dataKey][$rField]))
									{
										if($this->params['MERGE_CELLS_BY_BASE_TABLE']=='Y') $arElements[$dataKey][$rField] = array('TYPE' => 'MULTICELL');
										else $arElements[$dataKey][$rField] = array();
									}
									elseif(!is_array($arElements[$dataKey][$rField]))
									{
										if($this->params['MERGE_CELLS_BY_BASE_TABLE']=='Y') $arElements[$dataKey][$rField] = array('TYPE'=>'MULTICELL', $arElements[$dataKey][$rField]);
										else $arElements[$dataKey][$rField] = array($arElements[$dataKey][$rField]);
									}
									$arElements[$dataKey][$rField][] = $arRecord[$rField];
								}
							}
						}
					}
				}

				foreach($arElements as $ekey=>$arElement)
				{
					foreach($arSelectRelField as $k=>$v)
					{
						if(array_key_exists($k, $arElement) && is_array($arElement[$k]) && count($arElement[$k]) > 0 && count(array_unique($arElement[$k]))==1)
						{
							$arElements[$ekey][$k] = current($arElement[$k]);
						}
					}	
				}
			}
		}
		
		foreach($arElements as $arElement)
		{
			foreach($arSelectRelField as $k=>$v)
			{
				if(array_key_exists($k, $arElement))
				{
					$arElement[$v] = $arElement[$k];
					unset($arElement[$k]);
				}
			}			
			$arData[] = $arElement;
			$dbResCnt++;

			if($arParams['NAV_PARAMS']['nTopCount'] && $dbResCnt >= $arParams['NAV_PARAMS']['nTopCount'])
			{
				$break = true;
				break;
			}
		}
		
		$navRecordCount = $entityDataClass::getCount($arFilterCnt);
		$navPageCount = ceil($navRecordCount / $limit);
		
		if($dbResCnt > $navRecordCount) $navRecordCount = $dbResCnt;
		return array(
			'navRecordCount' => $navRecordCount,
			'navPageCount' => $navPageCount
		);
	}
	
	public function GetElementOrder()
	{
		$arEntityFields = $this->fl->GetEntityFieldsForFilter();
		$listIndex = $this->listIndex;
		$arOrder = array();
		$arSort = array_map('trim', explode('=>', $this->params['SORT'][$listIndex]));
		if($arSort[0])
		{
			$sortField = $arSort[0];
			$sortOrder = (ToUpper($arSort[1])=='DESC' ? 'DESC' : 'ASC');
			if(array_key_exists($sortField, $arEntityFields))
			{
				$arOrder[$sortField] = $sortOrder;
			}
		}
		$arPrimaries = array();
		foreach($arEntityFields as $k=>$v)
		{
			if($v['primary'] && strpos($k, '.')===false)
			{
				$arPrimaries[] = $k;
			}
		}
		foreach($arEntityFields as $k)
		{
			if(is_array($k)) continue;
			if(!isset($arOrder[$k]))
			{
				$arOrder[$k] = 'ASC';
			}
		}
		return $arOrder;
	}
	
	public function GetDomain()
	{
		if(!$this->domain)
		{
			$host = '';
			if($arSite = \Bitrix\Main\SiteTable::getList(array('filter'=>array('DEF'=>'Y')))->Fetch())
			{
				$host = $arSite['SERVER_NAME'];
			}
			if(!$host && $_SERVER['HTTP_HOST']) $host = $_SERVER['HTTP_HOST'];
			$this->domain = $host;
		}
		return $this->domain;
	}
	
	public function GetListForClass($entityDataClass, $getListParams)
	{
		if(method_exists('\Bitrix\Main\ORM\Query\Query', 'enablePrivateFields')) $getListParams['private_fields'] = true;
		try{
			$dbResElements = $entityDataClass::GetList($getListParams);
		}catch(\Exception $ex){
			if(is_callable(array('\Bitrix\Main\Diag\ExceptionHandlerFormatter', 'format')) && ($errorText = \Bitrix\Main\Diag\ExceptionHandlerFormatter::format($ex)))
			{
				if(preg_match('/Unknown parameter:\s*private_fields/i', $errorText))
				{
					unset($getListParams['private_fields']);
					return $entityDataClass::GetList($getListParams);
				}
			}
			throw new \Exception($ex->getMessage());
		}
		return $dbResElements;
	}
	
	public function IsPictureField($field)
	{
		return $this->fl->IsPictureField($field);
	}
	
	public function IsMultipleField($field)
	{
		$isMultiple = false;
		if(in_array($field, array('IE_SECTION_PATH'))) $isMultiple = true;
		return $isMultiple;
	}
	
	public function GetNumberOperation(&$val, $op)
	{
		if($op=='eq') return '=';
		elseif($op=='gt') return '>';
		elseif($op=='geq') return '>=';
		elseif($op=='lt') return '<';
		elseif($op=='leq') return '<=';
		elseif($op=='from_to')
		{
			$val = array_map('trim', explode('-', $val));
			return '><';
		}
		elseif($op=='empty')
		{
			$val = false;
			return '';
		}
		else return '';
	}
	
	public function GetStringOperation(&$val, $op)
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
	
	public function GetCalculatedValue($val)
	{
		try{
			if($this->params['ELEMENT_NOT_LOAD_FORMATTING']=='Y') $val = $val->getCalculatedValue();
			else $val = $val->getFormattedValue();
		}catch(\Exception $ex){}
		return self::CorrectCalculatedValue($val);
	}
	
	public static function CorrectCalculatedValue($val)
	{
		$val = str_ireplace('_x000D_', '', $val);
		if(!\Bitrix\EsolAie\Utils::IsUtfMode() && \CUtil::DetectUTF8($val)/*function_exists('mb_detect_encoding') && (mb_detect_encoding($val) == 'UTF-8')*/)
		{
			$val = strtr($val, array('Ø'=>'&#216;', '™'=>'&#153;', '®'=>'&#174;', '©'=>'&#169;'));
			$val = \Bitrix\Main\Text\Encoding::convertEncoding($val, "UTF-8", "Windows-1251");
		}
		return $val;
	}
	
	public function GetFloatVal($val)
	{
		return floatval(preg_replace('/[^\d\.]+/', '', str_replace(',', '.', $val)));
	}
	
	public function GetDateVal($val)
	{
		$time = strtotime($val);
		if($time > 0)
		{
			return ConvertTimeStamp($time, 'FULL');
		}
		return false;
	}
	
	public function AddDateFilter(&$arFilter, $arAddFilter, $field, $addField)
	{
		/*if(isset($arAddFilter[$addField.'_from_FILTER_PERIOD']) && in_array($arAddFilter[$addField.'from_FILTER_PERIOD'], array('day', 'week', 'month', 'quarter', 'year'))
			&& isset($arAddFilter[$addField.'_from_FILTER_DIRECTION']) && in_array($arAddFilter[$addField.'from_FILTER_PERIOD'], array('previous', 'current', 'next')))
		{}*/
		if($arAddFilter[$addField.'_from_FILTER_PERIOD']=='last_days'
			&& isset($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']) && strlen(trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS'])) > 0)
		{
			$days = (int)trim($arAddFilter[$addField.'_from_FILTER_LAST_DAYS']);
			$arFilter['>='.$field] = ConvertTimeStamp(time()-$days*24*60*60, "FULL");
		}
		else
		{
			Loader::includeModule("iblock");
			if(in_array($arAddFilter[$addField.'_from_FILTER_PERIOD'], array('day', 'week', 'month', 'quarter', 'year')) && in_array($arAddFilter[$addField.'_from_FILTER_DIRECTION'], array('previous', 'current', 'next')))
			{
				$time = time();
				$d1 = $d2 = (int)date('j', $time);
				$m1 = $m2 = (int)date('n', $time);
				$y1 = $y2 = (int)date('Y', $time);
				$x1 = $x2 = false;
				$ratio = 1;
				if($arAddFilter[$addField.'_from_FILTER_PERIOD']=='day')
				{
					$x1 = &$d1;
					$x2 = &$d2;
				}
				elseif($arAddFilter[$addField.'_from_FILTER_PERIOD']=='week')
				{
					$x1 = &$d1;
					$x2 = &$d2;
					$ratio = 7;
					$x1 = $x1 - (int)date('N', $time) + 1;
					$x2 = $x2 - (int)date('N', $time) + 7;
				}
				elseif($arAddFilter[$addField.'_from_FILTER_PERIOD']=='month')
				{
					$x1 = &$m1;
					$x2 = &$m2;
					$x2 = $x2 + 1;
					$d1 = 1;
					$d2 = 0;
				}
				elseif($arAddFilter[$addField.'_from_FILTER_PERIOD']=='quarter')
				{
					$x1 = &$m1;
					$x2 = &$m2;
					$ratio = 3;
					$q = ceil($x1/3);
					$x1 = ($q-1)*3 + 1;
					$x2 = ($q-1)*3 + 4;
					$d1 = 1;
					$d2 = 0;
				}
				elseif($arAddFilter[$addField.'_from_FILTER_PERIOD']=='year')
				{
					$x1 = &$y1;
					$x2 = &$y2;
					$d1 = 1;
					$d2 = 31;
					$m1 = 1;
					$m2 = 12;
				}
				if($arAddFilter[$addField.'_from_FILTER_DIRECTION']=='previous') {$x1 = $x1 - $ratio; $x2 = $x2 - $ratio;}
				elseif($arAddFilter[$addField.'_from_FILTER_DIRECTION']=='next') {$x1 = $x1 + $ratio; $x2 = $x2 + $ratio;}
				if($x1!==false)
				{
					$arAddFilter[$addField.'_from'] = ConvertTimeStamp(mktime(0, 0, 0, $m1, $d1, $y1), "PART");
					$arAddFilter[$addField.'_to'] = ConvertTimeStamp(mktime(0, 0, 0, $m2, $d2, $y2), "PART");
				}
			}
			if(!empty($arAddFilter[$addField.'_from'])) $arFilter['>='.$field] = $arAddFilter[$addField.'_from'];
			if(!empty($arAddFilter[$addField.'_to'])) $arFilter['<='.$field] = \CIBlock::isShortDate($arAddFilter[$addField.'_to'])? ConvertTimeStamp(AddTime(MakeTimeStamp($arAddFilter[$addField.'_to']), 1, "D"), "FULL"): $arAddFilter[$addField.'_to'];
		}
	}
	
	public function GetDefaultSite()
	{
		if(!isset($this->defaultSite) || !is_array($this->defaultSite))
		{
			if(!($arSite = \CSite::GetList(($by='sort'), ($order='asc'), array('DEFAULT'=>'Y'))->Fetch()))
				$arSite = \CSite::GetList(($by='sort'), ($order='asc'), array())->Fetch();
			$this->defaultSite = (is_array($arSite) ? $arSite : array());
		}
		return $this->defaultSite;
	}
	
	public function GetDefaultSiteId()
	{
		$arSite = $this->GetDefaultSite();
		return $arSite['ID'];
	}
	
	public function Str2Url($string, $arParams=array())
	{
		if(!is_array($arParams)) $arParams = array();
		if($arParams['TRANSLITERATION']=='Y')
		{
			if(isset($arParams['TRANS_LEN'])) $arParams['max_len'] = $arParams['TRANS_LEN'];
			if(isset($arParams['TRANS_CASE'])) $arParams['change_case'] = $arParams['TRANS_CASE'];
			if(isset($arParams['TRANS_SPACE'])) $arParams['replace_space'] = $arParams['TRANS_SPACE'];
			if(isset($arParams['TRANS_OTHER'])) $arParams['replace_other'] = $arParams['TRANS_OTHER'];
			if(isset($arParams['TRANS_EAT']) && $arParams['TRANS_EAT']=='N') $arParams['delete_repeat_replace'] = false;
		}
		return \CUtil::translit($string, LANGUAGE_ID, $arParams);
	}
	
	public static function GetDateFromPath($m)
	{
		return date($m[1]);
	}
	
	public static function GetFieldPrfixes($n)
	{
		return preg_replace("/(^[<>!%=?]*|\.[^\.]*$)/", "", $n);
	}
}
?>