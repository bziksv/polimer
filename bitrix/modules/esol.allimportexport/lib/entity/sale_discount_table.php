<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class SaleDiscountTable extends \Bitrix\Sale\Internals\DiscountTable
{
	static $lastImportId = null;
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		
		$arMap['IE_GROUP'] = array(
			'data_type' => '\Bitrix\Sale\Internals\DiscountGroupTable',
			'reference' => array(
				'=this.ID' => 'ref.DISCOUNT_ID',
			)
		);
		
		$arMap['IE_GROUP.GROUP_ID'] = new \Bitrix\Main\Entity\StringField(
			'IE_GROUP.GROUP_ID', 
			array(
				'title' => Loc::getMessage("ESOL_AIE_FL_SALE_DISCOUNT_GROUPS")
			)
		);
		
		return $arMap;
	}
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();
		unset(
			$arMap['DISCOUNT_VALUE'],
			$arMap['DISCOUNT_TYPE'],
			$arMap['PRICE_FROM'],
			$arMap['PRICE_TO'],
			$arMap['UNPACK'],
			$arMap['APPLICATION'],
			$arMap['IE_GROUP']
		);

		return $arMap;
	}
	
	/*
    public static function getUfId(): string
    {
        return '';
    }
	*/
	
	public static function Add(array $arFields)
	{
		global $APPLICATION;
		self::PrepareSerializedFields($arFields, true);
		if(!isset($arFields['ACTIVE'])) $arFields['ACTIVE'] = 'Y';

		$ID = \CSaleDiscount::Add($arFields);
		$result = new \Bitrix\Main\Entity\AddResult();
		if($ID > 0)
		{
			$result->setId($ID);
			self::$lastImportId = $ID;
		}
		elseif($ex = $APPLICATION->GetException())
		{
			$strError = $ex->GetString();
			$result->addError(new \Bitrix\Main\Error($strError));
		}			

		return $result;

		/*$result = parent::Add($arFields);
		return $result;*/
	}
	
	public static function Update($ID, array $arFields)
	{
		global $APPLICATION;
		self::PrepareSerializedFields($arFields);
		
		if(array_key_exists('USER_GROUPS', $arFields))
		{
			if(self::$lastImportId==$ID)
			{
				if(!is_array($arFields['USER_GROUPS'])) $arFields['USER_GROUPS'] = array($arFields['USER_GROUPS']);
				$dbRes = \Bitrix\Sale\Internals\DiscountGroupTable::getList(array('filter'=>array('=DISCOUNT_ID'=>$ID), 'select'=>array('GROUP_ID')));
				while($arr = $dbRes->Fetch())
				{
					if(!in_array($arr['GROUP_ID'], $arFields['USER_GROUPS']))
					{
						$arFields['USER_GROUPS'][] = $arr['GROUP_ID'];
					}
				}
			}
			
			if(!isset($arFields['ACTIVE']) && ($arDiscount = self::getList(array('filter'=>array('ID'=>$ID), 'select'=>array('ACTIVE')))->Fetch()))
			{
				$arFields['ACTIVE'] = $arDiscount['ACTIVE'];
			}
		}
		
		$result = new \Bitrix\Main\Entity\UpdateResult();
file_put_contents(dirname(__FILE__).'/test.txt', print_r($arFields, true));
		$res = \CSaleDiscount::Update($ID, $arFields);
		if($res)
		{
			self::$lastImportId = $ID;
		}
		elseif($ex = $APPLICATION->GetException())
		{
			$strError = $ex->GetString();
			$result->addError(new \Bitrix\Main\Error($strError));
		}			

		return $result;
		
		/*$result = parent::Update($ID, $arFields);
		return $result;*/
	}
	
	public static function PrepareFieldsForImport(&$arFields, $sep=';')
	{
		if(array_key_exists('IE_GROUP.GROUP_ID', $arFields))
		{
			$arFields['USER_GROUPS'] = $arFields['IE_GROUP.GROUP_ID'];
			if(!is_array($arFields['USER_GROUPS'])) $arFields['USER_GROUPS'] = explode($sep, $arFields['USER_GROUPS']);
			unset($arFields['IE_GROUP.GROUP_ID']);
		}
	}
	
	public static function PrepareSerializedFields(&$arFields, $bAdd = false)
	{		
		$arKeys = array('CONDITIONS_LIST', 'ACTIONS_LIST');
		foreach($arKeys as $key)
		{
			if(isset($arFields[$key]))
			{
				$key2 = substr($key, 0, -5);
				$arFields[$key2] = $arFields[$key];
				if(is_string($arFields[$key]))
				{
					$arFields[$key2] = \Bitrix\EsolAie\Utils::Unserialize($arFields[$key2]);
					if(!is_array($arFields[$key2])) $arFields[$key2] = array();
				}
				unset($arFields[$key]);
			}
		}
		
		if($bAdd && !isset($arFields['USER_GROUPS']))
		{
			$arFields['USER_GROUPS'] = array();
			$dbRes = \Bitrix\Main\GroupTable::getList(array('select'=>array('ID')));
			while($arr = $dbRes->Fetch())
			{
				$arFields['USER_GROUPS'][] = $arr['ID'];
			}
		}
	}
}
