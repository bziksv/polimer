<?php
namespace Bitrix\Sale\Location;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEGroupTable extends \Bitrix\Sale\Location\GroupTable
{	
	public static function getMap(): array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		if(array_key_exists('NAME', $arMap) && isset($arMap['NAME']['data_type']))
		{
			$arMap['NAME']['data_type'] = '\Bitrix\Sale\Location\Name\EsolIEGroupTable';
		}
		if(array_key_exists('LOCATION', $arMap))
		{
			unset($arMap['LOCATION']);
		}
		
		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
}
