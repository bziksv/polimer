<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class Utils
{
	public static function EntityTableExists($entity)
	{
		$entityClass = \Bitrix\EsolAie\Runner::GetEntityClassByKey($entity);
		return (bool)$entityClass;
	}
	
	public static function GetFieldKey($key, $field)
	{
		if(is_numeric($key) && is_object($field))
		{
			if(is_callable(array($field, 'getColumnName'))) return $field->getColumnName();
			if(is_callable(array($field, 'getTitle'))) return $field->getTitle();
		}
		return $key;
	}
	
	public static function GetAltClass($entityClass, $ns='')
	{
		if(strlen($entityClass) < 2) return $entityClass;
		$arRels = array(
			'\Bitrix\Main\UserTable' => '\Bitrix\Main\EsolIEUserTable',
			'\Bitrix\Sale\Internals\OrderTable' => '\Bitrix\Sale\Internals\EsolIEOrderTable',
			'\Bitrix\Sale\Internals\OrderPropsValueTable' => '\Bitrix\Sale\Internals\EsolIEOrderPropsValueTable',
			'\Bitrix\Sale\Internals\BasketTable' => '\Bitrix\Sale\Internals\EsolIESaleBasketTable'
		);
		if(strlen($ns) > 0 && strpos($entityClass, '\\')===false)
		{
			$entityClass = '\\'.trim($ns, '\\').'\\'.$entityClass;
		}
		$entityClass = \Bitrix\Main\Entity\Base::normalizeEntityClass($entityClass);
		
		if(isset($arRels[$entityClass])) return $arRels[$entityClass];
		else return $entityClass;
	}
	
	public static function PrepareMap(&$arMap, $ns='')
	{
		
		foreach($arMap as $k=>$v)
		{
			if(is_object($v) && ($v instanceof \Bitrix\Main\Entity\ReferenceField))
			{
				$arMap[$k] = new \Bitrix\Main\Entity\ReferenceField(
					$v->getName(),
					self::GetAltClass($v->getRefEntity()->getDataClass(), $ns),
					$v->getReference(),
					array('join_type' => $v->getJoinType())
				);
			}
			elseif(is_array($v) && isset($v['reference']) && isset($v['data_type']))
			{
				$arMap[$k]['data_type'] = self::GetAltClass($v['data_type'], $ns);
			}
		}
	}
	
	public static function GetFloatVal($val, $precision=0)
	{
		if(is_array($val)) $val = current($val);
		$val = floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
		if($precision > 0) $val = round($val, $precision);
		return $val;
	}
}