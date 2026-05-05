<?php
namespace Bitrix\Iblock;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEElementTable extends \Bitrix\Iblock\ElementTable
{
	static $propFields = null;
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		
		self::initPropertyFields();
		if(is_array(self::$propFields))
		{
			foreach(self::$propFields as $k=>$v)
			{
				$arMap[$k] = $v;
			}
		}
		
		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
	
	public static function initPropertyFields()
	{
		if(!isset(self::$propFields))
		{
			self::$propFields = array();
			
			$dbRes = \Bitrix\Iblock\PropertyTable::getList(array('order'=>array('IBLOCK_ID'=>'ASC', 'SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>array('PROPERTY_TYPE'=>array('S', 'N', 'L', 'E')), 'select'=>array('ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_NAME'=>'IBLOCK.NAME'), 'runtime'=>array(
				new \Bitrix\Main\Entity\ReferenceField(
					'IBLOCK',
					'Bitrix\Iblock\IblockTable',
					array(
						'=this.IBLOCK_ID' => 'ref.ID'
					)
				)
			)));
			
			while($arr = $dbRes->Fetch())
			{
				self::$propFields['PROPERTY_'.$arr['ID']] = array(
					'title' => Loc::getMessage("ESOL_AIE_FL_ELEMENT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']',
					'data_type' => 'string',
				);
			}
		}
	}
	
	public static function getList(array $parameters = array())
	{
		$arPropFilter = array();
		if(array_key_exists('filter', $parameters))
		{
			foreach($parameters['filter'] as $k=>$v)
			{
				if(preg_match('/PROPERTY_\d+$/', $k))
				{
					$arPropFilter[$k] = $v;
					unset($parameters['filter'][$k]);
				}
			}
		}
		
		if(count($arPropFilter))
		{
			$arIds = array(0);
			$dbRes = \CIblockElement::GetList(array(), $arPropFilter, false, false, array('ID'));
			while($arr = $dbRes->Fetch())
			{
				$arIds[] = $arr['ID'];
			}
			$parameters['filter']['ID'] = $arIds;
		}
		
		return parent::getList($parameters);
	}
}
