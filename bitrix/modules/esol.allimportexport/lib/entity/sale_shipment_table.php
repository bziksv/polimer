<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEShipmentTable extends \Bitrix\Sale\Internals\ShipmentTable
{
	public static function getMap(): array
	{
		$arMap = parent::getMap();		
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Sale\Internals');
		$arMap['IE_COMPANY_BY_NAME'] = array(
			'title' => Loc::getMessage('ESOL_AIE_SHIPMENT_FIELD_COMPANY_BY_NAME'),
			'data_type' => 'float',
			'expression' => array(
				'%s', 'COMPANY_BY.NAME'
			)
		);
		$arMap['IE_CALCULATE_DELIVERY_PRICE'] = array(
			'title' => Loc::getMessage('ESOL_AIE_SHIPMENT_FIELD_CALCULATE_DELIVERY_PRICE'),
			'data_type' => 'string',
			'expression' => array(
				'CONCAT(%s, "_", %s)', 'ID', 'ORDER_ID'
			)
		);
		
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
		if(isset($arMap['ORDER']) && is_array($arMap['ORDER']))
		{
			$arMap['ORDER']['ALLOW_REFS'] = array('USER', 'BASKET');
			$arOrderMap = \Bitrix\Sale\Internals\EsolIEOrderTable::getMap();
			$arOrderKeys = array_keys($arOrderMap);
			$arMap['ORDER']['ALLOW_REFS'] = array_merge($arMap['ORDER']['ALLOW_REFS'], preg_grep('/^IE_ORDERPROPERTY_\d+$/', $arOrderKeys));
		}
		return $arMap;
	}
	
	public static function GetParentClass()
	{
		return '\Bitrix\Sale\Internals\ShipmentTable';
	}
	
	/*public static function getRelTitles()
	{
		return array(
			'RESPONSIBLE_BY' => Loc::getMessage('ESOL_AIE_SPT_RESPONSIBLE_BY'),
			'COMPANY_BY' => Loc::getMessage('ESOL_AIE_SPT_COMPANY_BY'),
			'PAY_SYSTEM' =>  Loc::getMessage('ESOL_AIE_SPT_PAY_SYSTEM')
		);
	}*/
	
	public static function PrepareFieldsCustom(&$arFields)
	{
		//if(isset($arFields['SUM'])) $arFields['SUM'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['SUM']);
	}
	
	public static function PrepareExportField_IE_CALCULATE_DELIVERY_PRICE($field, $val, $arSettings, $arItem)
	{
		while(is_array($val))
		{
			$val = array_shift($val);
		}
		if(strlen($val) > 0)
		{
			list($ID, $ORDER_ID) = explode('_', $val);
			$val = '';
			if($ID && $ORDER_ID)
			{
				$order = \Bitrix\Sale\Order::load($ORDER_ID);
				foreach($order->getShipmentCollection() as $shipment)
				{
					if($shipment->getId()==$ID)
					{
						$val = \Bitrix\Sale\Delivery\Services\Manager::calculateDeliveryPrice($shipment)->getDeliveryPrice();
					}
				}
			}
		}
		return $val;
	}
	
	public static function Add(array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Add($arFields);
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Update($ID, $arFields);
		return $result;
	}
}
