<?php
namespace Bitrix\EsolAie\Export;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Profile {
	protected static $moduleId = 'esol.allimportexport';
	protected static $dbIsPrepared = false;
	protected static $instances = array();
	private $params = array();
	private $errors = array();
	private $entity = false;
	private $entityField = false;
	private $pid = null;
	private $exportMode = null;
	
	function __construct($entityField)
	{
		static::PrepareDB();
		$this->CheckStorage();
		
		$this->tmpdir = $_SERVER["DOCUMENT_ROOT"].'/upload/tmp/'.static::$moduleId.'/export/';
		CheckDirPath($this->tmpdir);
		$this->uploadDir = $_SERVER["DOCUMENT_ROOT"].'/upload/'.static::$moduleId.'/export/';
		CheckDirPath($this->uploadDir);
		
		foreach(array($this->tmpdir, $this->uploadDir) as $k=>$v)
		{
			$i = 0;
			while(++$i < 10 && strlen($v) > 0 && !file_exists($v) && dirname($v)!=$v)
			{
				$v = dirname($v);
			}
			if(strlen($v) > 0 && file_exists($v) && !is_writable($v))
			{
				$this->errors[] = sprintf(Loc::getMessage('ESOL_AE_DIR_NOT_WRITABLE'), $v);
			}
		}
		
		$this->tmpdir = realpath($this->tmpdir).'/';
		$this->uploadDir = realpath($this->uploadDir).'/';
		$this->entityField = $entityField;
	}
	
	public static function getInstance($entity='false')
	{
		if (!isset(static::$instances[$entity]))
			static::$instances[$entity] = new static($entity);

		return static::$instances[$entity];
	}
	
	public static function PrepareDB()
	{
		if(!static::$dbIsPrepared)
		{
			$profileDB = new ProfileTable();
			$conn = $profileDB->getEntity()->getConnection();
			if(is_callable(array($conn, 'queryExecute')))
			{
				$conn->queryExecute('SET wait_timeout=1800');
				$conn->queryExecute('SET sql_mode=""');
			}
			if(is_callable('\Bitrix\Main\Application', 'getInstance') && is_callable('\Bitrix\Main\Application', 'getExceptionHandler'))
			{
				$app = \Bitrix\Main\Application::getInstance();
				$app->getExceptionHandler()->setDebugMode(true);
			}
			if(is_callable(array($conn, 'stopTracker')))
			{
				$conn->stopTracker();
			}
			if(isset($GLOBALS['DB']) && is_object($GLOBALS['DB']))
			{
				$GLOBALS['DB']->debug = true;
				$GLOBALS['DB']->DebugToFile = false;
			}
			static::$dbIsPrepared = true;
		}
	}
	
