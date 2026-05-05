<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIESectionPropertyTable extends \Bitrix\Iblock\SectionPropertyTable
{
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
	
	public static function Add(array $arFields)
	{
		/*if(strlen($arFields['SECTION_ID']) > 0 && strlen($arFields['PROPERTY_ID']) > 0)
		{
			\CIBlockSectionPropertyLink::Set($arFields['SECTION_ID'], $arFields['PROPERTY_ID'], $arFields);
			$arResult = self::getList(array('filter'=>array('SECTION_ID'=>$arFields['SECTION_ID'], 'PROPERTY_ID'=>$arFields['PROPERTY_ID']), 'select'=>array('SECTION_ID', 'PROPERTY_ID')))->Fetch();
			$result = new \Bitrix\Main\Entity\AddResult();
			if($arResult)
			{
				$result->setId($arResult);
			}
		}
		else $result = parent::Add($arFields);*/
		
		$result = parent::Add($arFields);
		//if($arFields['IBLOCK_ID']) \Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($arFields['IBLOCK_ID']);
		
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		/*if(is_array($ID) && strlen($ID['SECTION_ID']) > 0 && strlen($ID['PROPERTY_ID']) > 0)
		{
			\CIBlockSectionPropertyLink::Set($ID['SECTION_ID'], $ID['PROPERTY_ID'], $arFields);
			$result = new \Bitrix\Main\Entity\UpdateResult();
		}
		else $result = parent::Update($ID, $arFields);*/
		
		$result = parent::Update($ID, $arFields);
		//if($ID['IBLOCK_ID']) \Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($ID['IBLOCK_ID']);
		
		return $result;
	}
	
	public static function Delete($ID)
	{		
		$result = parent::Delete($ID, $arFields);
		//if($ID['IBLOCK_ID']) \Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($ID['IBLOCK_ID']);
		
		return $result;
	}
}
