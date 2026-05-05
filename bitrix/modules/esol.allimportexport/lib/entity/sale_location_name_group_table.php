<?php
namespace Bitrix\Sale\Location\Name;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEGroupTable extends \Bitrix\Sale\Location\Name\GroupTable
{	
	public static function getMap(): array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		if(array_key_exists('LANGUAGE_ID', $arMap)) unset($arMap['LANGUAGE_ID']);
		if(array_key_exists('GROUP_ID', $arMap)) unset($arMap['GROUP_ID']);
		
		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
}