	public function CheckStorage()
	{
		$optionName = 'DB_STRUCT_VERSION_EXPORT';
		$moduleVersion = false;
		if(is_callable(array('\Bitrix\Main\ModuleManager', 'getVersion')))
		{
			$moduleVersion = \Bitrix\Main\ModuleManager::getVersion(static::$moduleId);
			if($moduleVersion==\Bitrix\Main\Config\Option::get(static::$moduleId, $optionName)) return;
		}
		
		/*Security addon*/
		$arFiles = array(
			'/bitrix/admin/esol_allimportexport_cron_settings.php' => '/bitrix/modules/esol.allimportexport/admin/cron_settings.php',
			'/bitrix/admin/esol_export_excel_cron_settings.php' => '/bitrix/modules/esol.importexportexcel/admin/iblock_export_excel_cron_settings.php',
			'/bitrix/admin/esol_import_excel_cron_settings.php' => '/bitrix/modules/esol.importexportexcel/admin/iblock_import_excel_cron_settings.php',
			'/bitrix/admin/esol_import_xml_cron_settings.php' => '/bitrix/modules/esol.importxml/admin/import_xml_cron_settings.php',
			'/bitrix/admin/esol_export_xml_cron_settings.php' => '/bitrix/modules/esol.exportxml/admin/export_xml_cron_settings.php',
			'/bitrix/admin/esol_massedit_profile.php' => '/bitrix/modules/esol.massedit/admin/profile.php',
			'/bitrix/admin/kda_export_excel_cron_settings.php' => '/bitrix/modules/kda.exportexcel/admin/iblock_export_excel_cron_settings.php',
			'/bitrix/admin/kda_import_excel_cron_settings.php' => '/bitrix/modules/kda.importexcel/admin/iblock_import_excel_cron_settings.php'
		);
		foreach($arFiles as $f1=>$f2)
		{
			$secBlock = "<?php\r\n".
				"if(isset(\$_REQUEST['path']) && strlen(\$_REQUEST['path']) > 0 || !file_exists(\$_SERVER['DOCUMENT_ROOT'].'".$f2."'))\r\n".
				"{\r\n".
				"	header((stristr(php_sapi_name(), 'cgi') !== false ? 'Status: ' : \$_SERVER['SERVER_PROTOCOL'].' ').'403 Forbidden');\r\n".
				"	die();\r\n".
				"}\r\n".
				"?>";
			$fileBlock = "<?php\r\n".
					"require(\$_SERVER['DOCUMENT_ROOT'].'".$f2."');\r\n".
					"?>";
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].$f1))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'].$f1, $secBlock.$fileBlock);
			}
			elseif(($c = file_get_contents($_SERVER['DOCUMENT_ROOT'].$f1)) && str_replace("\r", '', trim($c))==str_replace("\r", '', $fileBlock))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'].$f1, $secBlock.trim($c));
			}
		}
		/*/Security addon*/
		
		$profileEntity = $this->GetEntity();
		$tblName = $profileEntity->getTableName();
		$conn = $profileEntity->getEntity()->getConnection();
		if(!$conn->isTableExists($tblName))
		{
			$profileEntity->getEntity()->createDbTable();
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `PARAMS` `PARAMS` mediumtext DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_START` `DATE_START` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `DATE_FINISH` `DATE_FINISH` datetime DEFAULT NULL');
			$conn->query('ALTER TABLE `'.$tblName.'` CHANGE COLUMN `SORT` `SORT` int(11) NOT NULL DEFAULT "500"');
			
			$this->CheckTableEncoding($conn, $tblName);
		}
		else
		{
			$isNewFields = false;
			$arDbFields = array();
			$dbRes = $conn->query("SHOW COLUMNS FROM `" . $tblName . "`");
			while($arr = $dbRes->Fetch())
			{
				$arDbFields[] = $arr['Field'];
			}
			$fields = $profileEntity->getEntity()->getScalarFields();
			$helper = $conn->getSqlHelper();
			$prevField = 'ID';
			foreach($fields as $columnName => $field)
			{
				$realColumnName = $field->getColumnName();
				if(!in_array($realColumnName, $arDbFields))
				{
					$conn->query('ALTER TABLE '.$helper->quote($tblName).' ADD COLUMN '.$helper->quote($realColumnName).' '.$helper->getColumnTypeByField($field).' DEFAULT NULL AFTER '.$helper->quote($prevField));
					if($field->getDefaultValue())
					{
						$conn->query('UPDATE '.$helper->quote($tblName).' SET '.$helper->quote($realColumnName).'="'.$helper->forSql($field->getDefaultValue()).'"');
					}
					$isNewFields = true;
				}
				$prevField = $realColumnName;
			}
			if($isNewFields) $this->CheckTableEncoding($conn, $tblName);
		}
		
		if($moduleVersion)
		{
			\Bitrix\Main\Config\Option::set(static::$moduleId, $optionName, $moduleVersion);
		}
	}
	
	private function CheckTableEncoding($conn, $tblName)
	{
		$res = $conn->query('SHOW VARIABLES LIKE "character_set_connection"');
		$f = $res->fetch();
		$charset = trim($f['Value']);

		$res = $conn->query('SHOW VARIABLES LIKE "collation_connection"');
		$f = $res->fetch();
		$collation = trim($f['Value']);
		$charset2 = $this->GetCharsetByCollation($conn, $collation);
		
		$res0 = $conn->query('SHOW CREATE TABLE `' . $tblName . '`');
		$f0 = $res0->fetch();
		
		if (preg_match('/DEFAULT CHARSET=([a-z0-9\-_]+)/i', $f0['Create Table'], $regs))
		{
			$t_charset = $regs[1];
			if (preg_match('/COLLATE=([a-z0-9\-_]+)/i', $f0['Create Table'], $regs))
				$t_collation = $regs[1];
			else
			{
				$res0 = $conn->query('SHOW CHARSET LIKE "' . $t_charset . '"');
				$f0 = $res0->fetch();
				$t_collation = $f0['Default collation'];
			}
		}
		else
		{
			$res0 = $conn->query('SHOW TABLE STATUS LIKE "' . $tblName . '"');
			$f0 = $res0->fetch();
			if (!$t_collation = $f0['Collation'])
				return;
			$t_charset = $this->GetCharsetByCollation($conn, $t_collation);
		}
		
		if ($charset != $t_charset)
		{
			$conn->query('ALTER TABLE `' . $tblName . '` CHARACTER SET ' . $charset);
		}
		if ($t_collation != $collation)
		{	// table collation differs
			$conn->query('ALTER TABLE `' . $tblName . '` COLLATE ' . $collation);
		}
		
		$arFix = array();
		$res0 = $conn->query("SHOW FULL COLUMNS FROM `" . $tblName . "`");
		while($f0 = $res0->fetch())
		{
			$f_collation = $f0['Collation'];
			if ($f_collation === NULL || $f_collation === "NULL")
				continue;

			$f_charset = $this->GetCharsetByCollation($conn, $f_collation);
			if ($charset != $f_charset && $charset2 != $f_charset)
			{
				$arFix[] = ' MODIFY `'.$f0['Field'].'` '.$f0['Type'].' CHARACTER SET '.$charset.($collation != $f_collation ? ' COLLATE '.$collation : '').($f0['Null'] == 'YES' ? ' NULL' : ' NOT NULL').
						($f0['Default'] === NULL ? ($f0['Null'] == 'YES' ? ' DEFAULT NULL ' : '') : ' DEFAULT '.($f0['Type'] == 'timestamp' && $f0['Default'] == 'CURRENT_TIMESTAMP' ? $f0['Default'] : '"'.$conn->getSqlHelper()->forSql($f0['Default']).'"')).' '.$f0['Extra'];
			}
			elseif ($collation != $f_collation)
			{
				$arFix[] = ' MODIFY `'.$f0['Field'].'` '.$f0['Type'].' COLLATE '.$collation.($f0['Null'] == 'YES' ? ' NULL' : ' NOT NULL').
						($f0['Default'] === NULL ? ($f0['Null'] == 'YES' ? ' DEFAULT NULL ' : '') : ' DEFAULT '.($f0['Type'] == 'timestamp' && $f0['Default'] == 'CURRENT_TIMESTAMP' ? $f0['Default'] : '"'.$conn->getSqlHelper()->forSql($f0['Default']).'"')).' '.$f0['Extra'];
			}
		}

		if(count($arFix))
		{
			$conn->query('ALTER TABLE `'.$tblName.'` '.implode(",\n", $arFix));
		}
	}
	
	private function GetCharsetByCollation($conn, $collation)
	{
		static $CACHE;
		if (!$c = &$CACHE[$collation])
		{
			$res0 = $conn->query('SHOW COLLATION LIKE "' . $collation . '"');
			$f0 = $res0->Fetch();
			$c = $f0['Charset'];
		}
		return $c;
	}
	
	private function GetEntity()
	{
		if(!$this->entity)
		{
			$this->entity = new ProfileTable();
		}
		return $this->entity;
	}
	
	public function GetList($arFilter=array(), $useEntity = true)
	{
		$arProfiles = array();
		$profileEntity = $this->GetEntity();
		if(!is_array($arFilter)) $arFilter = array();
		if(empty($arFilter)) $arFilter = array('ACTIVE'=>'Y');
		if($useEntity && $this->entityField) $arFilter['ENTITY'] = $this->entityField;
		$dbRes = $profileEntity::getList(array('select'=>array('ID', 'NAME'), 'order'=>array('SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>$arFilter));
		while($arr = $dbRes->Fetch())
		{
			$arProfiles[$arr['ID'] - 1] = $arr['NAME'];
		}
		
		return $arProfiles;
	}
	
	public function GetByID($ID)
	{
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1)), 'select'=>array('PARAMS')))->fetch();
		if($arProfile && $arProfile['PARAMS'])
		{
			$arProfile = \Bitrix\EsolAie\Utils::Unserialize($arProfile['PARAMS']);
		}
		if(!is_array($arProfile)) $arProfile = array();
		
		return $arProfile;
	}
	
	public function GetFieldsByID($ID)
	{
		if(!is_numeric($ID)) return array();
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1))))->fetch();
		unset($arProfile['PARAMS']);
		
		return $arProfile;
	}
	
	public function Add($entity, $name)
	{
		global $APPLICATION;
		$APPLICATION->ResetException();
		
		$name = trim($name);
		if(strlen($name)==0)
		{
			$APPLICATION->throwException(Loc::getMessage("ESOL_AE_NOT_SET_PROFILE_NAME"));
			return false;
		}
		
		$entity = trim($entity);
		if(!\Bitrix\EsolAie\Runner::ValidateEntity($entity))
		{
			$APPLICATION->throwException(Loc::getMessage("ESOL_AE_WRONG_ENTITY"));
			return false;
		}
		
		$profileEntity = $this->GetEntity();
		$arFilter = array('NAME'=>$name);
		if($this->entityField) $arFilter['ENTITY'] = $this->entityField;
		if($arProfile = $profileEntity::getList(array('filter'=>$arFilter, 'select'=>array('ID')))->fetch())
		{
			$APPLICATION->throwException(Loc::getMessage("ESOL_AE_PROFILE_NAME_EXISTS"));
			return false;
		}
		
		$dbRes = $profileEntity::add(array('NAME'=>$name, 'ENTITY'=>$entity));
		if(!$dbRes->isSuccess())
		{
			$error = '';
			if($dbRes->getErrors())
			{
				foreach($dbRes->getErrors() as $errorObj)
				{
					$error .= $errorObj->getMessage().'. ';
				}
				$APPLICATION->throwException($error);
			}
			return false;
		}
		else
		{
			$ID = $dbRes->getId() - 1;			
			return $ID;
		}
	}
	
	public function Update($ID, $settigs_default, $settings)
	{
		$arProfile = $this->GetByID($ID);
		if(is_array($settigs_default))
		{
			$arProfile['SETTINGS_DEFAULT'] = $settigs_default;
		}
		if(is_array($settings))
		{
			$arProfile['SETTINGS'] = $settings;
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arProfile)));
	}
	
	public function UpdateExtra($ID, $extrasettings)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($extrasettings)) $extrasettings = array();
		$arProfile['EXTRASETTINGS'] = $extrasettings;
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arProfile)));
	}
	
	public function Delete($ID)
	{
		$profileEntity = $this->GetEntity();
		$profileEntity::delete($ID+1);
	}
	
	public function Copy($ID)
	{
		$profileEntity = $this->GetEntity();
		$arProfile = $profileEntity::getList(array('filter'=>array('ID'=>($ID + 1)), 'select'=>array('NAME', 'ENTITY', 'PARAMS')))->fetch();
		if(!$arProfile) return false;
		
		$newName = $arProfile['NAME'].Loc::getMessage("ESOL_AE_PROFILE_COPY");
		$arParams = \Bitrix\EsolAie\Utils::Unserialize($arProfile['PARAMS']);
		if($arParams['SETTINGS_DEFAULT']['DATA_FILE'] > 0)
		{
			$arParams['SETTINGS_DEFAULT']['DATA_FILE'] = Utils::CopyFile($arParams['SETTINGS_DEFAULT']['DATA_FILE'], true);
			$arProfile['PARAMS'] = serialize($arParams);
		}
		$dbRes = $profileEntity::add(array('NAME'=>$newName, 'ENTITY'=>$arProfile['ENTITY'], 'PARAMS'=>$arProfile['PARAMS']));
		if(!$dbRes->isSuccess())
		{
			$error = '';
			if($dbRes->getErrors())
			{
				foreach($dbRes->getErrors() as $errorObj)
				{
					$error .= $errorObj->getMessage().'. ';
				}
				//$APPLICATION->throwException($error);
			}
			return false;
		}
		else
		{
			$newId = $dbRes->getId() - 1;			
			return $newId;
		}
	}
	
	public function Rename($ID, $name)
	{
		global $APPLICATION;
		$APPLICATION->ResetException();
		
		$name = trim($name);
		if(strlen($name)==0)
		{
			$APPLICATION->throwException(Loc::getMessage("ESOL_AE_NOT_SET_PROFILE_NAME"));
			return false;
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('NAME'=>$name));
	}
	
	public function UpdateFields($ID, $arFields)
	{
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), $arFields);
	}
	
	public function ApplyToLists($ID, $listFrom, $listTo)
	{
		if(!is_numeric($listFrom) || !is_array($listTo) || count($listTo)==0) return;
		$listTo = preg_grep('/^\d+$/', $listTo);
		if(count($listTo)==0) return;
		
		$arParams = $this->GetByID($ID);
		foreach($listTo as $key)
		{
			$arParams['SETTINGS']['FIELDS_LIST'][$key] = $arParams['SETTINGS']['FIELDS_LIST'][$listFrom];
			$arParams['EXTRASETTINGS'][$key] = $arParams['EXTRASETTINGS'][$listFrom];
		}
		$profileEntity = $this->GetEntity();
		$profileEntity::update(($ID+1), array('PARAMS'=>serialize($arParams)));
	}
	
	public function GetStatus($id)
	{
		$tmpfile = $this->tmpdir.$id.'.txt';
		if(!file_exists($tmpfile) || filesize($tmpfile)>500*1024) return '';		
		$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp(file_get_contents($tmpfile));
		$percent = round(((int)$arParams['total_read_line'] / (int)$arParams['total_file_line']) * 100);
		$percent = min($percent, 99);
		if((time() - filemtime($tmpfile) < 4*60)) $status = Loc::getMessage("ESOL_AE_STATUS_PROCCESS");
		else $status = Loc::getMessage("ESOL_AE_STATUS_BREAK");
		return $status.' ('.$percent.'%)';
	}
	
	public function GetProfileParamsByFile($tmpfile)
	{
		$content = file_get_contents($tmpfile);
		$maxLength = 10*1024;
		if(strlen($content) > $maxLength)
		{
			$arParams = array();
			$content = preg_replace('/(.)\{[^\}]*\}(.)/Uis', '$1$2', $content);
			if(preg_match_all("/'([^']*)':'([^']*)'/", $content, $m))
			{
				foreach($m[1] as $k2=>$v2)
				{
					$arParams[$v2] = $m[2][$k2];
				}
			}
		}
		else
		{
			$arParams = \Bitrix\EsolAie\Utils::JsObjectToPhp(file_get_contents($tmpfile));
		}
		return $arParams;
	}
	
	public function GetProcessedProfiles()
	{
		$arProfiles = $this->GetList();
		foreach($arProfiles as $k=>$v)
		{
			$tmpfile = $this->tmpdir.$k.'.txt';
			if(!file_exists($tmpfile) || filesize($tmpfile)>500*1024 || (time() - filemtime($tmpfile) < 4*60) || filemtime($tmpfile) < mktime(0, 0, 0, 12, 24, 2015))
			{
				unset($arProfiles[$k]);
				continue;
			}
			
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			$percent = round(((int)$arParams['total_read_line'] / max((int)$arParams['total_file_line'], 1)) * 100);
			$percent = min($percent, 99);
			$arProfiles[$k] = array(
				'key' => $k,
				'name' => $v,
				'percent' => $percent
			);
		}
		if(!is_array($arProfiles)) $arProfiles = array();
		return $arProfiles;
	}
	
	public function RemoveProcessedProfile($id)
	{
		$tmpfile = $this->tmpdir.$id.'.txt';
		if(file_exists($tmpfile))
		{
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			if($arParams['tmpdir'])
			{
				DeleteDirFilesEx(substr($arParams['tmpdir'], strlen($_SERVER['DOCUMENT_ROOT'])));
			}
			unlink($tmpfile);
		}
	}
	
	public function GetProccessParams($id)
	{
		$tmpfile = $this->tmpdir.$id.'.txt';
		if(file_exists($tmpfile))
		{
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			$paramFile = $arParams['tmpdir'].'params.txt';
			if(file_exists($paramFile)) $arParams = \Bitrix\EsolAie\Utils::Unserialize(file_get_contents($paramFile));
			return $arParams;
		}
		return false;
	}
	
	public function GetProccessParamsFromPidFile($id)
	{
		$tmpfile = $this->tmpdir.$id.'.txt';
		if(file_exists($tmpfile))
		{
			if(time() - filemtime($tmpfile) < 3*60)
			{
				return false;
			}
			$arParams = $this->GetProfileParamsByFile($tmpfile);
			return $arParams;
		}
		return array();
	}
	
	public function SetExportParams($pid, $arParams=array())
	{
		$this->pid = $pid;
		$this->exportMode = ($arParams['EXPORT_MODE']=='CRON' ? 'CRON' : 'USER');
	}
	
	public function GetErrors()
	{
		if(!isset($this->errors) || !is_array($this->errors)) $this->errors = array();
		return implode('<br>', array_unique($this->errors));
	}
	
	public function ShowProfileList($fname)
	{
		$arProfiles = $this->GetList();
		?><select name="<?echo $fname;?>" id="<?echo $fname;?>" onchange="EProfile.Choose(this)"><?
			?><option value=""><?echo Loc::getMessage("ESOL_AE_NO_PROFILE"); ?></option><?
			?><option value="new" <?if($_REQUEST[$fname]=='new'){echo 'selected';}?>><?echo Loc::getMessage("ESOL_AE_NEW_PROFILE"); ?></option><?
			foreach($arProfiles as $k=>$profile)
			{
				?><option value="<?echo $k;?>" <?if(strlen($_REQUEST[$fname])>0 && strval($_REQUEST[$fname])===strval($k)){echo 'selected';}?>><?echo $profile; ?></option><?
			}
		?></select><?
	}
	
	public function SetParams($params=array())
	{
		$this->params = $params;
	}
	
	public function GetParam($name)
	{
		if(isset($this->params[$name])) return $this->params[$name];
		return null;
	}
	
	public function Apply(&$settigs_default, &$settings, $ID)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($settigs_default) && is_array($arProfile['SETTINGS_DEFAULT']))
		{
			$settigs_default = $arProfile['SETTINGS_DEFAULT'];
		}
		if(!is_array($settings) && is_array($arProfile['SETTINGS']))
		{
			$settings = $arProfile['SETTINGS'];
		}
		if(is_array($settings))
		{
			if($settings['DISPLAY_PARAMS'])
			{
				foreach($settings['DISPLAY_PARAMS'] as $k=>$v)
				{
					if($v && !is_array($v))
					{
						$v = \Bitrix\EsolAie\Utils::JsObjectToPhp($v);
					}
					if(!is_array($v)) $v = array();
					$settings['DISPLAY_PARAMS'][$k] = $v;
				}
			}
		}
		$this->SetParams($settigs_default);
	}
	
	public function ApplyExtra(&$extrasettings, $ID)
	{
		$arProfile = $this->GetByID($ID);
		if(!is_array($extrasettings) && is_array($arProfile['EXTRASETTINGS']))
		{
			$extrasettings = $arProfile['EXTRASETTINGS'];
		}
		elseif(!$GLOBALS['USER']->IsAdmin())
		{
			if(is_array($extrasettings))
			{
				$arExpressions = array();
				if(isset($arProfile['EXTRASETTINGS']) && is_array($arProfile['EXTRASETTINGS']))
				{
					foreach($arProfile['EXTRASETTINGS'] as $k=>$v)
					{
						if(!is_array($v)) continue;
						foreach($v as $k2=>$v2)
						{
							if(!is_array($v2)) continue;
							$arKeys = array('CONVERSION', 'EXTRA_CONVERSION');
							foreach($arKeys as $convKey)
							{
								if(!array_key_exists($convKey, $v2) || !is_array($v2[$convKey])) continue;
								foreach($v2[$convKey] as $k3=>$v3)
								{
									if($v3['THEN']=='EXPRESSION' && strlen($v3['TO']) > 0 && !in_array($v3['TO'], $arExpressions))
									{
										$arExpressions[] = $v3['TO'];
									}
								}
							}
						}
					}
				}
				
				foreach($extrasettings as $k=>$v)
				{
					if(!is_array($v)) continue;
					foreach($v as $k2=>$v2)
					{
						if(!is_array($v2)) continue;
						$arOldExtra = $arProfile['EXTRASETTINGS'][$k][$k2];
						if(isset($_POST['COL_POSITIONS'][$k][$k2]) && ($arColKeys = explode('_', $_POST['COL_POSITIONS'][$k][$k2])) && count($arColKeys)==2)
						{
							$arOldExtra = $arProfile['EXTRASETTINGS'][$arColKeys[0]][$arColKeys[1]];
						}
						$arKeys = array('CONVERSION', 'EXTRA_CONVERSION');
						foreach($arKeys as $convKey)
						{
							if(!array_key_exists($convKey, $v2) || !is_array($v2[$convKey])) continue;
							foreach($v2[$convKey] as $k3=>$v3)
							{
								if($v3['THEN']=='EXPRESSION' && !in_array($v3['TO'], $arExpressions))
								{
									$extrasettings[$k][$k2][$convKey][$k3]['TO'] = '';
									/*
									$index = (array_key_exists('INDEX', $v3) && strlen($v3['INDEX']) > 0 ? $v3['INDEX'] : $k3);
									if(isset($arOldExtra[$convKey][$index]['TO']))
									{
										$extrasettings[$k][$k2][$convKey][$k3]['TO'] = $arOldExtra[$convKey][$index]['TO'];
									}
									*/
								}
								if(array_key_exists('INDEX', $v3)) unset($extrasettings[$k][$k2][$convKey][$k3]['INDEX']);
							}
						}
					}
				}
			}
		}
	}
	
	public function OnStartExport()
	{
		$this->UpdateFields($this->pid, array(
			'DATE_START' => new \Bitrix\Main\Type\DateTime(),
			'DATE_FINISH' => false
		));
		/*foreach(GetModuleEvents(static::$moduleId, "OnStartExport", true) as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array($this->pid));
		}*/
	}
	
	public function OnEndExport()
	{
		$this->UpdateFields($this->pid, array(
			'DATE_FINISH'=>new \Bitrix\Main\Type\DateTime()
		));
		return true;
	}
	
	public function GetDefaultSiteId()
	{
		$arSite = \CSite::GetList(($by='sort'), ($order='asc'), array('DEFAULT'=>'Y'))->Fetch();
		return $arSite['ID'];
	}
	
	public function OutputBackup()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		
		$fileName = 'profiles_'.date('Y_m_d_H_i_s');
		$tempPath = \CFile::GetTempName('', bx_basename($fileName.'.zip'));
		$dir = rtrim(\Bitrix\Main\IO\Path::getDirectory($tempPath), '/').'/'.$fileName;
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		
		$arFiles = array(
			'config' => $dir.'/config.txt',
			'data' => $dir.'/data.txt'
		);
		
		file_put_contents($arFiles['config'], base64_encode(serialize(
			array(
				'domain' => $_SERVER['HTTP_HOST'],
				'encoding' => Utils::getSiteEncoding()
			)
		)));
		
		$handle = fopen($arFiles['data'], 'a');
		$profileEntity = $this->GetEntity();
		$dbRes = $profileEntity::getList(array('order'=>array('ID'=>'ASC'), 'select'=>array('ID', 'ACTIVE', 'NAME', 'PARAMS', 'SORT')));
		while($arProfile = $dbRes->Fetch())
		{
			foreach($arProfile as $k=>$v)
			{
				$arProfile[$k] = base64_encode($v);
			}
			fwrite($handle, base64_encode(serialize($arProfile))."\r\n");
		}
		fclose($handle);
		
		$zipObj = \CBXArchive::GetArchive($tempPath, 'ZIP');
		$zipObj->SetOptions(array(
			"COMPRESS" =>true,
			"ADD_PATH" => false,
			"REMOVE_PATH" => $dir.'/',
			"CHECK_PERMISSIONS" => false
		));
		$zipObj->Pack($dir.'/');
		
		foreach($arFiles as $file) unlink($file);
		rmdir($dir);
		
		header('Content-type: application/zip');
		header('Content-Transfer-Encoding: Binary');
		header('Content-length: '.filesize($tempPath));
		header('Content-disposition: attachment; filename="'.basename($tempPath).'"');
		readfile($tempPath);
		
		die();
	}
	
	public function RestoreBackup($arPFile, $arParams)
	{
		if(!isset($arPFile) || !is_array($arPFile) || $arPFile['error'] > 0 || $arPFile['size'] < 1)
		{
			return array('TYPE'=>'ERROR', 'MESSAGE'=>Loc::getMessage("ESOL_AE_RESTORE_NOT_LOAD_FILE"));
		}
		$filename = $arPFile['name'];
		if(ToLower(Utils::GetFileExtension($filename))!=='zip')
		{
			return array('TYPE'=>'ERROR', 'MESSAGE'=>Loc::getMessage("ESOL_AE_RESTORE_FILE_NOT_VALID"));
		}
		
		$tempPath = \CFile::GetTempName('', bx_basename($filename));
		$subdir = current(explode('.', $filename));
		if(strlen($subdir)==0) $subdir = 'backup';
		$dir = rtrim(\Bitrix\Main\IO\Path::getDirectory($tempPath), '/').'/'.$subdir;
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		
		$zipObj = \CBXArchive::GetArchive($arPFile['tmp_name'], 'ZIP');
		$zipObj->Unpack($dir.'/');
		
		$arFiles = array(
			'config' => $dir.'/config.txt',
			'data' => $dir.'/data.txt'
		);
		if(!file_exists($arFiles['config']) || !file_exists($arFiles['data']))
		{
			foreach($arFiles as $file) unlink($file);
			rmdir($dir);
			return array('TYPE'=>'ERROR', 'MESSAGE'=>Loc::getMessage("ESOL_AE_RESTORE_FILE_NOT_VALID"));
		}
		
		$profileEntity = $this->GetEntity();
		$encoding = Utils::getSiteEncoding();
		
		if($arParams['RESTORE_TYPE']=='REPLACE')
		{
			$dbRes = $profileEntity::getList();
			while($arProfile = $dbRes->Fetch())
			{
				$profileEntity::delete($arProfile['ID']);
			}
		}
		
		$arConfig = \Bitrix\EsolAie\Utils::Unserialize(base64_decode(file_get_contents($arFiles['config'])));
		$handle = fopen($arFiles['data'], "r");
		while(!feof($handle))
		{
			$buffer = trim(fgets($handle, 16777216));
			if(strlen($buffer) == 0) continue;			
			$arProfile = \Bitrix\EsolAie\Utils::Unserialize(base64_decode($buffer));
			if(!is_array($arProfile)) continue;
			foreach($arProfile as $k=>$v)
			{
				$v = base64_decode($v);
				if($encoding != $arConfig['encoding']) $v = \Bitrix\Main\Text\Encoding::convertEncoding($v, $arConfig['encoding'], $encoding);
				$arProfile[$k] = $v;
			}
			
			if($arParams['RESTORE_TYPE']=='ADD') unset($arProfile['ID']);
			$dbRes = $profileEntity::add($arProfile);
			/*if(!$dbRes->isSuccess())
			{
				$error = '';
				if($dbRes->getErrors())
				{
					foreach($dbRes->getErrors() as $errorObj)
					{
						$error .= $errorObj->getMessage().'. ';
					}
					$APPLICATION->throwException($error);
				}
			}
			else
			{
				$ID = $dbRes->getId();
			}*/
		}
		fclose($handle);
		foreach($arFiles as $file) unlink($file);
		rmdir($dir);
		
		return array('TYPE'=>'SUCCESS', 'MESSAGE'=>Loc::getMessage("ESOL_AE_RESTORE_SUCCESS"));
	}
}