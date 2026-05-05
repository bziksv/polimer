<?php
namespace Bitrix\EsolAie\Import;
require_once(dirname(__FILE__).'/../PHPExcel/PHPExcel.php');
use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Importer {
	protected static $cpSpecCharLetters = null;
	protected static $moduleId = 'esol.allimportexport';
	protected static $moduleSubDir = 'import/';
	protected static $instances = array();
	var $notHaveTimeSetWorksheet = false;
	var $titlesRow = false;
	var $arTmpImageDirs = array();
	var $extraConvParams = array();
	
	public static function getInstance($entity='false')
	{
		if (!isset(static::$instances[$entity]))
			static::$instances[$entity] = new static($entity);

		return static::$instances[$entity];
	}
	
	public function __construct($entity)
	{
		$this->fl = FieldList::getInstance($entity);
		$this->profile = Profile::getInstance($entity);
	}
	
	function setParams($filename, $params, $fparams, $stepparams, $pid = false)
	{
		$this->fl->setProfileParams($params);
		$this->filename = $_SERVER['DOCUMENT_ROOT'].$filename;
		$this->params = $params;
		$this->fparams = $fparams;
		$this->maxReadRows = 500;
		$this->skipRows = 0;
		$this->errors = array();
		$this->breakWorksheet = false;
		$this->stepparams = $stepparams;
		$this->stepparams['total_read_line'] = intval($this->stepparams['total_read_line']);
		$this->stepparams['total_line'] = intval($this->stepparams['total_line']);
		$this->stepparams['correct_line'] = intval($this->stepparams['correct_line']);
		$this->stepparams['error_line'] = intval($this->stepparams['error_line']);
		$this->stepparams['element_added_line'] = intval($this->stepparams['element_added_line']);
		$this->stepparams['element_updated_line'] = intval($this->stepparams['element_updated_line']);
		$this->stepparams['element_removed_line'] = intval($this->stepparams['element_removed_line']);
		$this->stepparams['element_last_id'] = (isset($this->stepparams['element_last_id']) ? $this->stepparams['element_last_id'] : 0);
		$this->stepparams['worksheetCurrentRow'] = intval($this->stepparams['worksheetCurrentRow']);
		if(!isset($this->stepparams['total_line_by_list'])) $this->stepparams['total_line_by_list'] = array();
		$this->stepparams['total_file_line'] = 0;
		if(is_array($this->params['LIST_LINES']))
		{
			foreach($this->params['LIST_ACTIVE'] as $k=>$v)
			{
				if($v=='Y')
				{
					$this->stepparams['total_file_line'] += $this->params['LIST_LINES'][$k];
				}
			}
		}
	
		//$this->cloud = new \Bitrix\KdaImportexcel\Cloud();
		$this->sftp = new \Bitrix\EsolAie\Sftp();
		
		$this->useProxy = false;
		$this->proxySettings = array(
			'proxyHost' => \Bitrix\Main\Config\Option::get(static::$moduleId, 'PROXY_HOST', ''),
			'proxyPort' => \Bitrix\Main\Config\Option::get(static::$moduleId, 'PROXY_PORT', ''),
			'proxyUser' => \Bitrix\Main\Config\Option::get(static::$moduleId, 'PROXY_USER', ''),
			'proxyPassword' => \Bitrix\Main\Config\Option::get(static::$moduleId, 'PROXY_PASSWORD', ''),
		);
		if($this->proxySettings['proxyHost'] && $this->proxySettings['proxyPort'])
		{
			$this->useProxy = true;
		}
		
		$this->SetZipClass();
		
		/*Temp folders*/
		$this->filecnt = 0;
		$dir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/'.static::$moduleSubDir;
		CheckDirPath($dir);
		if(!$this->stepparams['tmpdir'])
		{
			$i = 0;
			while(($tmpdir = $dir.$i.'/') && file_exists($tmpdir)){$i++;}
			$this->stepparams['tmpdir'] = $tmpdir;
			CheckDirPath($tmpdir);
		}
		$this->tmpdir = $this->stepparams['tmpdir'];
		$this->imagedir = $this->stepparams['tmpdir'].'images/';
		CheckDirPath($this->imagedir);
		$this->archivedir = $this->stepparams['tmpdir'].'archives/';
		CheckDirPath($this->archivedir);
		
		$this->tmpfile = $this->tmpdir.'params.txt';
		$this->profile->SetImportParams($pid, $stepparams);
		/*/Temp folders*/
		
		if(file_exists($this->tmpfile) && filesize($this->tmpfile) > 0)
		{
			$this->stepparams = array_merge($this->stepparams, \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($this->tmpfile)));
		}
		
		if(!isset($this->stepparams['curstep'])) $this->stepparams['curstep'] = 'import';
		
		if(!isset($this->params['MAX_EXECUTION_TIME']) || $this->params['MAX_EXECUTION_TIME']!==0)
		{
			if(\Bitrix\Main\Config\Option::get(static::$moduleId, 'SET_MAX_EXECUTION_TIME')=='Y' && is_numeric(\Bitrix\Main\Config\Option::get(static::$moduleId, 'MAX_EXECUTION_TIME')))
			{
				$this->params['MAX_EXECUTION_TIME'] = intval(\Bitrix\Main\Config\Option::get(static::$moduleId, 'MAX_EXECUTION_TIME'));
				if(ini_get('max_execution_time') && $this->params['MAX_EXECUTION_TIME'] > ini_get('max_execution_time') - 5) $this->params['MAX_EXECUTION_TIME'] = ini_get('max_execution_time') - 5;
				if($this->params['MAX_EXECUTION_TIME'] < 5) $this->params['MAX_EXECUTION_TIME'] = 5;
				if($this->params['MAX_EXECUTION_TIME'] > 300) $this->params['MAX_EXECUTION_TIME'] = 300;
			}
			else
			{
				$this->params['MAX_EXECUTION_TIME'] = intval(ini_get('max_execution_time')) - 10;
				if($this->params['MAX_EXECUTION_TIME'] < 10) $this->params['MAX_EXECUTION_TIME'] = 15;
				if($this->params['MAX_EXECUTION_TIME'] > 50) $this->params['MAX_EXECUTION_TIME'] = 50;
			}
		}
		if($this->params['ONLY_UPDATE_MODE']=='Y')
		{
			$this->params['ONLY_UPDATE_MODE_ELEMENT'] = $this->params['ONLY_UPDATE_MODE_SECTION'] = 'Y';
		}
		if($this->params['ONLY_CREATE_MODE']=='Y')
		{
			$this->params['ONLY_CREATE_MODE_ELEMENT'] = $this->params['ONLY_CREATE_MODE_SECTION'] = 'Y';
		}
		
		$pattern = '/(#FILENAME#|#IMPORT_PROCESS_ID#)/';
		foreach($this->fparams as $k=>$listParams)
		{
			foreach($listParams as $k2=>$ffilter)
			{
				if(isset($ffilter['UPLOAD_VALUES']) && is_array($ffilter['UPLOAD_VALUES']))
				{
					foreach($ffilter['UPLOAD_VALUES'] as $k3=>$val)
					{
						$this->fparams[$k][$k2]['UPLOAD_VALUES'][$k3] = preg_replace_callback($pattern, array($this, 'ConversionReplaceValues'), $val);
					}
				}
				if(isset($ffilter['NOT_UPLOAD_VALUES']) && is_array($ffilter['NOT_UPLOAD_VALUES']))
				{
					foreach($ffilter['NOT_UPLOAD_VALUES'] as $k3=>$val)
					{
						$this->fparams[$k][$k2]['NOT_UPLOAD_VALUES'][$k3] = preg_replace_callback($pattern, array($this, 'ConversionReplaceValues'), $val);
					}
				}
			}
		}
		
		if($pid!==false)
		{
			$this->procfile = $dir.$pid.'.txt';
			$this->errorfile = $dir.$pid.'_error.txt';
			if((int)$this->stepparams['import_started'] < 1)
			{
				$this->profile->OnStartImport();
				
				if(file_exists($this->procfile)) unlink($this->procfile);
				if(file_exists($this->errorfile)) unlink($this->errorfile);
			}
			$this->pid = $pid;
		}
	}
	
	public function SetZipClass()
	{
		if(/*$this->params['OPTIMIZE_RAM']!='Y' &&*/ !isset($this->stepparams['optimizeRam']))
		{
			$this->stepparams['optimizeRam'] = 'N';
			$origFileSize = filesize($this->filename);
			if((true /*class_exists('\XMLReader') && $origFileSize > 2*1024*1024*/) && ToLower(Utils::GetFileExtension($this->filename))=='xlsx')
			{
				$timeBegin = microtime(true);
				$needSize = $origFileSize*10;
				$tempPath = \CFile::GetTempName('', 'test_size.txt');
				CheckDirPath($tempPath);

				$fileSize = 0;
				$handle = fopen($tempPath, 'a');
				while($fileSize < $needSize && microtime(true) - $timeBegin < 3)
				{
					$partSize = min(5*1024*1024, $needSize - $fileSize);
					fwrite($handle, str_repeat('0', $partSize));
					$fileSize += $partSize;
				}
				fclose($handle);
				if($fileSize <= filesize($tempPath))
				{
					$this->stepparams['optimizeRam'] = 'Y';
				}
				unlink($tempPath);
				$dir = dirname($tempPath);
				if(count(array_diff(scandir($dir), array('.', '..')))==0)
				{
					rmdir($dir);
				}
			}
		}
		if($this->params['OPTIMIZE_RAM']=='Y' || $this->stepparams['optimizeRam']=='Y')
		{
			\KDAPHPExcel_Settings::setZipClass(\KDAPHPExcel_Settings::KDAIEZIPARCHIVE);
		}
	}
	
	public function CheckTimeEnding($time = 0)
	{
		if($time==0) $time = $this->timeBeginImport;
		return ($this->params['MAX_EXECUTION_TIME'] && (time()-$time >= $this->params['MAX_EXECUTION_TIME']));
	}
	
	public function GetRemainingTime()
	{
		if(!$this->params['MAX_EXECUTION_TIME']) return 600;
		else return ($this->params['MAX_EXECUTION_TIME'] - (time() - $this->timeBeginImport));
	}
	
	public function HaveTimeSetWorksheet($time)
	{
		$this->notHaveTimeSetWorksheet = ($this->params['MAX_EXECUTION_TIME'] && $this->params['TIME_READ_FILE'] && (time()-$time+$this->params['TIME_READ_FILE'] >= $this->params['MAX_EXECUTION_TIME']));
		return !$this->notHaveTimeSetWorksheet;
	}
	
	public function Import()
	{
		register_shutdown_function(array($this, 'OnShutdown'));
		set_error_handler(array($this, "HandleError"));
		set_exception_handler(array($this, "HandleException"));
		$this->stepparams['import_started'] = 1;
		$this->SaveStatusImport();
		
		$time = $this->timeBeginImport = $this->timeBeginTagCache = time();
		if($this->stepparams['curstep'] == 'import')
		{
			$this->InitImport();
			while($arItem = $this->GetNextRecord($time))
			{
				if(is_array($arItem)) $this->SaveRecord($arItem);
				if($this->CheckTimeEnding($time))
				{
					return $this->GetBreakParams();
				}
			}
			if($this->CheckTimeEnding($time) || $this->notHaveTimeSetWorksheet) return $this->GetBreakParams();
			$this->stepparams['curstep'] = 'import_end';
		}
		
		return $this->EndOfLoading($time);
	}
	
	public function EndOfLoading($time)
	{
		$this->SaveStatusImport(true);
		
		$entityDataClass = $this->fl->GetEntityClass();
		if(is_callable(array($entityDataClass, 'AfterSaveHandler')))
		{
			$entityDataClass::AfterSaveHandler($this->stepparams);
		}
		
		$arEventData = $this->profile->OnEndImport($this->filename, $this->stepparams);
		
		foreach(GetModuleEvents(static::$moduleId, "OnEndImport", true) as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array($this->pid, $arEventData));
		}
		ZipArchive::RemoveFileDir($this->filename);
		
		return $this->GetBreakParams('finish');
	}	
	
	public function InitImport()
	{
		$this->objReader = \KDAPHPExcel_IOFactory::createReaderForFile($this->filename);
		$this->worksheetNames = array();
		if(is_callable(array($this->objReader, 'listWorksheetNames')))
		{
			$this->worksheetNames = $this->objReader->listWorksheetNames($this->filename);
		}		
		if($this->params['ELEMENT_NOT_LOAD_STYLES']=='Y' && $this->params['ELEMENT_NOT_LOAD_FORMATTING']=='Y')
		{
			$this->objReader->setReadDataOnly(true);
		}
		if(isset($this->params['CSV_PARAMS']))
		{
			$this->objReader->setCsvParams($this->params['CSV_PARAMS']);
		}
		$this->chunkFilter = new KDAChunkReadFilter();
		$this->objReader->setReadFilter($this->chunkFilter);
		
		$this->worksheetNum = (isset($this->stepparams['worksheetNum']) ? intval($this->stepparams['worksheetNum']) : 0);
		$this->worksheetCurrentRow = intval($this->stepparams['worksheetCurrentRow']);
		$this->GetNextWorksheetNum();
	}
	
	public function GetBreakParams($action = 'continue')
	{
		$arStepParams = array(
			'params' => $this->GetStepParams(),
			'action' => $action,
			'errors' => $this->errors,
			'sessid' => bitrix_sessid()
		);
		
		if($action == 'continue')
		{
			file_put_contents($this->tmpfile, serialize($arStepParams['params']));
			if(file_exists($this->imagedir))
			{
				DeleteDirFilesEx(substr($this->imagedir, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
		}
		else
		{
			if(file_exists($this->procfile)) unlink($this->procfile);
			if(file_exists($this->tmpdir)) DeleteDirFilesEx(substr($this->tmpdir, strlen($_SERVER['DOCUMENT_ROOT'])));
		}
		
		unset($arStepParams['params']['currentelement']);
		unset($arStepParams['params']['currentelementitem']);
		return $arStepParams;
	}
	
	public function GetStepParams()
	{
		return array_merge($this->stepparams, array(
			'worksheetNum' => intval($this->worksheetNum),
			'worksheetCurrentRow' => $this->worksheetCurrentRow
		));
	}
	
	public function SetWorksheet($worksheetNum, $worksheetCurrentRow)
	{
		$this->skipRows = 0;
		
		$timeBegin = microtime(true);
		$this->chunkFilter->setRows($worksheetCurrentRow, $this->maxReadRows);
		if($this->efile) $this->efile->__destruct();
		if($this->worksheetNames[$worksheetNum]) $this->objReader->setLoadSheetsOnly($this->worksheetNames[$worksheetNum]);
		if($this->stepparams['csv_position'] && is_callable(array($this->objReader, 'setStartFilePosRow')))
		{
			$this->objReader->setStartFilePosRow($this->stepparams['csv_position']);
		}
		$this->efile = $this->objReader->load($this->filename);
		$this->worksheetIterator = $this->efile->getWorksheetIterator();
		$this->worksheet = $this->worksheetIterator->current();
		$timeEnd = microtime(true);
		$this->params['TIME_READ_FILE'] = ceil($timeEnd - $timeBegin);
		
		$this->params['CURRENT_ELEMENT_UID'] = $this->params['ELEMENT_UID'];
		$this->params['CURRENT_ELEMENT_UID_SKU'] = $this->params['ELEMENT_UID_SKU'];
		if($this->params['CHANGE_ELEMENT_UID'][$this->worksheetNum]=='Y')
		{
			$this->params['CURRENT_ELEMENT_UID'] = $this->params['LIST_ELEMENT_UID'][$this->worksheetNum];
			$this->params['CURRENT_ELEMENT_UID_SKU'] = $this->params['LIST_ELEMENT_UID_SKU'][$this->worksheetNum];
		}
		
		$filedList = $this->params['FIELDS_LIST'][$this->worksheetNum];
		
		$arEntityFields = $this->fl->GetEntityFields();
		$notSetUid = (bool)((is_array($this->params['CURRENT_ELEMENT_UID']) && count(array_diff($this->params['CURRENT_ELEMENT_UID'], $filedList)) > 0) || (!is_array($this->params['CURRENT_ELEMENT_UID']) && !in_array($this->params['CURRENT_ELEMENT_UID'], $filedList)));
		
		/*$arRequiredFields = $this->fl->GetRequiredField();
		$notSetRequired = (bool)(count(array_diff(array_keys($arRequiredFields), $filedList)) > 0);*/
		
		if($notSetUid)
		{
			if($this->worksheet->getHighestDataRow() > 0)
			{
				if($notSetUid)
				{
					$nofields = (is_array($this->params['CURRENT_ELEMENT_UID']) ? array_diff($this->params['CURRENT_ELEMENT_UID'], $filedList) : array($this->params['CURRENT_ELEMENT_UID']));
					foreach($nofields as $k=>$field)
					{
						$nofields[$k] = '"'.$arEntityFields[$field]['title'].'"';
					}
					$nofields = implode(', ', $nofields);
					$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_NOT_SET_UID"), $this->worksheetNum+1, $nofields);
				}
				/*elseif($notSetRequired)
				{
					$nofields = (array_diff(array_keys($arRequiredFields), $filedList));
					foreach($nofields as $k=>$field)
					{
						$nofields[$k] = '"'.$arEntityFields[$field]['title'].'"';
					}
					$nofields = implode(', ', $nofields);
					$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_NOT_SET_REQUIRED"), $this->worksheetNum+1, $nofields);
				}*/
			}
			if(!$this->GetNextWorksheetNum(true))
			{
				$this->worksheet = false;
				return false;
			}
			$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
			$this->SetWorksheet($this->worksheetNum, $pos);
			return;
		}
		
		$this->iblockId = $iblockId;
		$this->fieldSettings = array();
		$this->fieldSettingsExtra = array();
		$this->fieldOnlyNew = array();
		foreach($filedList as $k=>$field)
		{
			$fieldParams = $this->fparams[$this->worksheetNum][$k];
			if(!is_array($fieldParams)) $fieldParams = array();
			$this->fieldSettings[$field] = $fieldParams;
			if(strpos($field, '|')!==false) $this->fieldSettings[substr($field, 0, strpos($field, '|'))] = $fieldParams;
			$this->fieldSettingsExtra[$k] = $fieldParams;
			if($this->fieldSettings[$field]['SET_NEW_ONLY']=='Y')
			{
				$this->fieldOnlyNew[] = $field;
			}
		}
		
		if(!isset($this->stepparams['ELEMENT_NOT_LOAD_STYLES_ORIG']))
		{
			$this->stepparams['ELEMENT_NOT_LOAD_STYLES_ORIG'] = ($this->params['ELEMENT_NOT_LOAD_STYLES']=='Y' ? 'Y' : 'N');
		}
		else
		{
			$this->params['ELEMENT_NOT_LOAD_STYLES'] = $this->stepparams['ELEMENT_NOT_LOAD_STYLES_ORIG'];
		}
		$listSettings = $this->params['LIST_SETTINGS'][$this->worksheetNum];
		$this->titlesRow = (isset($listSettings['SET_TITLES']) ? $listSettings['SET_TITLES'] : false);

		$maxDrawCol = 0;
		$this->draws = array();
		if($this->params['ELEMENT_LOAD_IMAGES']=='Y')
		{
			$drawCollection = $this->worksheet->getDrawingCollection();
			if($drawCollection)
			{
				$arMergedCells = array();
				$arMergedCellsPE = $this->worksheet->getMergeCells();
				if(is_array($arMergedCellsPE))
				{
					foreach($arMergedCellsPE as $coord)
					{
						list($coord1, $coord2) = explode(':', $coord, 2);
						$arCoords1 = \KDAPHPExcel_Cell::coordinateFromString($coord1);
						$arCoords2 = \KDAPHPExcel_Cell::coordinateFromString($coord2);
						$arMergedCells[$arCoords1[0]][$coord] = array($arCoords1[1], $arCoords2[1]);
						$arMergedCells[$arCoords2[0]][$coord] = array($arCoords1[1], $arCoords2[1]);
					}
				}
				
				foreach($drawCollection as $drawItem)
				{
					$coord = $drawItem->getCoordinates();
					$arPartsCoord = \KDAPHPExcel_Cell::coordinateFromString($coord);
					$maxDrawCol = max($maxDrawCol, \KDAPHPExcel_Cell::columnIndexFromString($arPartsCoord[0]));
					$arPartsCoordTo = array();
					if(is_callable(array($drawItem, 'getCoordinatesTo')) && ($coordTo = $drawItem->getCoordinatesTo()))
					{
						$arPartsCoordTo = \KDAPHPExcel_Cell::coordinateFromString($coordTo);
					}				
					$arCoords = array();
					if(!empty($arPartsCoordTo))
					{
						for($i=$arPartsCoord[1]; $i<=$arPartsCoordTo[1]; $i++)
						{
							$arCoords[] = $arPartsCoord[0].$i;
						}
					}
					if(isset($arMergedCells[$arPartsCoord[0]]) && is_array($arMergedCells[$arPartsCoord[0]]))
					{
						foreach($arMergedCells[$arPartsCoord[0]] as $range)
						{
							if($arPartsCoord[1] >= $range[0] && $arPartsCoord[1] <= $range[1])
							{
								for($i=$range[0]; $i<=$range[1]; $i++)
								{
									$arCoords[] = $arPartsCoord[0].$i;
								}
							}
						}
					}
					if(empty($arCoords)) $arCoords[] = $coord;
					foreach($arCoords as $coord)
					{
						if(is_callable(array($drawItem, 'getPath')))
						{
							$this->draws[$coord] = $drawItem->getPath();
						}
						elseif(is_callable(array($drawItem, 'getImageResource')))
						{
							$this->draws[$coord] = array(
								'IMAGE_RESOURCE' => $drawItem->getImageResource(),
								'RENDERING_FUNCTION' => $drawItem->getRenderingFunction(),
								'MIME_TYPE' => $drawItem->getMimeType(),
								'FILENAME' => $drawItem->getIndexedFilename()
							);
						}
					}
				}
			}
		}
		
		$this->useHyperlinks = false;
		$this->useNotes = false;
		foreach($this->fieldSettingsExtra as $k=>$v)
		{
			if(is_array($v['CONVERSION']))
			{
				foreach($v['CONVERSION'] as $k2=>$v2)
				{
					if(strpos($v2['TO'], '#CLINK#')!==false)
					{
						$this->useHyperlinks = true;
					}
					if(strpos($v2['TO'], '#CNOTE#')!==false)
					{
						$this->useNotes = true;
					}
				}
			}
		}
		
		$this->worksheetColumns = max(\KDAPHPExcel_Cell::columnIndexFromString($this->worksheet->getHighestDataColumn()), $maxDrawCol);
		$this->worksheetRows = min($this->maxReadRows, $this->worksheet->getHighestDataRow());
		$this->worksheetCurrentRow = $worksheetCurrentRow;
		if($this->worksheet)
		{
			$this->worksheetRows = min($worksheetCurrentRow+$this->maxReadRows, $this->worksheet->getHighestDataRow());
		}
	}
	
	public function SetFilePosition($pos, $time)
	{
		if($this->breakWorksheet)
		{
			$this->breakWorksheet = false;
			if(!$this->GetNextWorksheetNum(true)) return;
			if(!$this->HaveTimeSetWorksheet($time)) return false;
			$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
			$this->SetWorksheet($this->worksheetNum, $pos);
		}
		else
		{
			$pos = $this->GetNextLoadRow($pos, $this->worksheetNum);
			if(($pos >= $this->worksheetRows) || !$this->worksheet)
			{
				if(!$this->HaveTimeSetWorksheet($time)) return false;
				if(!$this->GetNextWorksheetNum()) return;
				$this->SetWorksheet($this->worksheetNum, $pos);
				if($this->worksheetCurrentRow > $this->worksheetRows)
				{
					if(!$this->GetNextWorksheetNum(true)) return;
					if(!$this->HaveTimeSetWorksheet($time)) return false;
					$pos = $this->GetNextLoadRow(1, $this->worksheetNum);
					$this->SetWorksheet($this->worksheetNum, $pos);
				}
				$this->SaveStatusImport();
			}
			else
			{
				$this->worksheetCurrentRow = $pos;
			}
		}
		$this->stepparams['csv_position'] = $this->chunkFilter->getFilePosRow($this->worksheetCurrentRow);
	}
	
	public function GetNextWorksheetNum($inc = false)
	{
		if($inc) $this->worksheetNum++;
		$arLists = $this->params['LIST_ACTIVE'];
		while(isset($arLists[$this->worksheetNum]) && $arLists[$this->worksheetNum]!='Y')
		{
			$this->worksheetNum++;
		}
		if(!isset($arLists[$this->worksheetNum]))
		{
			$this->worksheet = false;
			return false;
		}
		return true;
	}
	
	public function CheckSkipLine($currentRow, $worksheetNum, $checkValue = true)
	{
		$load = true;
		
		if($this->breakWorksheet ||
			(!$this->params['CHECK_ALL'][$worksheetNum] && !isset($this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1])) || 
			(isset($this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1]) && !$this->params['IMPORT_LINE'][$worksheetNum][$currentRow - 1])
			|| ($this->titlesRow!==false && $this->titlesRow==($currentRow - 1)))
		{
			$load = false;
		}
				
		if($load && !empty($this->params['ADDITIONAL_SETTINGS'][$worksheetNum]['LOADING_RANGE']))
		{
			$load = false;
			$arRanges = $this->params['ADDITIONAL_SETTINGS'][$worksheetNum]['LOADING_RANGE'];
			foreach($arRanges as $k=>$v)
			{
				$row = $currentRow;
				if(($v['FROM'] || $v['TO']) && ($row >= $v['FROM'] || !$v['FROM']) && ($row <= $v['TO'] || !$v['TO']))
				{
					$load = true;
				}
			}
		}
		
		if($load && $checkValue && is_array($this->fparams[$worksheetNum]))
		{
			foreach($this->fparams[$worksheetNum] as $k=>$v)
			{
				if(!is_array($v) || strpos($k, '__')===0) continue;
				if(is_array($v['UPLOAD_VALUES']) || is_array($v['NOT_UPLOAD_VALUES']) || $v['FILTER_EXPRESSION'])
				{
					$val = $this->worksheet->getCellByColumnAndRow($k, $currentRow);
					$valOrig = $this->GetCalculatedValue($val);
					$val = $this->ApplyConversions($valOrig, $v['CONVERSION'], array());
					if(is_array($val)) $val = array_map(array(__CLASS__, 'TrimToLower'), $val);
					else $val = ToLower(trim($val));
				}
				else
				{
					$val = '';
				}
				
				if(is_array($v['UPLOAD_VALUES']))
				{
					$subload = false;
					foreach($v['UPLOAD_VALUES'] as $needval)
					{
						$needval = ToLower(trim($needval));
						if($needval==$val
							|| (is_array($val) && in_array($needval, $val))
							|| ($needval=='{empty}' && ((!is_array($val) && strlen($val)==0) || (is_array($val) && count(array_diff(array_map('trim', $val), array('')))==0)))
							|| ($needval=='{not_empty}' && ((!is_array($val) && strlen($val) > 0) || (is_array($val) && count(array_diff(array_map('trim', $val), array(''))) > 0))))
						{
							$subload = true;
						}
					}
					$load = ($load && $subload);
				}
				
				if(is_array($v['NOT_UPLOAD_VALUES']))
				{
					$subload = true;
					foreach($v['NOT_UPLOAD_VALUES'] as $needval)
					{
						$needval = ToLower(trim($needval));
						if($needval==$val
							|| (is_array($val) && in_array($needval, $val))
							|| ($needval=='{empty}' && ((!is_array($val) && strlen($val)==0) || (is_array($val) && count(array_diff(array_map('trim', $val), array('')))==0)))
							|| ($needval=='{not_empty}' && ((!is_array($val) && strlen($val) > 0) || (is_array($val) && count(array_diff(array_map('trim', $val), array(''))) > 0))))
						{
							$subload = false;
						}
					}
					$load = ($load && $subload);
				}
				
				if($v['FILTER_EXPRESSION'])
				{
					$load = ($load && $this->ExecuteFilterExpression($valOrig, $v['FILTER_EXPRESSION']));
				}
			}
		}
		if(!$load && isset($this->stepparams['currentelement']))
		{
			unset($this->stepparams['currentelement']);
		}
		return !$load;
	}
	
	public function ExecuteFilterExpression($val, $expression, $altReturn = true, $arParams = array(), $field = false)
	{
		$expression = trim($expression);
		if(strlen($expression)==0) return $val;
		foreach($arParams as $k=>$v)
		{
			${$k} = $v;
		}
		$ret = '';
		try{				
			if(stripos($expression, 'return')===0)
			{
				$command = $expression.';';
				$ret = eval($command);
			}
			elseif(preg_match('/\$val\s*=/', $expression))
			{
				$command = $expression.';';
				eval($command);
				$ret = $val;
			}
			else
			{
				$command = 'return '.$expression.';';
				$ret = eval($command);
			}
		}catch(\Exception | \Error $ex){
			if(is_array($field) && isset($field['NAME']))
			{
				$fieldName = $field['NAME'];
				$fieldNames = $this->fl->GetFieldNames();
				$error = Loc::getMessage("ESOL_AE_PHPEXPRESSION_ERROR");
				
				if($fieldName = $fieldNames[$fieldName])
				{
					$this->errors[] = sprintf($error, $fieldName, $this->worksheetCurrentRow, $this->worksheetNumForSave+1, $ex->getMessage(), htmlspecialcharsbx($expression));
				}
			}
			$ret = $altReturn;
		}
		return $ret;
	}
	
	public function ExecuteOnAfterSaveHandler($handler, $ID)
	{
		try{				
			$command = $handler.';';
			eval($command);
		}catch(\Exception $ex){}
	}
	
	public function GetNextLoadRow($row, $worksheetNum)
	{
		$nextRow = $row;
		if(isset($this->params['LIST_ACTIVE'][$worksheetNum]))
		{
			while($this->CheckSkipLine($nextRow, $worksheetNum, false))
			{
				$nextRow++;
				if($nextRow - $row > 30000)
				{
					return $nextRow;
				}
			}
		}
		return $nextRow;
	}
	
	public function GetNextRecord($time)
	{
		if($this->SetFilePosition($this->worksheetCurrentRow + 1, $time)===false) return false;
		while($this->worksheet && $this->CheckSkipLine($this->worksheetCurrentRow, $this->worksheetNum))
		{
			if($this->CheckTimeEnding($time)) return false;
			if($this->SetFilePosition($this->worksheetCurrentRow + 1, $time)===false) return false;
		}

		if(!$this->worksheet)
		{
			return false;
		}
		
		$arItem = array();
		$this->hyperlinks = array();
		$this->notes = array();
		for($column = 0; $column < $this->worksheetColumns; $column++) 
		{
			$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
			$valText = $this->GetCalculatedValue($val);			
			$arItem[$column] = trim($valText);
			$arItem['~'.$column] = $valText;
			if($this->params['ELEMENT_NOT_LOAD_STYLES']!='Y' && !isset($arItem['STYLE']) && strlen(trim($valText))>0)
			{
				$arItem['STYLE'] = md5(\Bitrix\EsolAie\Utils::PhpToJSObject(self::GetCellStyle($val)));
			}
			if($this->params['ELEMENT_LOAD_IMAGES']=='Y')
			{
				if($this->draws[$val->getCoordinate()])
				{
					$draw = $this->draws[$val->getCoordinate()];
					if(is_array($draw) && isset($draw['RENDERING_FUNCTION']))
					{
						$tmpsubdir = $this->imagedir.($this->filecnt++).'/';
						CheckDirPath($tmpsubdir);
						if(call_user_func($draw['RENDERING_FUNCTION'], $draw['IMAGE_RESOURCE'], $tmpsubdir.$draw['FILENAME']))
						{
							$draw = substr($tmpsubdir, strlen($_SERVER["DOCUMENT_ROOT"])).$draw['FILENAME'];
						}
						else $draw = '';
					}
					$arItem['i~'.$column] = $draw;
					if(strlen(trim($arItem[$column]))==0)
					{
						$arItem[$column] = $draw;
						$arItem['~'.$column] = $draw;
					}
				}
			}
			
			if($this->useHyperlinks)
			{
				$this->hyperlinks[$column] = self::CorrectCalculatedValue($val->getHyperlink()->getUrl());
			}
			if($this->useNotes)
			{
				$comment = $this->worksheet->getCommentByColumnAndRow($column, $this->worksheetCurrentRow);
				if($comment->getImage()) $note = $comment->getImage();
				elseif(is_object($comment->getText())) $note = $comment->getText()->getPlainText();
				$this->notes[$column] = $note;
			}
		}

		$this->worksheetNumForSave = $this->worksheetNum;
		return $arItem;
	}
	
	public function SaveRecord($arItem)
	{
		$saveReadRecord = (bool)(!isset($this->stepparams['lastoffergenkey']));
		
		if($saveReadRecord) $this->stepparams['total_read_line']++;
		if(count(array_diff(array_map('trim', $arItem), array('')))==0)
		{
			$this->skipRows++;
			if((isset($this->params['ADDITIONAL_SETTINGS'][$this->worksheetNum]['BREAK_LOADING']) && $this->params['ADDITIONAL_SETTINGS'][$this->worksheetNum]['BREAK_LOADING']=='Y') || ($this->skipRows>=$this->maxReadRows - 1))
			{
				$this->breakWorksheet = true;
			}
			return false;
		}
		if($saveReadRecord)
		{
			$this->stepparams['total_line']++;
			$this->stepparams['total_line_by_list'][$this->worksheetNum]++;
		}
		
		$filedList = $this->params['FIELDS_LIST'][$this->worksheetNumForSave];
		$arEntityFields = $this->fl->GetEntityFieldsForFilter();
		$entityDataClass = $this->fl->GetEntityClass();
		$primaryField = $this->fl->GetPrimaryField();
		$this->currentItemValues = $arItem;

		$arFieldsElement = array();
		$arFieldsElementOrig = array();
		foreach($filedList as $key=>$field)
		{
			if(!array_key_exists($field, $arEntityFields)) continue;
			$k = $key;
			if(strpos($k, '_')!==false) $k = substr($k, 0, strpos($k, '_'));
			$value = $arItem[$k];
			$fs = (isset($this->fieldSettingsExtra[$key]) ? $this->fieldSettingsExtra[$key] : $this->fieldSettings[$field]);
			if($fs['NOT_TRIM']=='Y') $value = $arItem['~'.$k];
			$origValue = $arItem['~'.$k];
			
			$conversions = $fs['CONVERSION'];
			if(!empty($conversions))
			{
				$eqValues = (bool)($value===$origValue);
				$value = $this->ApplyConversions($value, $conversions, $arItem, array('KEY'=>$k, 'NAME'=>$field));
				if($eqValues) $origValue = $value;
				else $origValue = $this->ApplyConversions($origValue, $conversions, $arItem, array('KEY'=>$k, 'NAME'=>$field));
				if($value===false) continue;
			}
			
			if(strlen(is_array($value) ? implode('', $value) : $value) > 0 && isset($fs['REL_FIELD']) && strlen($fs['REL_FIELD']) > 0 && ($fs['REL_FIELD']!='ID') && ($arRelTable = $this->fl->GetRelTableByField($field, $fs['REL_FIELD'])) && is_callable(array($arRelTable['NAME'], 'getList')))
			{
				$relTblName = $arRelTable['NAME'];
				if($arRel = $relTblName::getList(array('filter'=>array('='.$fs['REL_FIELD']=>$value), 'select'=>array($arRelTable['PRIMARY'])))->fetch())
				{
					$value = $origValue = $arRel[$arRelTable['PRIMARY']];
				}
			}
			
			$arFieldsElement[$field] = $value;
			$arFieldsElementOrig[$field] = $origValue;
		}

		$arUid = array();
		if(!is_array($this->params['CURRENT_ELEMENT_UID'])) $this->params['CURRENT_ELEMENT_UID'] = array($this->params['CURRENT_ELEMENT_UID']);
		foreach($this->params['CURRENT_ELEMENT_UID'] as $uid)
		{
			$nameUid = $arEntityFields[$uid]['title'];
			$valUid = $arFieldsElementOrig[$uid];
			$valUid2 = $arFieldsElement[$uid];
			
			/*if($uid == 'ACTIVE_FROM' || $uid == 'ACTIVE_TO')
			{
				$uid = 'DATE_'.$uid;
				$valUid = $this->GetDateVal($valUid);
				$valUid2 = $this->GetDateVal($valUid2);
			}*/
			
			$arUid[] = array(
				'uid' => $uid,
				'nameUid' => $nameUid,
				'valUid' => $valUid,
				'valUid2' => $valUid2
			);
		}
		
		$emptyFields = array();
		foreach($arUid as $k=>$v)
		{
			if((is_array($v['valUid']) && count(array_diff($v['valUid'], array('')))==0)
				|| (!is_array($v['valUid']) && strlen(trim($v['valUid']))==0)) $emptyFields[] = $v['nameUid'];
		}
		
		if(!empty($emptyFields) || empty($arUid))
		{
			if($this->stepparams['element_last_id'] > 0)
			{
				$arDefKeys = array();
				foreach($arFieldsElement as $k=>$v)
				{
					if(strlen(is_array($v) ? implode('', $v) : $v) > 0) $arDefKeys[] = $k;
				}
				if(count($arDefKeys) > 0 && count(preg_grep('/\./', $arDefKeys))==count($arDefKeys))
				{
					$arFieldsElement2 = array();
					foreach($arFieldsElement as $k=>$v)
					{
						if(strpos($k, '.')!==false) $arFieldsElement2[$k] = $v;
					}
					$this->PrepareFields($arFieldsElement2, $entityDataClass);
					$entityDataClass::update($this->stepparams['element_last_id'], $arFieldsElement2);
					$this->stepparams['correct_line']++;
					return false;
				}
			}
			
			$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_NOT_SET_FIELD"), implode(', ', $emptyFields), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
			$this->stepparams['error_line']++;
			return false;
		}
		
		$arFilter = array();
		foreach($arUid as $v)
		{
			if(is_array($v['valUid'])) $arSubfilter = array_map(array($this, 'Trim'), $v['valUid']);
			else 
			{
				$arSubfilter = array($this->Trim($v['valUid']));
				if($this->Trim($v['valUid']) != $v['valUid2'])
				{
					$arSubfilter[] = $this->Trim($v['valUid2']);
					if(strlen($v['valUid2']) != strlen($this->Trim($v['valUid2'])))
					{
						$arSubfilter[] = $v['valUid2'];
					}
				}
				if(strlen($v['valUid']) != strlen($this->Trim($v['valUid'])))
				{
					$arSubfilter[] = $v['valUid'];
				}
			}
			
			if(count($arSubfilter) == 1)
			{
				$arSubfilter = $arSubfilter[0];
			}
			$arFilter['='.$v['uid']] = $arSubfilter;
		}
		
		$isError = false;
		$arKeys = array_keys($arFieldsElement);
		//if($primaryField && !in_array($primaryField, $arKeys)) $arKeys[] = $primaryField;
		if($primaryField)
		{
			$arPrimaryFields = $primaryField;
			if(!is_array($arPrimaryFields)) $arPrimaryFields = array($arPrimaryFields);
			foreach($arPrimaryFields as $pfKey)
			{
				if(!in_array($pfKey, $arKeys)) $arKeys[] = $pfKey;
			}
		}
		$this->PrepareFieldsForSearch($arKeys);
		
		$dbRes = $entityDataClass::getList(array('filter'=>$arFilter, 'select'=>$arKeys));
		while($arElement = $dbRes->Fetch())
		{
			if(is_array($primaryField))
			{
				$ID = array();
				foreach($primaryField as $pfKey)
				{
					$ID[$pfKey] = $arElement[$pfKey];
				}
			}
			else
			{
				$ID = $arElement[$primaryField];
			}
			if($this->params['ONLY_DELETE_MODE']=='Y')
			{
				if(!empty($ID))
				{
					$entityDataClass::delete($ID);
					$this->stepparams['element_removed_line']++;
				}
				continue;
			}
			
			$arFieldsElement2 = $arFieldsElement;
			if($this->params['ONLY_CREATE_MODE_ELEMENT']!='Y')
			{
				$this->UnsetUidFields($arFieldsElement2, $this->params['CURRENT_ELEMENT_UID']);
				
				if(!empty($this->fieldOnlyNew))
				{
					$this->UnsetExcessFields($this->fieldOnlyNew, $arFieldsElement2);
				}
				
				$this->PrepareFields($arFieldsElement2, $entityDataClass);
				if(empty($arFieldsElement2))
				{
					$this->stepparams['element_updated_line']++;
					continue;
				}

				if(empty($arFieldsElement2) || (($dbRes2 = $entityDataClass::update($ID, $arFieldsElement2)) && $dbRes2->isSuccess()))
				{
					$this->AfterSave($ID, $arFieldsElement);
					$this->stepparams['element_updated_line']++;
				}
				else
				{
					$isError = true;
					$this->stepparams['error_line']++;
					$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_UPDATE_ELEMENT_ERROR"), implode(', ',$dbRes2->GetErrorMessages()), $this->worksheetNumForSave+1, $this->worksheetCurrentRow, (is_array($ID) ? print_r($ID, true) : $ID));
				}
			}
		}
		
		$allowCreate = (bool)($dbRes->getSelectedRowsCount()==0 && $this->params['ONLY_DELETE_MODE']!='Y' && $this->params['ONLY_UPDATE_MODE_ELEMENT']!='Y');
		if($allowCreate)
		{
			if(is_callable(array($entityDataClass, 'PrepareFieldsForAddImport')))
			{
				$entityDataClass::PrepareFieldsForAddImport($arFieldsElement);
			}
			$arRequiredFields = $this->fl->GetRequiredField();
			$arRequiredKeys = array_diff(array_keys($arRequiredFields), array_keys($arFieldsElement));
			if(count($arRequiredKeys) > 0)
			{
				foreach($arRequiredKeys as $k=>$field)
				{
					$arRequiredKeys[$k] = '"'.$arEntityFields[$field]['title'].'"';
				}
				$arRequiredKeys = implode(', ', $arRequiredKeys);
				$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_NOT_SET_REQUIRED_ADD"), $arRequiredKeys, $this->worksheetNum+1, $this->worksheetCurrentRow);
				$isError = true;
				$allowCreate = false;
			}
		}
		
		if($allowCreate)
		{
			if($this->params['ONLY_UPDATE_MODE_ELEMENT']!='Y')
			{
				$arFieldsElement2 = $arFieldsElement;
				$this->UnsetUidFields($arFieldsElement2, $this->params['CURRENT_ELEMENT_UID'], true);

				$this->PrepareFields($arFieldsElement2, $entityDataClass);
				$dbRes2 = $entityDataClass::add($arFieldsElement2);
				if($dbRes2->isSuccess())
				{
					$ID = $dbRes2->getId();
					$this->AfterSave($ID, $arFieldsElement, true);
					$this->stepparams['element_added_line']++;
				}
				else
				{
					$this->stepparams['error_line']++;
					$this->errors[] = sprintf(Loc::getMessage("ESOL_AE_ADD_ELEMENT_ERROR"), implode(', ',$dbRes2->GetErrorMessages()), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
					$isError = true;
				}
			}
		}
		
		if(!$isError) $this->stepparams['correct_line']++;
		$this->SaveStatusImport();
		$this->RemoveTmpImageDirs();
	}
	
	public function AfterSave($ID, $arFieldsElement, $bAdd=false)
	{
		$entityDataClass = $this->fl->GetEntityClass();
		if(is_callable(array($entityDataClass, 'SaveRelatedEntities')))
		{
			$entityDataClass::SaveRelatedEntities($ID, $arFieldsElement, (bool)($this->stepparams['element_last_id']==$ID));
		}
		if(is_callable(array($entityDataClass, 'AfterSaveHandler')))
		{
			$entityDataClass::AfterSaveHandler($this->stepparams, $ID, $bAdd);
		}
		$this->stepparams['element_last_id'] = $ID;
	}
	
	public function PrepareFieldsForSearch(&$arKeys)
	{
		$entityClass = $this->fl->GetEntityClass();
		if(is_callable(array($entityClass, 'getImportExtraFields')))
		{
			$arFields = $entityClass::getImportExtraFields();
			$arKeys = array_diff($arKeys, array_keys($arFields));
		}
		$arEntityFields = $this->fl->GetEntityFields();
		foreach($arKeys as $k=>$v)
		{
			if(strpos($v, '.')!==false) unset($arKeys[$k]);
			elseif(array_key_exists($v, $arEntityFields) && is_array($arEntityFields[$v]) && array_key_exists('private', $arEntityFields[$v]) && $arEntityFields[$v]['private']) unset($arKeys[$k]);
		}
	}
	
	public function PrepareFields(&$arFieldsElement, $entityDataClass=false)
	{
		if($entityDataClass!==false && is_callable(array($entityDataClass, 'PrepareFieldsForImport')))
		{
			$entityDataClass::PrepareFieldsForImport($arFieldsElement, $this->params['ELEMENT_MULTIPLE_SEPARATOR']);
		}
		$arEntityFields = $this->fl->GetEntityFieldsForFilter();
		foreach($arFieldsElement as $k=>$v)
		{
			if(!isset($arEntityFields[$k])) continue;
			$arFieldsElement[$k] = $this->GetFieldValue($arEntityFields[$k], $v);
		}
	}
	
	public function PrepareElementPictures(&$arFieldsElement, $isOffer=false)
	{
		$arPictures = array('PREVIEW_PICTURE', 'DETAIL_PICTURE');
		foreach($arPictures as $picName)
		{
			if($arFieldsElement[$picName])
			{
				$val = $arFieldsElement[$picName];
				$arFile = $this->GetFileArray($val, array(), array('FILETYPE'=>'IMAGE'));
				if(empty($arFile) && strpos($val, $this->params['ELEMENT_MULTIPLE_SEPARATOR'])!==false)
				{
					$arVals = array_diff(array_map('trim', explode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $val)), array(''));
					if(count($arVals) > 0 && ($val = current($arVals)))
					{
						$arFile = $this->GetFileArray($val, array(), array('FILETYPE'=>'IMAGE'));
					}
				}
				$arFieldsElement[$picName] = $arFile;
			}
			if(isset($arFieldsElement[$picName.'_DESCRIPTION']))
			{
				$arFieldsElement[$picName]['description'] = $arFieldsElement[$picName.'_DESCRIPTION'];
				unset($arFieldsElement[$picName.'_DESCRIPTION']);
			}
		}
		if((isset($arFieldsElement['DETAIL_PICTURE']) && is_array($arFieldsElement['DETAIL_PICTURE'])) && (!isset($arFieldsElement['PREVIEW_PICTURE']) || !is_array($arFieldsElement['PREVIEW_PICTURE'])))
		{
			$arFieldsElement['PREVIEW_PICTURE'] = array();
		}
		
		$arTexts = array('PREVIEW_TEXT', 'DETAIL_TEXT');
		foreach($arTexts as $keyText)
		{
			if($arFieldsElement[$keyText])
			{
				if($this->fieldSettings[($isOffer ? 'OFFER_' : '').'IE_'.$keyText]['LOAD_BY_EXTLINK']=='Y')
				{
					$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>10, 'disableSslVerification'=>true));
					$path = $arFieldsElement[$keyText];
					$arUrl = parse_url($path);
					$res = $client->get($path);
					$hct = ToLower($client->getHeaders()->get('content-type'));
					$siteEncoding = Utils::getSiteEncoding();
					if(class_exists('\DOMDocument') && $arUrl['fragment'])
					{
						$doc = new \DOMDocument();
						$doc->preserveWhiteSpace = false;
						$doc->formatOutput = true;
						$doc->loadHTML($res);
						$node = $doc;
						$arParts = preg_split('/\s+/', $arUrl['fragment']);
						$i = 0;
						while(isset($arParts[$i]) && ($node instanceOf DOMDocument || $node instanceOf DOMElement))
						{
							$part = $arParts[$i];
							$tagName = (preg_match('/^([^#\.]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '');
							$tagId = (preg_match('/^[^#]*#([^#\.]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '');
							$arClasses = array_diff(explode('.', (preg_match('/^[^\.]*\.([^#]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '')), array(''));
							if($tagName)
							{
								$nodes = $node->getElementsByTagName($tagName);
								if($tagId || !empty($arClasses))
								{
									$find = false;
									$key = 0;
									while(!$find && $key<$nodes->length)
									{
										$node1 = $nodes->item($key);
										$subfind = true;
										if($tagId && $node1->getAttribute('id')!=$tagId) $subfind = false;
										foreach($arClasses as $className)
										{
											if($className && !preg_match('/(^|\s)'.preg_quote($className, '/').'(\s|$)/is', $node1->getAttribute('class'))) $subfind = false;
										}
										$find = $subfind;
										if(!$find) $key++;
									}
									if($find) $node = $nodes->item($key);
									else $node = null;
								}
								else
								{
									$node = $nodes->item(0);
								}
							}
							$i++;
						}
						if($node instanceOf DOMElement)
						{
							$innerHTML = '';
							$children = $node->childNodes;
							foreach($children as $child)
							{
								$innerHTML .= $child->ownerDocument->saveXML($child);
							}
							if(strlen($innerHTML)==0 && $node->nodeValue) $innerHTML = $node->nodeValue;
							$res = $innerHTML;
						}
						else
						{
							$res = '';
						}
						if($res && $siteEncoding!='utf-8')
						{
							$res = \Bitrix\Main\Text\Encoding::convertEncoding($res, 'utf-8', $siteEncoding);
						}
					}
					elseif(preg_match('/charset=(.+)(;|$)/Uis', $hct, $m))
					{
						$fileEncoding = ToLower(trim($m[1]));
						$siteEncoding = Utils::getSiteEncoding();
						if($siteEncoding!=$fileEncoding)
						{
							$res = \Bitrix\Main\Text\Encoding::convertEncoding($res, $fileEncoding, $siteEncoding);
						}
					}
					$arFieldsElement[$keyText] = $res;
				}
				else
				{
					$textFile = $_SERVER["DOCUMENT_ROOT"].$arFieldsElement[$keyText];
					if(file_exists($textFile) && is_file($textFile) && is_readable($textFile))
					{
						$arFieldsElement[$keyText] = file_get_contents($textFile);
					}
				}
			}
		}
	}
	
	public function GetFieldValue($arProp, $val, $multi=false)
	{
		if(!$multi && isset($arProp['uf_settings']) && is_array($arProp['uf_settings']) && $arProp['uf_settings']['MULTIPLE']=='Y')
		{
			if(is_array($val)) $arVals = $val;
			else $arVals = explode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $val);
			foreach($arVals as $k=>$v)
			{
				$arVals[$k] = $this->GetFieldValue($arProp, $v, true);
			}
			return $arVals;
		}
		
		if($arProp['data_type']=='datetime')
		{
			$val = $this->GetDateVal($val);
		}
		elseif($arProp['data_type']=='date')
		{
			$val = $this->GetDateVal($val, 'PART');
		}
		elseif($arProp['data_type']=='file')
		{
			$val = $this->GetFileArray($val);
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
			if($arProp['uf_user_type']=='datetime')
			{
				$val = $this->GetDateVal($val);
			}
			elseif($arProp['uf_user_type']=='date')
			{
				$val = $this->GetDateVal($val, 'PART');
			}
			elseif($arProp['uf_user_type']=='hlblock')
			{
				$val = $this->GetHighloadBlockValue($arProp, $val);
			}
			elseif($arProp['uf_user_type']=='boolean')
			{
				$val = $this->GetHLBoolValue($val);
			}
			elseif($arProp['uf_user_type']=='file')
			{
				$val = $this->GetFileArray($val);
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
		
		if(isset($arProp['serialized']) && $arProp['serialized'] && !is_array($val) && strlen($val) > 0)
		{
			$val = \Bitrix\EsolAie\Utils::Unserialize($val);
			if(!is_array($val)) $val = array();
		}
		
		return $val;
	}
	
	public function SaveStatusImport($end = false)
	{
		if($this->procfile)
		{
			$writeParams = $this->GetStepParams();
			$writeParams['action'] = ($end ? 'finish' : 'continue');
			file_put_contents($this->procfile, \Bitrix\EsolAie\Utils::PhpToJSObject($writeParams));
		}
	}
	
	public function UnsetUidFields(&$arFieldsElement, $arUids, $saveVal=false)
	{
		foreach($arUids as $fieldKey)
		{
			if(isset($arFieldsElement[$fieldKey]))
			{
				if(is_array($arFieldsElement[$fieldKey]))
				{
					if($saveVal)
					{
						$arFieldsElement[$fieldKey] = array_diff($arFieldsElement[$fieldKey], array(''));
						if(count($arFieldsElement[$fieldKey]) > 0) $arFieldsElement[$fieldKey] = end($arFieldsElement[$fieldKey]);
						else $arFieldsElement[$fieldKey] = '';
					}
					else unset($arFieldsElement[$fieldKey]);
				}
				elseif(!$saveVal)
				{
					unset($arFieldsElement[$fieldKey]);
				}
			}
		}
	}
	
	public function UnsetExcessFields($fieldsList, &$arFieldsElement)
	{
		foreach($fieldsList as $field)
		{
			unset($arFieldsElement[$field]);
		}
	}
	
	public function SaveElementId($ID, $type='E')
	{
		$isNew = $this->profile->SaveElementId($ID, $type);

		return $isNew;
	}
	
	public function CreateTmpImageDir()
	{
		$tmpsubdir = $this->imagedir.($this->filecnt++).'/';
		CheckDirPath($tmpsubdir);
		$this->arTmpImageDirs[] = $tmpsubdir;
		return $tmpsubdir;
	}
	
	public function RemoveTmpImageDirs()
	{
		if(!empty($this->arTmpImageDirs))
		{
			foreach($this->arTmpImageDirs as $k=>$v)
			{
				DeleteDirFilesEx(substr($v, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
			$this->arTmpImageDirs = array();
		}
	}
	
	public function GetFileArray($file, $arDef=array(), $arParams=array())
	{
		$checkSubdirs = true;
		$dirname = '';
		$fileOrig = $file = trim($file);
		$fileTypes = array();
		$bNeedImage = (bool)($arParams['FILETYPE']=='IMAGE');
		if($bNeedImage) $fileTypes = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
		elseif($arParams['FILE_TYPE']) $fileTypes = array_diff(array_map('trim', explode(',', ToLower($arParams['FILE_TYPE']))), array(''));
		
		if($file=='-')
		{
			return array('del'=>'Y');
		}
		elseif($tmpFile = $this->GetFileFromArchive($fileOrig))
		{
			$file = $tmpFile;
			if($this->PathContainsMask($file)) $dirname = $file;
		}
		elseif(strpos($file, '/')===0 || (strpos($file, '://')===false && strpos($file, '/')!==false))
		{
			$file = '/'.ltrim($file, '/');
			if($this->PathContainsMask($file) && !file_exists($file) && !file_exists($_SERVER['DOCUMENT_ROOT'].$file))
			{
				$arFiles = $this->GetFilesByMask($file);
				if($arParams['MULTIPLE']=='Y' && count($arFiles) > 1)
				{
					foreach($arFiles as $k=>$v)
					{
						$arFiles[$k] = self::GetFileArray($v, $arDef, $arParams);
					}
					return array('VALUES'=>$arFiles);
				}
				elseif(count($arFiles) > 0)
				{
					$tmpfile = current($arFiles);
					return self::GetFileArray($tmpfile, $arDef, $arParams);
				}
			}
			
			$tmpsubdir = $this->CreateTmpImageDir();
			$arFile = \CFile::MakeFileArray($file);
			/*Try search other register*/
			if(strlen($arFile['tmp_name'])==0 && !is_dir($_SERVER['DOCUMENT_ROOT'].$file))
			{
				$newFile = '';
				$fileDir = dirname($file);
				$fileName = ToLower(basename($file));
				$arFiles = scandir($_SERVER['DOCUMENT_ROOT'].$fileDir);
				foreach($arFiles as $fn)
				{
					if($fileName==ToLower($fn))
					{
						$newFile = $fileDir.'/'.$fn;
						break;
					}
				}
				if(strlen($newFile) > 0)
				{
					$file = $newFile;
					$arFile = \CFile::MakeFileArray($file);
				}
			}
			/*/Try search other register*/
			$file = $tmpsubdir.$arFile['name'];
			copy($arFile['tmp_name'], $file);
		}
		elseif(strpos($file, 'zip://')===0)
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$oldfile = $file;
			$file = $tmpsubdir.basename($oldfile);
			copy($oldfile, $file);
		}
		elseif(preg_match('/ftp(s)?:\/\//', $file))
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$arFile = $this->sftp->MakeFileArray($file);
			$file = $tmpsubdir.$arFile['name'];
			copy($arFile['tmp_name'], $file);
		}
		/*elseif($service = $this->cloud->GetService($file))
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			if($arFile = $this->cloud->MakeFileArray($service, $file))
			{
				$file = $tmpsubdir.$arFile['name'];
				copy($arFile['tmp_name'], $file);
				$checkSubdirs = 1;
			}
		}*/
		elseif(preg_match('/http(s)?:\/\//', $file))
		{
			$file = rawurldecode($file);
			$arUrl = parse_url($file);
			//Cyrillic domain
			if(preg_match('/[^A-Za-z0-9\-\.]/', $arUrl['host']))
			{
				if(!class_exists('\idna_convert')) require_once(dirname(__FILE__).'/../idna_convert.class.php');
				if(class_exists('\idna_convert'))
				{
					$idn = new \idna_convert();
					$oldHost = $arUrl['host'];
					if(!\CUtil::DetectUTF8($oldHost)) $oldHost = Utils::Win1251Utf8($oldHost);
					$file = str_replace($arUrl['host'], $idn->encode($oldHost), $file);
				}
			}
			if(class_exists('\Bitrix\Main\Web\HttpClient'))
			{
				$tmpsubdir = $this->CreateTmpImageDir();
				$baseName = bx_basename($file);
				$tempPath = $tmpsubdir.$baseName;
				$tempPath2 = $tmpsubdir.(\Bitrix\Main\IO\Path::convertLogicalToPhysical($baseName));
				$ext = ToLower(Utils::GetFileExtension($baseName));
				$arOptions = array();
				if($this->useProxy) $arOptions = $this->proxySettings;
				$arOptions['disableSslVerification'] = true;
				$maxTime = $this->GetRemainingTime();
				if($maxTime < -5) return array();
				$maxTime = max(1, min(30, $maxTime));
				$arOptions['socketTimeout'] = $arOptions['streamTimeout'] = $maxTime;
				$ob = new \Bitrix\Main\Web\HttpClient($arOptions);
				//$ob->setHeader('User-Agent', 'BitrixSM HttpClient class');
				$ob->setHeader('User-Agent', Utils::GetUserAgent());
				try{
					if(!\CUtil::DetectUTF8($file)) $file = Utils::Win1251Utf8($file);
					$file = preg_replace_callback('/[^:\/?=&#@]+/', array(__CLASS__, 'UrlDecodeCallback'), str_replace('\\', '/', $file));
					if($ob->download($file, $tempPath) && $ob->getStatus()!=404)
					{
						if(strpos($ob->getHeaders()->get('content-type'), 'text/html')===false || in_array($ext, array('.htm', '.html')))
						{
							$file = $tempPath2;
						}
						elseif($bNeedImage
							&& ($arFile = \CFile::MakeFileArray($tempPath2))
							&& stripos($arFile['type'], 'image')===false
							&& ($fileContent = file_get_contents($tempPath2))
							&& preg_match_all('/src=[\'"]([^\'"]*)[\'"]/is', $fileContent, $m))
						{
							$img = trim(current($m[1]));
							$ob = new \Bitrix\Main\Web\HttpClient($arOptions);
							$ob->setHeader('User-Agent', Utils::GetUserAgent());
							if($ob->download($img, $tempPath) 
								&& $ob->getStatus()!=404 
								&& (strpos($ob->getHeaders()->get('content-type'), 'text/html')===false || in_array($ext, array('.htm', '.html')))) $file = $tempPath2;
							else return array();
						}
						else return array();
					}
					else return array();
				}catch(\Exception $ex){}
				$hcd = $ob->getHeaders()->get('content-disposition');
				if($hcd && stripos($hcd, 'filename=')!==false)
				{
					$hcdParts = array_map('trim', explode(';', $hcd));
					$hcdParts1 = preg_grep('/filename\*=UTF\-8\'\'/i', $hcdParts);
					$hcdParts2 = preg_grep('/filename=/i', $hcdParts);
					$newFn = '';
					if(count($hcdParts1) > 0)
					{
						$hcdParts1 = explode("''", current($hcdParts1));
						$newFn = urldecode(trim(end($hcdParts1), '"\' '));
						if(!\Bitrix\EsolAie\Utils::IsUtfMode()) $newFn = \Bitrix\Main\Text\Encoding::convertEncoding($newFn, "UTF-8", "Windows-1251");
						$newFn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($newFn);
					}
					elseif(count($hcdParts2) > 0)
					{
						$hcdParts2 = explode('=', current($hcdParts2));
						$newFn = trim(end($hcdParts2), '"\' ');
						$newFn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($newFn);
					}
					if(strpos($file, $newFn)===false)
					{
						$file = Utils::ReplaceFile($file, dirname($file).$newFn);
					}
				}
			}
		}
		$arFile = \CFile::MakeFileArray($file);
		
		if(!$arFile['name'] && !\CUtil::DetectUTF8($file))
		{
			$file = Utils::Win1251Utf8($file);
			$arFile = \CFile::MakeFileArray($file);
		}
		
		if(file_exists($file) && is_dir($file))
		{
			$dirname = $file;
		}
		elseif(in_array($arFile['type'], array('application/zip', 'application/x-zip-compressed')) && !empty($fileTypes) && !in_array('zip', $fileTypes))
		{
			$archiveParams = $this->GetArchiveParams($fileOrig);
			if(!$archiveParams['exists'])
			{
				CheckDirPath($archiveParams['path']);
				$isExtract = false;
				if(class_exists('\ZipArchive'))
				{
					$zipObj = new \ZipArchive();
					if ($zipObj->open(\Bitrix\Main\IO\Path::convertLogicalToPhysical($arFile['tmp_name']))===true)
					{
						$isExtract = (bool)$zipObj->extractTo($archiveParams['path']);
						$zipObj->close();
					}
				}
				if(!$isExtract)
				{
					$zipObj = \CBXArchive::GetArchive($arFile['tmp_name'], 'ZIP');
					$zipObj->Unpack($archiveParams['path']);
				}
				Utils::CorrectEncodingForExtractDir($archiveParams['path']);
			}
			$dirname = $archiveParams['file'];
		}
		if(strlen($dirname) > 0)
		{
			$arFile = array();
			if(file_exists($dirname) && is_file($dirname)) $arFiles = array($dirname);
			elseif($this->PathContainsMask($dirname)) $arFiles = $this->GetFilesByMask($dirname);
			else $arFiles = Utils::GetFilesByExt($dirname, $fileTypes, $checkSubdirs);

			if($arParams['MULTIPLE']=='Y' && count($arFiles) > 1)
			{
				foreach($arFiles as $k=>$v)
				{
					$arFiles[$k] = \CFile::MakeFileArray($v);
				}
				$arFile = array('VALUES'=>$arFiles);
			}
			elseif(count($arFiles) > 0)
			{
				$tmpfile = current($arFiles);
				$arFile = \CFile::MakeFileArray($tmpfile);
			}
		}
		
		if(strpos($arFile['type'], 'image/')===0)
		{
			$ext = ToLower(str_replace('image/', '', $arFile['type']));
			if($this->IsWrongExt($arFile['name'], $ext))
			{
				if(($ext!='jpeg' || (($ext='jpg') && $this->IsWrongExt($arFile['name'], $ext)))
					&& ($ext!='svg+xml' || (($ext='svg') && $this->IsWrongExt($arFile['name'], $ext)))
				)
				{
					$arFile['name'] = $arFile['name'].'.'.$ext;
				}
			}
		}
		elseif($bNeedImage) $arFile = array();

		if(!empty($arDef) && !empty($arFile))
		{
			if(isset($arFile['VALUES']))
			{
				foreach($arFile['VALUES'] as $k=>$v)
				{
					$arFile['VALUES'][$k] = $this->PictureProcessing($v, $arDef);
				}
			}
			else
			{
				$arFile = $this->PictureProcessing($arFile, $arDef);
			}
		}
		if(!empty($arFile) && strpos($arFile['type'], 'image/')===0)
		{
			list($width, $height, $type, $attr) = getimagesize($arFile['tmp_name']);
			$arFile['external_id'] = 'i_'.md5(serialize(array('width'=>$width, 'height'=>$height, 'name'=>$arFile['name'], 'size'=>$arFile['size'])));
		}
		if(!empty($arFile) && strpos($arFile['type'], 'html')!==false) $arFile = array();
		if(array_key_exists('size', $arFile) && $arFile['size']==0 && filesize($arFile['tmp_name'])==0) $arFile = array();
		
		return $arFile;
	}
	
	public function IsWrongExt($name, $ext)
	{
		return (bool)(mb_substr($name, -(mb_strlen($ext) + 1))!='.'.$ext);
	}
	
	public function PathContainsMask($path)
	{
		return (bool)((strpos($path, '*')!==false || (strpos($path, '{')!==false && strpos($path, '}')!==false)));
	}
	
	public function GetFilesByMask($mask)
	{
		$arFiles = array();
		$prefix = (strpos($mask, $_SERVER['DOCUMENT_ROOT'])===0 ? '' : $_SERVER['DOCUMENT_ROOT']);
		if(strpos($mask, '/*/')===false)
		{
			$arFiles = glob($prefix.$mask, GLOB_BRACE);
		}
		else
		{
			$i = 1;
			while(empty($arFiles) && $i<8)
			{
				$arFiles = glob($prefix.str_replace('/*/', str_repeat('/'.'*', $i).'/', $mask), GLOB_BRACE);
				$i++;
			}
		}
		if(empty($arFiles)) return array();
		
		$arFiles = array_map(array(__CLASS__, 'RemoveDocRoot'), $arFiles);
		usort($arFiles, array(__CLASS__, 'SortByStrlen'));
		return $arFiles;
	}
	
	public function GetArchiveParams($file)
	{
		$arUrl = parse_url($file);
		$fragment = (isset($arUrl['fragment']) ? $arUrl['fragment'] : '');
		if(strlen($fragment) > 0) $file = mb_substr($file, 0, -mb_strlen($fragment) - 1);
		$archivePath = $this->archivedir.md5($file).'/';
		return array(
			'path' => $archivePath, 
			'exists' => file_exists($archivePath),
			'file' => $archivePath.ltrim($fragment, '/')
		);
	}
	
	public function GetFileFromArchive($file)
	{
		$archiveParams = $this->GetArchiveParams($file);
		if(!$archiveParams['exists']) return false;
		return $archiveParams['file'];
	}
	
	public function GetHLBoolValue($val)
	{
		$res = $this->GetBoolValue($val);
		if($res=='Y') return 1;
		else return 0;
	}
	
	public function GetBoolValue($val, $numReturn = false, $defaultValue = false)
	{
		return Utils::GetBoolValue($val, $numReturn, $defaultValue);
	}
	
	public function GetFieldEnumValue($arProp, $val)
	{
		if(strlen($val) > 0 && $arProp['uf_field_id'])
		{
			$fieldId = (int)$arProp['uf_field_id'];
			if(!isset($this->enumVals[$fieldId][$val]))
			{
				$valId = '';
				$dbRes = \CUserFieldEnum::Getlist(array(), array('USER_FIELD_ID'=>$fieldId, 'VALUE'=>$val));
				if($arr = $dbRes->Fetch())
				{
					$valId = $arr['ID'];
				}
				else
				{
					$arVals = array();
					$dbRes = \CUserFieldEnum::Getlist(array('SORT'=>'ASC', 'ID'=>'ASC'), array('USER_FIELD_ID'=>$fieldId));
					while($arr = $dbRes->Fetch())
					{
						$arVals[$arr['ID']] = $arr;
					}
					$arVals['n0'] = array('VALUE'=>$val);
					$enumObj = new \CUserFieldEnum();
					$enumObj->SetEnumValues($fieldId, $arVals);
					
					$dbRes = \CUserFieldEnum::Getlist(array(), array('USER_FIELD_ID'=>$fieldId, 'VALUE'=>$val));
					if($arr = $dbRes->Fetch())
					{
						$valId = $arr['ID'];
					}
				}
				
				$this->enumVals[$fieldId][$val] = $valId;
			}
			return $this->enumVals[$fieldId][$val];
		}
		return $val;
	}
	
	public function GetHighloadBlockValue($arProp, $val)
	{
		$val = trim($val);
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
						$dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID'=>'HLBLOCK_'.$htblId, 'FIELD_NAME'=>'UF_NAME'));
						if($arr = $dbRes->Fetch())
						{
							$this->hlblFields[$fieldId] = $arr['FIELD_NAME'];
						}
						else $this->hlblFields[$fieldId] = 'ID';
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
				
				$dbRes2 = $entityDataClass::GetList(array('filter'=>array($fieldName=>$val), 'select'=>array('ID', $fieldName), 'limit'=>1));
				if($arr2 = $dbRes2->Fetch())
				{
					$this->htblPropVals[$htblId][$val] = $arr2['ID'];
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
		$val = trim($val);
		if($val && Loader::includeModule('iblock') && $arProp['uf_settings']['IBLOCK_ID'])
		{
			$iblockId = (int)$arProp['uf_settings']['IBLOCK_ID'];
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
				$dbRes = \CIBlockElement::GetList(array(), array($selectField=>$val, 'IBLOCK_ID'=>$iblockId), false, false, array('ID'));
				if($arElem = $dbRes->GetNext())
				{
					$this->elemPropVals[$val][$selectField] = $arElem['ID'];
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
		$val = trim($val);
		if($val && Loader::includeModule('iblock') && $arProp['uf_settings']['IBLOCK_ID'])
		{
			$iblockId = (int)$arProp['uf_settings']['IBLOCK_ID'];
			$selectField = 'NAME';
			if($relField)
			{
				$selectField = $relField;
			}
			
			if(!isset($this->sectPropVals[$val][$selectField]))
			{
				$dbRes = \CIBlockSection::GetList(array(), array('IBLOCK_ID'=>$iblockId, $selectField=>$val), false, array('ID'));
				if($arElem = $dbRes->GetNext())
				{
					$this->sectPropVals[$val][$selectField] = $arElem['ID'];
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

	public function PictureProcessing($arFile, $arDef)
	{
		if($arDef["SCALE"] === "Y")
		{
			$arNewPicture = \CIBlock::ResizePicture($arFile, $arDef);
			if(is_array($arNewPicture))
			{
				$arFile = $arNewPicture;
			}
			/*elseif($arDef["IGNORE_ERRORS"] !== "Y")
			{
				unset($arFile);
				$strWarning .= Loc::getMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
			}*/
		}

		if($arDef["USE_WATERMARK_FILE"] === "Y")
		{
			\CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_FILE_POSITION"],
				"type" => "file",
				"size" => "real",
				"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
				"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
			));
		}

		if($arDef["USE_WATERMARK_TEXT"] === "Y")
		{
			\CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_TEXT_POSITION"],
				"type" => "text",
				"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
				"text" => $arDef["WATERMARK_TEXT"],
				"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
				"color" => $arDef["WATERMARK_TEXT_COLOR"],
			));
		}
		return $arFile;
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
		
		if(preg_match('/#CELL\d+#/', $paramName))
		{
			$k = intval(substr($paramName, 5, -1)) - 1;
			if(is_array($this->currentItemValues) && isset($this->currentItemValues[$k])) $value = $this->currentItemValues[$k];
			elseif($this->worksheet && ($val = $this->worksheet->getCellByColumnAndRow($k, $this->worksheetCurrentRow)))
			{
				$valText = $this->GetCalculatedValue($val);
				$value = $valText;
			}
		}
		elseif(preg_match('/#CELL(\d+)([\-\+]\d+)#/', $paramName, $m2))
		{
			if($this->worksheet && ($val = $this->worksheet->getCellByColumnAndRow((int)$m2[1] - 1, $this->worksheetCurrentRow + (int)$m2[2])))
			{
				$valText = $this->GetCalculatedValue($val);
				$value = $valText;
			}
		}
		elseif(preg_match('/#CELL(~+)(\d+)#/', $paramName, $m2))
		{
			$k = $m2[1].(intval($m2[2]) - 1);
			if(is_array($this->currentItemValues) && isset($this->currentItemValues[$k])) $value = $this->currentItemValues[$k];
		}
		elseif($paramName=='#CLINK#')
		{
			if($this->useHyperlinks && strlen($this->currentFieldKey) > 0)
			{
				$value = $this->hyperlinks[$this->currentFieldKey];
			}
		}
		elseif($paramName=='#CNOTE#')
		{
			if($this->useNotes && strlen($this->currentFieldKey) > 0)
			{
				$value = $this->notes[$this->currentFieldKey];
			}
		}
		elseif($paramName=='#HASH#')
		{
			$hash = md5(serialize($this->currentItemValues).serialize($this->params['FIELDS_LIST'][$this->worksheetNumForSave]));
			$value = $hash;
		}
		elseif($paramName=='#FILENAME#')
		{
			$value = bx_basename($this->filename);
		}
		elseif($paramName=='#SHEETNAME#')
		{
			$value = (is_callable(array($this->worksheet, 'getTitle')) ? $this->worksheet->getTitle() : '');
		}
		elseif($paramName=='#IMPORT_PROCESS_ID#')
		{
			$value = $this->stepparams['loggerExecId'];
		}
		
		if($isVar)
		{
			$this->extraConvParams[$paramName] = $value;
			return '$this->extraConvParams['.$quot.$paramName.$quot.']';
		}
		else return $value;
	}
	
	public function ApplyConversions($val, $arConv, $arItem, $field=false)
	{
		$arExpParams = array();
		$fieldName = $fieldKey = false;
		if(!is_array($field))
		{
			$fieldName = $field;
		}
		else
		{
			if($field['NAME']) $fieldName = $field['NAME'];
			if(strlen($field['KEY']) > 0) $fieldKey = $field['KEY'];
			if(strlen($field['PARENT_ID']) > 0) $arExpParams['PARENT_ID'] = $field['PARENT_ID'];
		}
		
		if(is_array($arConv))
		{
			$execConv = false;
			$this->currentItemValues = $arItem;
			$prefixPattern = '/(\$\{[\'"])?(#CELL~*\d+#|#CELL\d+[\-\+]\d+#|#CLINK#|#CNOTE#|#HASH#|#FILENAME#|#SHEETNAME#|#IMPORT_PROCESS_ID#)([\'"]\})?/';
			foreach($arConv as $k=>$v)
			{
				$condVal = $val;
				if((int)$v['CELL'] > 0)
				{
					$numCell = (int)$v['CELL'] - 1;
					if(array_key_exists($numCell, $arItem))
					{
						$condVal = (array_key_exists('~~'.$numCell, $arItem) ? $arItem['~~'.$numCell] : $arItem[$numCell]);
					}
					else
					{
						$condVal = $this->GetCalculatedValue($this->worksheet->getCellByColumnAndRow($numCell, $this->worksheetCurrentRow));
					}
				}
				if(strlen($v['FROM']) > 0) $v['FROM'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['FROM']);
				if($v['CELL']=='ELSE') $v['WHEN'] = '';
				$condValNum = $this->GetFloatVal($condVal);
				$fromNum = $this->GetFloatVal($v['FROM']);
				if(($v['CELL']=='ELSE' && !$execConv)
					|| ($v['WHEN']=='EQ' && $condVal==$v['FROM'])
					|| ($v['WHEN']=='NEQ' && $condVal!=$v['FROM'])
					|| ($v['WHEN']=='GT' && $condValNum > $fromNum)
					|| ($v['WHEN']=='LT' && $condValNum < $fromNum)
					|| ($v['WHEN']=='GEQ' && $condValNum >= $fromNum)
					|| ($v['WHEN']=='LEQ' && $condValNum <= $fromNum)
					|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
					|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
					|| ($v['WHEN']=='REGEXP' && preg_match('/'.ToLower($v['FROM']).'/i'.Utils::getUtfModifier(), ToLower($condVal)))
					|| ($v['WHEN']=='NOT_REGEXP' && !preg_match('/'.ToLower($v['FROM']).'/i'.Utils::getUtfModifier(), ToLower($condVal)))
					|| ($v['WHEN']=='EMPTY' && strlen($condVal)==0)
					|| ($v['WHEN']=='NOT_EMPTY' && strlen($condVal) > 0)
					|| ($v['WHEN']=='ANY'))
				{
					$this->currentFieldKey = $fieldKey;
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
					elseif($v['THEN']=='LCASE') $val = ToLower($val);
					elseif($v['THEN']=='UCASE') $val = ToUpper($val);
					elseif($v['THEN']=='UFIRST') $val = preg_replace_callback('/^(\s*)(.*)$/', array(__CLASS__, 'UFirstCallback'), $val);
					elseif($v['THEN']=='UWORD') $val = implode(' ', array_map(array(__CLASS__, 'UWordCallback'), explode(' ', $val)));
					elseif($v['THEN']=='MATH_ROUND') $val = round($this->GetFloatVal($val));
					elseif($v['THEN']=='MATH_MULTIPLY') $val = $this->GetFloatVal($val) * $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_DIVIDE') $val = $this->GetFloatVal($val) / $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_ADD') $val = $this->GetFloatVal($val) + $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_SUBTRACT') $val = $this->GetFloatVal($val) - $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='NOT_LOAD') $val = false;
					elseif($v['THEN']=='EXPRESSION') $val = $this->ExecuteFilterExpression($val, $v['TO'], false, $arExpParams, $field);
					elseif($v['THEN']=='STRIP_TAGS') $val = strip_tags($val);
					elseif($v['THEN']=='CLEAR_TAGS') $val = preg_replace('/<([a-z][a-z0-9:]*)[^>]*(\/?)>/i','<$1$2>', $val);
					elseif($v['THEN']=='TRANSLIT')
					{
						$arParams = array();
						$val = $this->Str2Url($val, $arParams);
					}
					$execConv = true;
				}
			}
		}
		return $val;
	}
	
	public function CalcFloatValue($val)
	{
		$val = preg_replace_callback('/#CELL\d+#/', array($this, 'ConversionReplaceValues'), $val);
		if(preg_match('/[+\-\/*]/', $val))
		{
			try{
				$command = 'return '.str_replace(',', '.', $val).';';
				$val = eval($command);
			}catch(\Exception $ex){}
		}
		return $val;
	}
	
	public function GetCurrentItemValues()
	{
		if(is_array($this->currentItemValues)) return $this->currentItemValues;
		else return array();
	}
	
	public static function GetPreviewData($file, $showLines, $arParams = array(), $colsCount = false)
	{
		$selfobj = new ImporterStatic($arParams, $file);
		$file = $_SERVER['DOCUMENT_ROOT'].$file;
		$objReader = \KDAPHPExcel_IOFactory::createReaderForFile($file);		
		if($arParams['ELEMENT_NOT_LOAD_STYLES']=='Y' && $arParams['ELEMENT_NOT_LOAD_FORMATTING']=='Y')
		{
			$objReader->setReadDataOnly(true);
		}
		if(isset($arParams['CSV_PARAMS']))
		{
			$objReader->setCsvParams($arParams['CSV_PARAMS']);
		}
		$chunkFilter = new KDAChunkReadFilter();
		$objReader->setReadFilter($chunkFilter);
		$maxLine = 1000;
		if(!$colsCount) $maxLine = max($showLines + 50, 50);
		$chunkFilter->setRows(1, $maxLine);

		$efile = $objReader->load($file);
		$arWorksheets = array();
		foreach($efile->getWorksheetIterator() as $worksheet) 
		{
			$maxDrawCol = 0;
			if($arParams['ELEMENT_LOAD_IMAGES']=='Y')
			{
				$drawCollection = $worksheet->getDrawingCollection();
				if($drawCollection)
				{
					foreach($drawCollection as $drawItem)
					{
						$coord = $drawItem->getCoordinates();
						$arCoords = \KDAPHPExcel_Cell::coordinateFromString($coord);
						$maxDrawCol = max($maxDrawCol, \KDAPHPExcel_Cell::columnIndexFromString($arCoords[0]));
					}
				}
			}
			
			$columns_count = max(\KDAPHPExcel_Cell::columnIndexFromString($worksheet->getHighestDataColumn()), $maxDrawCol);
			$columns_count = min($columns_count, 5000);
			$rows_count = $worksheet->getHighestDataRow();

			$arLines = array();
			$cntLines = $emptyLines = 0;
			for ($row = 0; ($row < $rows_count && count($arLines) < min($showLines+$emptyLines, $maxLine)); $row++) 
			{
				$arLine = array();
				$bEmpty = true;
				for ($column = 0; $column < $columns_count; $column++) 
				{
					$val = $worksheet->getCellByColumnAndRow($column, $row+1);					
					$valText = $selfobj->GetCalculatedValue($val);
					if(strlen(trim($valText)) > 0) $bEmpty = false;
					
					$curLine = array('VALUE' => $valText);
					if($arParams['ELEMENT_NOT_LOAD_STYLES']!='Y')
					{
						$curLine['STYLE'] = $selfobj->GetCellStyle($val, true);
					}
					$arLine[] = $curLine;
				}

				$arLines[$row] = $arLine;
				if($bEmpty)
				{
					$emptyLines++;
				}
				$cntLines++;
			}
			
			if($colsCount)
			{
				$columns_count = $colsCount;
				$arLines = array();
				$lastEmptyLines = 0;
				for ($row = $cntLines; $row < $rows_count; $row++) 
				{
					$arLine = array();
					$bEmpty = true;
					for ($column = 0; $column < $columns_count; $column++) 
					{
						$val = $worksheet->getCellByColumnAndRow($column, $row+1);
						$valText = $selfobj->GetCalculatedValue($val);
						if(strlen(trim($valText)) > 0) $bEmpty = false;
						
						$curLine = array('VALUE' => $valText);
						if($arParams['ELEMENT_NOT_LOAD_STYLES']!='Y')
						{
							$curLine['STYLE'] = $selfobj->GetCellStyle($val, true);
						}
						$arLine[] = $curLine;
					}
					if($bEmpty) $lastEmptyLines++;
					else $lastEmptyLines = 0;
					$arLines[$row] = $arLine;
				}
				
				if($lastEmptyLines > 0)
				{
					$arLines = array_slice($arLines, 0, -$lastEmptyLines, true);
				}
			}
			
			$arCells = explode(':', $worksheet->getSelectedCells());
			$heghestRow = intval(preg_replace('/\D+/', '', end($arCells)));
			if(is_callable(array($worksheet, 'getRealHighestRow'))) $heghestRow = intval($worksheet->getRealHighestRow());
			elseif($worksheet->getHighestDataRow() > $heghestRow) $heghestRow = intval($worksheet->getHighestDataRow());
			if(stripos($file, '.csv'))
			{
				$heghestRow = Utils::GetFileLinesCount($file);
			}

			$arWorksheets[] = array(
				'title' => self::CorrectCalculatedValue($worksheet->GetTitle()),
				'show_more' => ($row < $rows_count - 1),
				'lines_count' => $heghestRow,
				'lines' => $arLines
			);
		}
		return $arWorksheets;
	}
	
	public function IsChangedImage($fileId, $arNewFile)
	{
		if(!$fileId)
		{
			if(!empty($arNewFile))
			{
				if(array_key_exists('DESCRIPTION', $arNewFile) && strlen(trim($arNewFile['DESCRIPTION']))==0) unset($arNewFile['DESCRIPTION']);
				if(array_key_exists('description', $arNewFile) && strlen(trim($arNewFile['description']))==0) unset($arNewFile['description']);
				if(array_key_exists('VALUE', $arNewFile) && empty($arNewFile['VALUE'])) unset($arNewFile['VALUE']);
			}
			if(empty($arNewFile)) return false;
		}
			
		if($this->params['ELEMENT_IMAGES_FORCE_UPDATE']=='Y' || !$fileId) return true;
		if(is_array($fileId) && array_key_exists('VALUE', $fileId)) $fileId = $fileId['VALUE'];
		$arFile = \CFile::GetFileArray($fileId);
		$arNewFileVal = $arNewFile;
		if(isset($arNewFileVal['VALUE'])) $arNewFileVal = $arNewFileVal['VALUE'];
		if(isset($arNewFileVal['DESCRIPTION'])) $arNewFile['description'] = $arNewFile['DESCRIPTION'];
		if(!isset($arNewFileVal['tmp_name']) && isset($arNewFile['description']) && $arNewFile['description']==$arFile['DESCRIPTION'])
		{
			return false;
		}
		list($width, $height, $type, $attr) = getimagesize($arNewFileVal['tmp_name']);
		if(((array_key_exists('external_id', $arNewFileVal) && $arFile['EXTERNAL_ID']==$arNewFileVal['external_id'])
			|| ($arFile['FILE_SIZE']==$arNewFileVal['size'] 
				&& $arFile['ORIGINAL_NAME']==$arNewFileVal['name'] 
				&& (!$arFile['WIDTH'] || !$arFile['WIDTH'] || ($arFile['WIDTH']==$width && $arFile['HEIGHT']==$height))))
			&& file_exists($_SERVER['DOCUMENT_ROOT'].\Bitrix\Main\IO\Path::convertLogicalToPhysical($arFile['SRC']))
			&& (!isset($arNewFile['description']) || $arNewFile['description']==$arFile['DESCRIPTION']))
		{
			return false;
		}
		return true;
	}
	
	public function GetCellStyle($val, $modify = false)
	{
		$style = $val->getStyle();
		if(!is_object($style)) return array();
		$arStyle = array(
			'COLOR' => $style->getFont()->getColor()->getRGB(),
			'FONT-FAMILY' => $style->getFont()->getName(),
			'FONT-SIZE' => $style->getFont()->getSize(),
			'FONT-WEIGHT' => $style->getFont()->getBold(),
			'FONT-STYLE' => $style->getFont()->getItalic(),
			'TEXT-DECORATION' => $style->getFont()->getUnderline(),
			'BACKGROUND' => ($style->getFill()->getFillType()=='solid' ? $style->getFill()->getStartColor()->getRGB() : ''),
		);
		$outlineLevel = (int)$val->getWorksheet()->getRowDimension($val->getRow())->getOutlineLevel();
		if($outlineLevel > 0)
		{
			$arStyle['TEXT-INDENT'] = $outlineLevel;
		}
		if($modify)
		{
			$arStyle['EXT'] = array(
				'COLOR' => $style->getFont()->getColor()->getRealRGB(),
				'BACKGROUND' => ($style->getFill()->getFillType()=='solid' ? $style->getFill()->getStartColor()->getRealRGB() : ''),
			);
		}
		
		return $arStyle;
	}
	
	public function GetStyleByColumn($column, $param)
	{
		$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
		$arStyle = self::GetCellStyle($val);
		if(isset($arStyle[$param])) return $arStyle[$param];
		else return '';
	}
	
	public function GetOrigValueByColumn($column)
	{
		$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
		return $val->getValue();
	}
	
	public function GetValueByColumn($column)
	{
		$val = $this->worksheet->getCellByColumnAndRow($column, $this->worksheetCurrentRow);
		$valOrig = $this->GetCalculatedValue($val);
		return $valOrig;
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
			$val = self::ReplaceCpSpecChars($val);
			if(function_exists('iconv'))
			{
				$newVal = iconv("UTF-8", "CP1251//IGNORE", $val);
				if(strlen(trim($newVal))==0 && strlen(trim($val))>0)
				{
					$newVal2 = \Bitrix\Main\Text\Encoding::convertEncoding($val, "UTF-8", "Windows-1251");
					if(strpos(trim($newVal2), '?')!==0) $newVal = $newVal2;
				}
				$val = $newVal;
			}
			else $val = \Bitrix\Main\Text\Encoding::convertEncoding($val, "UTF-8", "Windows-1251");
		}
		return $val;
	}
	
	public static function ReplaceCpSpecChars($val)
	{
		$specChars = array('Ø'=>'&#216;', '™'=>'&#153;', '®'=>'&#174;', '©'=>'&#169;');
		if(!isset(static::$cpSpecCharLetters))
		{
			$cpSpecCharLetters = array();
			foreach($specChars as $char=>$code)
			{
				$letter = false;
				$pos = 0;
				for($i=192; $i<255; $i++)
				{
					$tmpLetter = \Bitrix\Main\Text\Encoding::convertEncodingArray(chr($i), 'CP1251', 'UTF-8');
					$tmpPos = strpos($tmpLetter, $char);
					if($tmpPos!==false)
					{
						$letter = $tmpLetter;
						$pos = $tmpPos;
					}
				}
				$cpSpecCharLetters[$char] = array('letter'=>$letter, 'pos'=>$pos);
			}
			static::$cpSpecCharLetters = $cpSpecCharLetters;
		}
		
		foreach($specChars as $char=>$code)
		{
			if(strpos($val, $char)===false) continue;
			$letter = static::$cpSpecCharLetters[$char]['letter'];
			$pos = static::$cpSpecCharLetters[$char]['pos'];

			if($letter!==false)
			{
				if($pos==0) $val = preg_replace('/'.mb_substr($letter, 0, 1).'(?!'.mb_substr($letter, 1, 1).')/', $code, $val);
				elseif($pos==1) $val = preg_replace('/(?<!'.mb_substr($letter, 0, 1).')'.mb_substr($letter, 1, 1).'/', $code, $val);
			}
			else
			{
				$val = str_replace($char, $code, $val);
			}
		}
		return $val;
	}
	
	public function GetFloatVal($val, $precision=0)
	{
		if(is_array($val)) $val = current($val);
		$val = floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
		if($precision > 0) $val = round($val, $precision);
		return $val;
	}
	
	public function GetDateVal($val, $format = 'FULL')
	{
		$time = strtotime($val);
		if($time > 0)
		{
			//return ConvertTimeStamp($time, $format);
			$date = ConvertTimeStamp($time, $format);
			if($format=='PART') return new \Bitrix\Main\Type\Date($date);
			else return new \Bitrix\Main\Type\DateTime($date);
		}
		return false;
	}

	public function Trim($str)
	{
		$str = trim($str);
		$str = preg_replace('/(^(\xC2\xA0|\s)+|(\xC2\xA0|\s)+$)/s', '', $str);
		return $str;
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
	
	public static function TrimToLower($n)
	{
		return ToLower(trim($n));
	}
	
	public static function UrlDecodeCallback($m)
	{
		return rawurlencode($m[0]);
	}
	
	public static function RemoveDocRoot($n)
	{
		return substr($n, strlen($_SERVER["DOCUMENT_ROOT"]));
	}
	
	public static function SortByStrlen($a, $b)
	{
		return strlen($a)<strlen($b) ? -1 : 1;
	}
	
	public static function UFirstCallback($m)
	{
		return $m[1].ToUpper(mb_substr($m[2], 0, 1)).ToLower(mb_substr($m[2], 1));
	}
	
	public static function UWordCallback($m)
	{
		return ToUpper(mb_substr($m, 0, 1)).ToLower(mb_substr($m, 1));
	}
	
	public function OnShutdown()
	{
		$arError = error_get_last();
		if(!is_array($arError) || !isset($arError['type']) || !in_array($arError['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR))) return;
		
		if($this->worksheetCurrentRow > 0)
		{
			$this->EndWithError(sprintf(Loc::getMessage("ESOL_AE_FATAL_ERROR_IN_LINE"), $this->worksheetNumForSave+1, $this->worksheetCurrentRow, $arError['type'], $arError['message'], $arError['file'], $arError['line']));
		}
		else
		{
			$this->EndWithError(sprintf(Loc::getMessage("ESOL_AE_FATAL_ERROR"), $arError['type'], $arError['message'], $arError['file'], $arError['line']));
		}
	}
	
	public function HandleError($code, $message, $file, $line)
	{
		return true;
	}
	
	public function HandleException($exception)
	{
		$error = '';
		if($this->worksheetCurrentRow > 0)
		{
			$error .= sprintf(Loc::getMessage("ESOL_AE_ERROR_LINE"), $this->worksheetNumForSave+1, $this->worksheetCurrentRow);
		}
		if(is_callable(array('\Bitrix\Main\Diag\ExceptionHandlerFormatter', 'format')))
		{
			$error .= \Bitrix\Main\Diag\ExceptionHandlerFormatter::format($exception);
		}
		else
		{
			$error .= sprintf(Loc::getMessage("ESOL_AE_FATAL_ERROR"), '', $exception->getMessage(), $exception->getFile(), $exception->getLine());
		}
		$this->EndWithError($error);
	}
	
	public function EndWithError($error)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$this->errors[] = $error;
		$this->SaveStatusImport();
		echo '<!--module_return_data-->'.\Bitrix\EsolAie\Utils::PhpToJSObject($this->GetBreakParams());
		die();
	}
}

class ImporterStatic extends Importer
{
	function __construct($params, $file='')
	{
		$this->params = $params;
		$this->filename = $_SERVER['DOCUMENT_ROOT'].$file;
		$this->SetZipClass();
	}
}

class KDAChunkReadFilter implements \KDAPHPExcel_Reader_IReadFilter
{
	private $_startRow = 0;
	private $_endRow = 0;
	private $_arFilePos = array();
	private $_arMerge = array();
	private $_params = array();
	/**  Set the list of rows that we want to read  */
	
	public function setParams($arParams=array())
	{
		$this->_params = $arParams;
	}
	
	public function getParam($paramName)
	{
		return (array_key_exists($paramName, $this->_params) ? $this->_params[$paramName] : false);
	}
	
	public function setMergeCells($mergeRef)
	{
		if(preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', trim($mergeRef), $m) && $m[2]!=$m[4])
		{
			/*$this->_arMerge[$m[1]][$m[2].':'.$m[4]] = array($m[2], $m[4]);
			$this->_arMerge[$m[3]][$m[2].':'.$m[4]] = array($m[2], $m[4]);*/
			$this->_arMerge[$m[2].':'.$m[4]] = array($m[2], $m[4]);
		}
	}

	public function setRows($startRow, $chunkSize) {
		$this->_startRow    = $startRow;
		$this->_endRow      = $startRow + $chunkSize;
		$this->_arMerge = array();
	}

	public function readCell($column, $row, $worksheetName = '') {
		//  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
		if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) {
			return true;
		}
		elseif(count($this->_arMerge) > 0){
			foreach($this->_arMerge as $range){
				if($row >= $range[0] && $row <= $range[1] && (($this->_startRow >= $range[0] && $this->_startRow <= $range[1]) || ($this->_endRow >= $range[0] && $this->_endRow <= $range[1]))){
					return true;
				}
			}
		}
		return false;
	}
	
	public function getStartRow()
	{
		return $this->_startRow;
	}
	
	public function getEndRow()
	{
		return $this->_endRow;
	}
	
	public function setFilePosRow($row, $pos)
	{
		$this->_arFilePos[$row] = $pos;
	}
	
	public function getFilePosRow($row)
	{
		$nextRow = $row + 1;
		$pos = 0;
		if(!empty($this->_arFilePos))
		{
			if(isset($this->_arFilePos[$nextRow])) $pos = (int)$this->_arFilePos[$nextRow];
			else
			{
				$arKeys = array_keys($this->_arFilePos);
				if(!empty($arKeys))
				{
					$maxKey = max($arKeys);
					if($nextRow > $maxKey);
					{
						$nextRow = $maxKey;
						$pos = (int)$this->_arFilePos[$maxKey];
					}
				}
			}
		}
		return array(
			'row' => $nextRow,
			'pos' => $pos
		);
	}
}	
?>