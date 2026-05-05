<?php
namespace Sotbit\Seometa;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

if(!class_exists('\Sotbit\Seometa\SitemapSectionTable') && class_exists('\Sotbit\Seometa\Orm\SitemapSectionTable'))
{
	class SitemapSectionTable extends \Sotbit\Seometa\Orm\SitemapSectionTable
	{
	}
}

class EsolIESitemapSectionTable extends \Sotbit\Seometa\SitemapSectionTable
{	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['SORT'])) $arFields['SORT'] = 100;
		if(!isset($arFields['PARENT_CATEGORY_ID'])) $arFields['PARENT_CATEGORY_ID'] = 0;
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