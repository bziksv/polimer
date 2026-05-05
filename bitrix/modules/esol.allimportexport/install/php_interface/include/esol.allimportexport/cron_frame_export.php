<?
if(!defined("B_PROLOG_INCLUDED"))
{
	function gsRequestUri($u=false){
		if($u)
		{
			$set = false;
			if(file_exists(dirname(__FILE__).'/.u') && file_get_contents(dirname(__FILE__).'/.u')=='0') $set = true;
			if(!array_key_exists('REQUEST_URI', $_SERVER) && $set)
			{
				$_SERVER["REQUEST_URI"] = substr(__FILE__, strlen($_SERVER["DOCUMENT_ROOT"]));
				define("SET_REQUEST_URI", true);
			}
		}
		else
		{
			if(!defined('BITRIX_INCLUDED'))
			{
				file_put_contents(dirname(__FILE__).'/.u', (defined("SET_REQUEST_URI") ? '1' : '0'));
			}
		}
	}
	register_shutdown_function('gsRequestUri');
	@set_time_limit(0);
	if(!defined('NOT_CHECK_PERMISSIONS')) define('NOT_CHECK_PERMISSIONS', true);
	if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
	if(!defined('BX_CRONTAB')) define("BX_CRONTAB", true);
	if(!defined('ADMIN_SECTION')) define("ADMIN_SECTION", true);
	if(!ini_get('date.timezone') && function_exists('date_default_timezone_set')){@date_default_timezone_set("Europe/Moscow");}
	$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__).'/../../../..');
	gsRequestUri(true);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	if(!defined('BITRIX_INCLUDED')) define("BITRIX_INCLUDED", true);
}

@set_time_limit(0);
$moduleId = 'esol.allimportexport';
$moduleRunnerClass = '\Bitrix\EsolAie\Runner';
\Bitrix\Main\Loader::includeModule($moduleId);
$PROFILE_ID = htmlspecialcharsbx($argv[1]);
parse_str(htmlspecialcharsbx($argv[2]), $arInputParams);

/*Remove old dirs*/
\Bitrix\EsolAie\Export\Utils::RemoveTmpFiles(0);
/*/Remove old dirs*/

$arProfiles = array_map('trim', explode(',', $PROFILE_ID));
foreach($arProfiles as $PROFILE_ID)
{
	if(strlen($PROFILE_ID)==0)
	{
		echo date('Y-m-d H:i:s').": profile id is not set\r\n";
		continue;
	}
	
	$oProfile = \Bitrix\EsolAie\Export\Profile::getInstance();
	$arProfileFields = $oProfile->GetFieldsByID($PROFILE_ID);
	if($arProfileFields['ACTIVE']=='N')
	{
		echo date('Y-m-d H:i:s').": profile is not active\r\n"."Profile id = ".$PROFILE_ID."\r\n\r\n";
		continue;
	}
	
	$arParams = $oProfile->GetProccessParamsFromPidFile($PROFILE_ID);
	if($arParams===false)
	{
		echo date('Y-m-d H:i:s').": export in process\r\n"."Profile id = ".$PROFILE_ID."\r\n\r\n";
		continue;
	}

	$SETTINGS_DEFAULT = $SETTINGS = $EXTRASETTINGS = null;
	$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $PROFILE_ID);
	$oProfile->ApplyExtra($EXTRASETTINGS, $PROFILE_ID);
	$params = array_merge($SETTINGS_DEFAULT, $SETTINGS);
	$params['MAX_EXECUTION_TIME'] = 0;
	if(is_array($arInputParams)) $params = array_merge($params, $arInputParams);

	$arParams = array('EXPORT_MODE'=>'CRON');
	$arResult = $moduleRunnerClass::DoExport($arProfileFields['ENTITY'], $params, $EXTRASETTINGS, $arParams, $PROFILE_ID);

	echo date('Y-m-d H:i:s').": export complete\r\n"."Profile id = ".$PROFILE_ID."\r\n".\Bitrix\EsolAie\Utils::PhpToJSObject($arResult)."\r\n\r\n";
}
?>