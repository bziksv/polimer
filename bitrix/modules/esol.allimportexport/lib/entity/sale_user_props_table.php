<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEUserPropsTable extends \Bitrix\Sale\Internals\UserPropsTable
{
	static $propFields = null;
	static $orderProps = array();
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);

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
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();

		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^IE_USERPROPS_[^\.]*$/', array_keys($arMap))));
		
		return $arMap;
	}
	
	public static function prepareDefaultFields(&$arFields)
	{
		$arFields = array_diff($arFields, preg_grep('/^IE_USERPROPS_/', $arFields));
	}
	
	public static function initPropertyFields()
	{
		if(!isset(self::$propFields))
		{
			self::$propFields = array();
			$dbRes = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('order'=>array('ID'=>'ASC')));
			while($arr = $dbRes->Fetch())
			{
				self::$propFields['IE_USERPROPS_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
					'IE_USERPROPS_'.$arr['ID'],
					'Bitrix\Sale\Internals\UserPropsValueTable',
					array(
						'=ref.USER_PROPS_ID' => 'this.ID',
						'=ref.ORDER_PROPS_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
					)
				);
				self::$propFields['IE_USERPROPS_'.$arr['ID'].'.VALUE'] = new \Bitrix\Main\Entity\StringField(
					'IE_USERPROPS_'.$arr['ID'].'.VALUE', 
					array(
						'title' => Loc::getMessage("ESOL_AE_FL_ORDER_PROP").' '.$arr['NAME'].' ['.$arr['ID'].']'
					)
				);
			}
		}
	}
	
	public static function SaveRelatedEntities($ID, $arFieldsElement, $secondUpdate=false)
	{
		$arPropKeys = array_map(array(__CLASS__, 'GetOrderPropertyId'), preg_grep('/^IE_USERPROPS_\d+\.VALUE$/', array_keys($arFieldsElement)));
		if(!empty($arPropKeys))
		{
			$arProps = array();
			$dbRes = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('order'=>array('ID'=>'ASC'), 'filter'=>array('ID'=>$arPropKeys)));
			while($arr = $dbRes->Fetch())
			{
				$arProps[$arr['ID']] = $arr;
			}
			
			$arUserProps = array();
			$dbRes = \Bitrix\Sale\Internals\UserPropsValueTable::getList(array('filter'=>array('USER_PROPS_ID'=>$ID, 'ORDER_PROPS_ID'=>$arPropKeys)));
			while($arr = $dbRes->Fetch())
			{
				$arUserProps[$arr['ORDER_PROPS_ID']] = $arr;
			}
			
			foreach($arProps as $propKey=>$arProp)
			{
				$propVal = trim($arFieldsElement['IE_USERPROPS_'.$propKey.'.VALUE']);
				if($arProp['MULTIPLE']=='Y' && $secondUpdate)
				{
					$oldVal = (is_array($arUserProps[$propKey]['VALUE']) ? $arUserProps[$propKey]['VALUE'] : array($arUserProps[$propKey]['VALUE']));
					if(strlen($propVal) > 0) $propVal = array_merge($oldVal, array($propVal));
					else $propVal = $oldVal;
				}
				
				if(isset($arUserProps[$propKey]))
				{
					if(strlen($propVal) > 0 || is_array($propVal)) \Bitrix\Sale\Internals\UserPropsValueTable::update($arUserProps[$propKey]['ID'], array('VALUE'=>$propVal));
					else \Bitrix\Sale\Internals\UserPropsValueTable::delete($arUserProps[$propKey]['ID']);
				}
				elseif(strlen($propVal) > 0)
				{
					\Bitrix\Sale\Internals\UserPropsValueTable::add(array('USER_PROPS_ID'=>$ID, 'ORDER_PROPS_ID'=>$propKey, 'NAME'=>$arProp['NAME'], 'VALUE'=>$propVal));
				}
			}
		}
	}
	
	public static function GetOrderPropertyId($n)
	{
		return substr($n, 13, -6);
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['DATE_UPDATE'])) $arFields['DATE_UPDATE'] = ConvertTimeStamp(false, "FULL");
	}
	
	public static function PrepareFieldsCustom(&$arFields)
	{
		$arFields = array_diff_key($arFields, array_flip(preg_grep('/^IE_USERPROPS_/', array_keys($arFields))));
	}

	static function CheckCntFilterFields($fieldName)
	{
		if(strpos($fieldName, 'IE_USERPROPS_')!==false) return false;
		return true;
	}
	
	public static function Add(array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Add($arFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
		}
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Update($ID, $arFields);
		if($result->isSuccess())
		{

		}
		return $result;
	}
}
