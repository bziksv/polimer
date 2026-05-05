<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

if(!class_exists('\Sotbit\Seometa\ConditionTable') && class_exists('\Sotbit\Seometa\Orm\ConditionTable'))
{
	class ConditionTable extends \Sotbit\Seometa\Orm\ConditionTable
	{
	}
}

class EsolIEConditionTable extends \Sotbit\Seometa\ConditionTable
{
	public static function PrepareFieldsForExport(&$arFields, $sep=';')
	{
		$arKeys = array('SITES', 'SECTIONS', 'RULE', 'META');
		foreach($arKeys as $key)
		{
			if(isset($arFields[$key]))
			{
				$arVal = \Bitrix\EsolAie\Utils::Unserialize($arFields[$key], true);
				if(is_array($arVal))
				{
					$arFields[$key] = \Bitrix\EsolAie\Utils::PhpToJsObject($arVal);
				}
			}
		}
	}
	
	public static function PrepareFieldsForImport(&$arFields)
	{
		$arKeys = array('SITES', 'SECTIONS', 'RULE', 'META');
		foreach($arKeys as $key)
		{
			if(isset($arFields[$key]))
			{
				$arVal = \Bitrix\EsolAie\Utils::JsObjectToPhp($arFields[$key]);
				if(is_array($arVal))
				{
					$arFields[$key] = serialize($arVal);
				}
			}
		}
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['SORT'])) $arFields['SORT'] = 100;
		if(!isset($arFields['FILTER_TYPE'])) $arFields['FILTER_TYPE'] = 'default';
		if(!isset($arFields['CHANGEFREQ'])) $arFields['CHANGEFREQ'] = 'always';
		if(!isset($arFields['DATE_CHANGE'])) $arFields['DATE_CHANGE'] = new \Bitrix\Main\Type\DateTime(ConvertTimeStamp(time(), 'FULL'));
	}
	
	/*public static function Add(array $arFields)
	{
		$result = parent::Add($arFields);
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$result = parent::Update($ID, $arFields);
		return $result;
	}*/
}