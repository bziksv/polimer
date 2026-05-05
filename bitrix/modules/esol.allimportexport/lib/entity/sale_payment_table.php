<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEPaymentTable extends \Bitrix\Sale\Internals\PaymentTable
{
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Sale\Internals');
		
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
		return '\Bitrix\Sale\Internals\PaymentTable';
	}
	
	public static function getRelTitles()
	{
		return array(
			'RESPONSIBLE_BY' => Loc::getMessage('ESOL_AIE_SPT_RESPONSIBLE_BY'),
			'COMPANY_BY' => Loc::getMessage('ESOL_AIE_SPT_COMPANY_BY'),
			'PAY_SYSTEM' =>  Loc::getMessage('ESOL_AIE_SPT_PAY_SYSTEM')
		);
	}
	
	public static function AfterUpdate($ID, $arFields)
	{
		if($arFields['PAID'])
		{
			$ORDER_ID = $arFields['ORDER_ID'];
			if(!$ORDER_ID && ($arPayment = self::getList(array('filter'=>array('ID'=>$ID), 'select'=>array('ORDER_ID')))->fetch()))
			{
				$ORDER_ID = $arPayment['ORDER_ID'];
			}
			
			if($ORDER_ID && ($order = \Bitrix\Sale\Order::load($ORDER_ID)))
			{
				$paymentCollection = $order->getPaymentCollection();
				$paymentItem = $paymentCollection->getItemById($ID);
				$paymentItem->setField('PAID', ($arFields['PAID']=='Y' ? 'N' : 'Y')); //fix change value
				$paymentItem->setField('PAID', $arFields['PAID']);
				$order->save();
			}
		}
	}
	
	public static function PrepareFieldsCustom(&$arFields)
	{
		if(isset($arFields['SUM'])) $arFields['SUM'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['SUM']);
	}
	
	public static function Add(array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Add($arFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Update($ID, $arFields);
		if($result->isSuccess())
		{
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
}
