<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

if(!class_exists('\Sotbit\Seometa\SeometaUrlTable') && class_exists('\Sotbit\Seometa\Orm\SeometaUrlTable'))
{
	class SeometaUrlTable extends \Sotbit\Seometa\Orm\SeometaUrlTable
	{
	}
}

class EsolIESitemapUrlTable extends \Sotbit\Seometa\SeometaUrlTable
{	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['CATEGORY_ID'])) $arFields['CATEGORY_ID'] = 0;
		if(!isset($arFields['CONDITION_ID'])) $arFields['CONDITION_ID'] = 0;
		if(!isset($arFields['iblock_id'])) $arFields['iblock_id'] = 0;
		if(!isset($arFields['section_id'])) $arFields['section_id'] = 0;
		if(!isset($arFields['PRODUCT_COUNT'])) $arFields['PRODUCT_COUNT'] = 0;
		if(!isset($arFields['IN_SITEMAP'])) $arFields['IN_SITEMAP'] = 'N';
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