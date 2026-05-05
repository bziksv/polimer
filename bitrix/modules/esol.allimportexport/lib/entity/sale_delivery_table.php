<?php
namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEDeliveryTable extends \Bitrix\Sale\Delivery\Services\Table
{
	static $propFields = null;
	static $propFieldTitles = null;
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		if(class_exists('\Bitrix\Sale\Delivery\ExtraServices\Manager') && Loc::getMessage("DELIVERY_SERVICE_MANAGER_ES_NAME"))
		{
			$arMap['IE_STORE_PICKUP'] = new \Bitrix\Main\Entity\ReferenceField(
				'IE_STORE_PICKUP',
				'Bitrix\Sale\Delivery\ExtraServices\Table',
				array(
					'=ref.DELIVERY_ID' => 'this.ID',
					'=ref.CODE' => new \Bitrix\Main\DB\SqlExpression('?i', \Bitrix\Sale\Delivery\ExtraServices\Manager::STORE_PICKUP_CODE)
				)
			);
			
			$arMap['IE_STORE_PICKUP.PARAMS'] = new \Bitrix\Main\Entity\StringField(
				'IE_STORE_PICKUP.PARAMS', 
				array(
					'title' => Loc::getMessage("DELIVERY_SERVICE_MANAGER_ES_NAME")
				)
			);
		}
		
		if(class_exists('\Bitrix\Sale\Internals\ServiceRestrictionTable'))
		{
			$arRestMap = \Bitrix\Sale\Internals\ServiceRestrictionTable::getMap();
			\Bitrix\EsolAie\Entity\Utils::PrepareMap($arRestMap);
			$arRestMapKeys = array_keys($arRestMap);
			$arMap['IE_RESTRICTIONS'] = new \Bitrix\Main\Entity\ReferenceField(
				'IE_RESTRICTIONS',
				'Bitrix\Sale\Internals\ServiceRestrictionTable',
				array(
					'=ref.SERVICE_ID' => 'this.ID'
				)
			);
			
			$arMap['IE_RESTRICTIONS_DATA'] = array(
				'title' => Loc::getMessage("ESOL_AIE_DELIVERY_RESTRICTIONS"),
				'data_type' => 'string',
				'multiple_rels' => 'Y',
				'expression' => array(
					'CONCAT("{SERVICE_TYPE:\'", %s, "\', SORT:\'", %s, "\', CLASS_NAME:\'", %s, "\', PARAMS:\'", IF(%s IS NOT NULL, %s, ""), "\'}")', 'IE_RESTRICTIONS.SERVICE_TYPE', 'IE_RESTRICTIONS.SORT', 'IE_RESTRICTIONS.CLASS_NAME', 'IE_RESTRICTIONS.PARAMS', 'IE_RESTRICTIONS.PARAMS'
				)
			);
		}

		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();

		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(IE_STORE_PICKUP[^\.]*|IE_RESTRICTIONS)$/', array_keys($arMap))));
		if($type=='import')
		{
			unset($arMap['IE_RESTRICTIONS_DATA']['expression']);
		}
		
		return $arMap;
	}
	
	public static function prepareDefaultFields(&$arFields)
	{
		$arFields = array_diff($arFields, preg_grep('/^IE_STORE_PICKUP/', $arFields));
	}
	
	public static function PrepareFieldsForExport(&$arFields, $sep=';')
	{
		if(isset($arFields['IE_STORE_PICKUP/PARAMS']) && is_array($arFields['IE_STORE_PICKUP/PARAMS']) 
			&& isset($arFields['IE_STORE_PICKUP/PARAMS']['STORES']) && is_array($arFields['IE_STORE_PICKUP/PARAMS']['STORES']))
		{
			$arFields['IE_STORE_PICKUP/PARAMS'] = implode(';', $arFields['IE_STORE_PICKUP/PARAMS']['STORES']);
		}
	}
	
	public static function AfterUpdate($ID, $arFields)
	{
		if(isset($arFields['IE_STORE_PICKUP.PARAMS']))
		{
			$stores = array_diff(array_map('intval', explode(';', $arFields['IE_STORE_PICKUP.PARAMS'])), array(0));
			if(!empty($stores))
			{
				\Bitrix\Sale\Delivery\ExtraServices\Manager::saveStores($ID, $stores);
			}
			else
			{
				\Bitrix\Sale\Delivery\ExtraServices\Manager::setStoresUnActive($ID);
			}
		}
		
		if(isset($arFields['IE_RESTRICTIONS_DATA']))
		{
			$arVals = $arValsSer = array();
			$val = trim($arFields['IE_RESTRICTIONS_DATA'], '; ');
			$val = preg_replace('/\}\s*;+\s*\{/', '};{', $val);
			while(($pos = mb_strpos($val, '};{'))!==false)
			{
				$arVals[] = \Bitrix\EsolAie\Utils::JsObjectToPhp(mb_substr($val, 0, $pos+1));
				$val = mb_substr($val, $pos+2);
				$val = trim($val, '; ');
			}
			if(strlen($val) > 0) $arVals[] = \Bitrix\EsolAie\Utils::JsObjectToPhp($val);
			foreach($arVals as $k=>$v)
			{
				if(strlen($v['PARAMS']) > 0) $arVals[$k]['PARAMS'] = $v['PARAMS'] = \Bitrix\EsolAie\Utils::Unserialize($v['PARAMS']);
				else unset($arVals[$k]['PARAMS'], $v['PARAMS']);
				$arValsSer[$k] = serialize($v);
			}
			
			$arOldVals = array();
			$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array('filter'=>array('SERVICE_ID'=>$ID)));
			while($arr = $dbRes->Fetch())
			{
				if(($k = array_search(serialize(array_intersect_key($arr, array_flip(array('SERVICE_TYPE', 'SORT', 'CLASS_NAME', 'PARAMS')))), $arValsSer))!==false)
				{
					unset($arVals[$k], $arValsSer[$k]);
					continue;
				}
				\Bitrix\Sale\Internals\ServiceRestrictionTable::delete($arr['ID']);
			}
			foreach($arVals as $k=>$v)
			{
				$v['SERVICE_ID'] = $ID;
				if(!isset($v['SERVICE_TYPE'])) $v['SERVICE_TYPE'] = '0';
				\Bitrix\Sale\Internals\ServiceRestrictionTable::add($v);
			}
		}
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		/*if(!isset($arFields['DATE_UPDATE'])) $arFields['DATE_UPDATE'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['DATE_INSERT'])) $arFields['DATE_INSERT'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['DATE_STATUS'])) $arFields['DATE_STATUS'] = ConvertTimeStamp(false, "FULL");*/
	}
	
	public static function PrepareFieldsCustom(&$arFields)
	{
		/*$arFields = array_diff_key($arFields, array_flip(preg_grep('/^IE_PROPERTY_/', array_keys($arFields))));
		if(isset($arFields['PRICE'])) $arFields['PRICE'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['PRICE']);
		if(isset($arFields['PRICE_DELIVERY'])) $arFields['PRICE_DELIVERY'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['PRICE_DELIVERY']);
		if(isset($arFields['SUM_PAID'])) $arFields['SUM_PAID'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['SUM_PAID']);*/
	}
	
	public static function GetUpdatableFields($arFields)
	{
		if(isset($arFields['IE_STORE_PICKUP.PARAMS'])) unset($arFields['IE_STORE_PICKUP.PARAMS']);
		if(isset($arFields['IE_RESTRICTIONS_DATA'])) unset($arFields['IE_RESTRICTIONS_DATA']);
		return $arFields;
	}
	
	public static function Add(array $arFields)
	{
		//self::PrepareFieldsCustom($arFields);
		$arUpdateFields = self::GetUpdatableFields($arFields);
		$result = parent::Add($arUpdateFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		//self::PrepareFieldsCustom($arFields);
		$arUpdateFields = self::GetUpdatableFields($arFields);
		if((empty($arUpdateFields) && ($result = new \Bitrix\Main\Entity\UpdateResult()))
			|| (($result = parent::Update($ID, $arUpdateFields)) && $result->isSuccess()))
		{
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
}
