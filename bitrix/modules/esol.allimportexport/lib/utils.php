<?php
namespace Bitrix\EsolAie;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Utils {
	public static function Unserialize($val, $allowedClasses=false)
	{
		if($allowedClasses===true)
		{
			if(preg_match_all('/O:\d+:"([^"]+)"/', $val, $m))
			{
				$allowedClasses = array_unique($m[1]);
			}
		}
		elseif($allowedClasses!==false && !is_array($allowedClasses))
		{
			$allowedClasses = array($allowedClasses);
		}
		if(!is_array($allowedClasses)) $allowedClasses = [];
		return unserialize($val, ['allowed_classes'=>$allowedClasses]);
	}
	
	public static function PhpToJSObject($arData)
	{
		$data = '';
		if(is_callable(array('\Bitrix\Main\Web\Json', 'encode')))
		{
			$data = \Bitrix\Main\Web\Json::encode($arData);
		}
		else
		{
			$data = \CUtil::PhpToJSObject($arData);
		}
		return $data;
	}
	
	public static function JsObjectToPhp($data)
	{
		if(strlen(trim($data))==0) return array();
		$arResult = null;
		if(is_callable(array('\Bitrix\Main\Web\Json', 'decode')))
		{
			try
			{
				$arResult = \Bitrix\Main\Web\Json::decode($data);
			}
			catch(\Throwable $exception)
			{
				//echo $exception->getMessage();
			}
		}
		if($arResult === null)
		{
			try
			{
				$arResult = \CUtil::JsObjectToPhp($data, true);
			}
			catch(\Throwable $exception)
			{
				//echo $exception->getMessage();
			}
		}
		if($arResult === null)
		{
			$arResult = array();
		}
		return $arResult;
	}
	
	public static function IsUtfMode()
	{
		if(is_callable(array('\Bitrix\Main\Application', 'isUtfMode')))
		{
			return \Bitrix\Main\Application::isUtfMode();
		}
		return (bool)(defined('BX_UTF') && BX_UTF);
	}
}
?>