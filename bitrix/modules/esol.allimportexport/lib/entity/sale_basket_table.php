<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIESaleBasketTable extends \Bitrix\Sale\Internals\BasketTable
{
	static $propFields = null;
	static $propFieldTitles = null;
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Sale\Internals');
		if(isset($arMap['PRODUCT']) && is_array($arMap['PRODUCT']))
		{
			$arMap['PRODUCT']['data_type'] = '\Bitrix\Sale\Internals\EsolIEProductTable';
		}
		if(isset($arMap['SHIPMENT']) && is_array($arMap['SHIPMENT']))
		{
			$arMap['SHIPMENT']['data_type'] = '\Bitrix\Sale\Internals\EsolIEShipmentTable';
		}
		
		$arMap['IE_IBLOCK_ELEMENT'] = new \Bitrix\Main\Entity\ReferenceField(
			'IE_IBLOCK_ELEMENT',
			'\Bitrix\Iblock\ElementTable',
			array(
				'=ref.ID' => 'this.PRODUCT_ID'
			)
		);
		
		$arMap['IE_SHIPMENT_ITEM_STORE'] = new \Bitrix\Main\Entity\ReferenceField(
			'IE_SHIPMENT_ITEM_STORE',
			'\Bitrix\Sale\Internals\ShipmentItemStoreTable',
			array(
				'=ref.BASKET_ID' => 'this.ID'
			)
		);
		
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
		
		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^IE_BASKETPROPERTY_[^\.]*$/', array_keys($arMap))));
		
		if(isset($arMap['PRODUCT']) && is_array($arMap['PRODUCT']) && \Bitrix\Main\Loader::includeModule('catalog'))
		{
			$arMap['PRODUCT']['ALLOW_REFS'] = array();
			$arProductMap = \Bitrix\Catalog\EsolIEProductTable::getMap();
			$arProductKeys = array_keys($arProductMap);
			//$arMap['PRODUCT']['ALLOW_REFS'] = array_merge($arMap['PRODUCT']['ALLOW_REFS'], preg_grep('/^(IE_PRODUCTPROPERTY_|IE_PRODUCTPROPTABLE_)\d+$/', $arProductKeys));
			$arMap['PRODUCT']['ALLOW_REFS'] = array_merge($arMap['PRODUCT']['ALLOW_REFS'], preg_grep('/^(IE_PRODUCTPROPERTY_)\d+$/', $arProductKeys));
			if(empty($arMap['PRODUCT']['ALLOW_REFS'])) unset($arMap['PRODUCT']['ALLOW_REFS']);
		}

		return $arMap;
	}
	
	public static function getRelTitles()
	{
		self::initPropertyFields();
		$arResult = self::$propFieldTitles;
		if(!is_array($arResult)) $arResult = array();
		return $arResult;
	}
	
	public static function prepareDefaultFields(&$arFields)
	{
		$arFields = array_diff($arFields, preg_grep('/^IE_BASKETPROPERTY_/', $arFields));
	}
	
	public static function initPropertyFields()
	{
		if(!isset(self::$propFields))
		{
			self::$propFields = array();
			self::$propFieldTitles = array();
			$dbRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList(array('select'=>array('NAME', 'CODE'), 'order'=>array('ID'=>'DESC'), 'limit'=>1000));
			$arProps = array();
			while($arr = $dbRes->Fetch())
			{
				$code = preg_replace('/\W/', '', $arr['CODE']);
				if(strlen($code)===0) continue;
				if(array_key_exists($code, $arProps)) continue;
				
				self::$propFields['IE_BASKETPROPERTY_'.$code] = new \Bitrix\Main\Entity\ReferenceField(
					'IE_BASKETPROPERTY_'.$code,
					'Bitrix\Sale\Internals\BasketPropertyTable',
					array(
						'=ref.BASKET_ID' => 'this.ID',
						'=ref.CODE' => new \Bitrix\Main\DB\SqlExpression('?s', $arr['CODE'])
					)
				);
				self::$propFields['IE_BASKETPROPERTY_'.$code.'.VALUE'] = new \Bitrix\Main\Entity\StringField(
					'IE_BASKETPROPERTY_'.$code.'.VALUE', 
					array(
						'title' => Loc::getMessage("ESOL_AE_FL_BASKET_PROP").' '.$arr['NAME'].' ['.$arr['CODE'].']'
					)
				);
				
				$arProps[$code] = $arr;
			}
		}
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['DATE_UPDATE'])) $arFields['DATE_UPDATE'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['DATE_INSERT'])) $arFields['DATE_INSERT'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['MODULE'])) $arFields['MODULE'] = 'catalog';
		if(!isset($arFields['PRODUCT_PROVIDER_CLASS'])) $arFields['PRODUCT_PROVIDER_CLASS'] = '\Bitrix\Catalog\Product\CatalogProvider';
	}
	
	public static function PrepareFieldsCustom(&$arFields, $new=false)
	{
		if(isset($arFields['PRICE'])) $arFields['PRICE'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['PRICE']);
		if(isset($arFields['DISCOUNT_PRICE'])) $arFields['DISCOUNT_PRICE'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['DISCOUNT_PRICE']);
		if(isset($arFields['BASE_PRICE'])) $arFields['BASE_PRICE'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['BASE_PRICE']);
		if(isset($arFields['QUANTITY'])) $arFields['QUANTITY'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['QUANTITY']);
		if($new && (!isset($arFields['PRODUCT_ID']) || !is_numeric($arFields['PRODUCT_ID']))) $arFields['PRODUCT_ID'] = 0;
	}
	
	public static function Add(array $arFields)
	{
		self::PrepareFieldsCustom($arFields, true);
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
