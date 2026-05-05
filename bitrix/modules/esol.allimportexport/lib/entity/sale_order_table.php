<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class EsolIEOrderTable extends \Bitrix\Sale\Internals\OrderTable
{
	static $propFields = null;
	static $propFieldTitles = null;
	static $orderProps = array();
	protected static $profParams = array();
	protected static $propsEntityType = null;
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		$arMap['SUM_PURCHASING_PRICE'] = array(
			'title' => Loc::getMessage('ESOL_AIE_ORDER_SUM_PURCHASE_PRICE'),
			'data_type' => 'float',
			'expression' => array(
				'SUM(%s*%s)','BASKET.PRODUCT.PURCHASING_PRICE','BASKET.QUANTITY'
			)
		);
		
		$arMap['IE_DELIVERY'] = array(
			'data_type' => '\Bitrix\Sale\Delivery\Services\Table',
			'reference' => array(
				'=this.DELIVERY_ID' => 'ref.ID',
			)
		);
		
		$arMap['IE_PAYMENT'] = array(
			'data_type' => '\Bitrix\Sale\Internals\PaySystemActionTable',
			'reference' => array(
				'=this.PAY_SYSTEM_ID' => 'ref.ID',
			)
		);
		
		foreach($arMap as $key=>$field)
		{
			if(is_callable(array($field, 'getName')) && $field->getName()=='SHIPMENT')
			{
				$arMap[$key] = new \Bitrix\Main\Entity\ReferenceField(
					'SHIPMENT',
					'\Bitrix\Sale\Internals\EsolIEShipmentTable',
					array(
						'=ref.ORDER_ID' => 'this.ID',
						/*'=ref.SYSTEM' => new \Bitrix\Main\DB\SqlExpression('?s', 'N')*/
					)
				);
			}
		}

		self::initPropertyFields();
		if(is_array(self::$propFields))
		{
			foreach(self::$propFields as $k=>$v)
			{
				$arMap[$k] = $v;
			}
		}
		
		if(!isset(self::$propsEntityType))
		{
			self::$propsEntityType = false;
			$propsMap = \Bitrix\Sale\Internals\OrderPropsValueTable::getMap();
			if(isset($propsMap['ENTITY_ID']) && isset($propsMap['ENTITY_TYPE']))
			{
				self::$propsEntityType = true;
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
	
	public static function getRelAllowImportFields()
	{
		$fl = \Bitrix\EsolAie\Import\FieldList::getInstance('salebasket');
		$arBasketFields = $fl->GetEntityFieldsDirect(false, false);
		$arGroups = array('BASKET'=>array(
			'PRODUCT_ID',
			'NAME',
			'QUANTITY',
			'PRICE',
			'CURRENCY',
			'VAT_INCLUDED',
			'VAT_RATE'
		));
		/*foreach($arBasketFields as $k=>$v)
		{
			if(strpos($k, '.')!==false) continue;
			$arGroups['BASKET'][$k] = array();
		}*/
		return $arGroups;
	}
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();

		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^IE_ORDERPROPERTY_[^\.]*$/', array_keys($arMap))));
		
		return $arMap;
	}
	
	public static function getImportExtraFields()
	{
		$arMap = array();
		$arMap['IE_SALE_USER_PROPS_ID'] = array(
			'data_type' => 'integer',
			'title' => Loc::getMessage('ESOL_AE_FL_ORDER_SALE_USER_PROPS_ID'),
		);
		$arMap['IE_SALE_USER_PROPS'] = array(
			'data_type' => '\Bitrix\Sale\Internals\EsolIEUserPropsTable',
			'reference' => array(
				'=this.IE_SALE_USER_PROPS_ID' => 'ref.ID',
			)
		);
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
		$arFields = array_diff($arFields, preg_grep('/^IE_ORDERPROPERTY_/', $arFields));
	}
	
	public static function initPropertyFields()
	{
		if(!isset(self::$propFields))
		{
			self::$propFields = array();
			self::$propFieldTitles = array();
			$dbRes = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('order'=>array('ID'=>'ASC')));
			while($arr = $dbRes->Fetch())
			{
				if($arr['TYPE']=='ENUM')
				{
					$className = 'EsolIEOrderPropertyID'.$arr['ID'].'Table';
					$fullClassName = '\Bitrix\Sale\Internals\\'.$className;
					$command = 'namespace Bitrix\Sale\Internals;'."\r\n".
						'class '.$className.' extends OrderPropsValueTable{'."\r\n".
							'public static function getMap(): array{'."\r\n".
								'$arMap = parent::getMap();'."\r\n".
								'$arMap["VALUE_ENUM"] = new \Bitrix\Main\Entity\ReferenceField('."\r\n".
									'"VALUE_ENUM",'."\r\n".
									'"\Bitrix\Sale\Internals\OrderPropsVariantTable",'."\r\n".
									'array('."\r\n".
										'"=ref.VALUE" => "this.VALUE",'."\r\n".
										'"=ref.ORDER_PROPS_ID" => "this.ORDER_PROPS_ID"'."\r\n".
									')'."\r\n".
								');'."\r\n".
								'return $arMap;'."\r\n".
								
							'}'."\r\n".
						'}';
					eval($command);						
						
					self::$propFields['IE_ORDERPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
						'IE_ORDERPROPERTY_'.$arr['ID'],
						$fullClassName,
						array(
							'=ref.ORDER_ID' => 'this.ID',
							'=ref.ORDER_PROPS_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
						)
					);
					self::$propFields['IE_ORDERPROPERTY_'.$arr['ID'].'.VALUE_ENUM.NAME'] = new \Bitrix\Main\Entity\StringField(
						'IE_ORDERPROPERTY_'.$arr['ID'].'.VALUE_ENUM.NAME', 
						array(
							'title' => Loc::getMessage("ESOL_AE_FL_ORDER_PROP").' '.$arr['NAME'].' ['.$arr['ID'].']'
						)
					);
				}
				else
				{
					self::$propFields['IE_ORDERPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
						'IE_ORDERPROPERTY_'.$arr['ID'],
						'Bitrix\Sale\Internals\OrderPropsValue',
						array(
							'=ref.ORDER_ID' => 'this.ID',
							'=ref.ORDER_PROPS_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
						)
					);
					self::$propFields['IE_ORDERPROPERTY_'.$arr['ID'].'.VALUE'] = new \Bitrix\Main\Entity\StringField(
						'IE_ORDERPROPERTY_'.$arr['ID'].'.VALUE', 
						array(
							'title' => Loc::getMessage("ESOL_AE_FL_ORDER_PROP").' '.$arr['NAME'].' ['.$arr['ID'].']'
						)
					);
					//self::$propFieldTitles['IE_ORDERPROPERTY_'.$arr['ID']] = Loc::getMessage("ESOL_AE_FL_ORDER_PROP").' '.$arr['NAME'];
				}
			}
		}
	}
	
	public static function GetExportFieldSettings($field)
	{
		$arData = array();
		if($field=='DATE_STATUS')
		{
			$arStatuses = array(array('VALUE'=>'', 'TITLE'=>Loc::getMessage("ESOL_AE_FL_ORDER_NOT_CHOOSE")));
			$dbRes = \Bitrix\Sale\Internals\StatusTable::getList(array('filter'=>array('STATUS_LANG.LID'=>LANGUAGE_ID), 'select'=>array('ID', 'TITLE'=>'STATUS_LANG.NAME')));
			while($arr = $dbRes->Fetch())
			{
				$arStatuses[] = array('VALUE'=>$arr['ID'], 'TITLE'=>'['.$arr['ID'].'] '.$arr['TITLE']);
			}
			/*$arData[] = array(
				'NOTE' => Loc::getMessage("ESOL_AE_FL_ORDER_STATUS_FROM_NOTE"),
				'TITLE' => Loc::getMessage("ESOL_AE_FL_ORDER_STATUS_FROM"),
				'NAME' => 'STATUS_FROM',
				'TYPE' => 'SELECT',
				'OPTIONS' => $arStatuses
			);*/
			$arData[] = array(
				'NOTE' => Loc::getMessage("ESOL_AE_FL_ORDER_STATUS_FROM_NOTE"),
				'TITLE' => Loc::getMessage("ESOL_AE_FL_ORDER_STATUS_TO"),
				'NAME' => 'STATUS_TO',
				'TYPE' => 'SELECT',
				'MULTIPLE' => 'Y',
				'OPTIONS' => $arStatuses
			);
		}
		elseif(preg_match('/^IE_ORDERPROPERTY_(\d+)\.VALUE$/', $field, $m))
		{
			if($arProp = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('filter'=>array('ID'=>$m[1])))->Fetch())
			{
				if($arProp['TYPE']=='LOCATION' && class_exists('\Bitrix\Sale\Location\LocationTable'))
				{
					$fl = new \Bitrix\EsolAie\Export\FieldList('locations');
					$arFields = $fl->GetEntityFields();
					$arData[] = array(
						'TITLE' => Loc::getMessage("ESOL_AE_FL_ORDER_LOCATION_REL_FIELD"),
						'NAME' => 'REL_FIELD',
						'TYPE' => 'SELECT',
						'OPTIONS' => array(
							array('VALUE'=>'', 'TITLE'=>$arFields['CODE']['title']),
							array('VALUE'=>'NAME', 'TITLE'=>$arFields['NAME']['title'])
						)
					);
				}
			}
		}
		return $arData;
	}
	
	public static function PrepareExportFilter(&$arFilter)
	{
		$arFilterKeys = array_keys($arFilter);
		if(count(preg_grep('/^\WIE_ORDERPROPERTY_\d+\.VALUE$/', $arFilterKeys)) > 0 && class_exists('\Bitrix\Sale\Location\LocationTable'))
		{
			$dbRes = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('filter'=>array('TYPE'=>'LOCATION'), 'select'=>array('ID')));
			while($arProp = $dbRes->Fetch())
			{
				$arKeys = preg_grep('/^\WIE_ORDERPROPERTY_'.$arProp['ID'].'\.VALUE$/', $arFilterKeys);
				if(count($arKeys) > 0)
				{
					foreach($arKeys as $key)
					{
						if(is_array($arFilter[$key]) || strlen($arFilter[$key])==0) continue;
						if($arLoc = \Bitrix\Sale\Location\LocationTable::getList(array('filter'=>array('=NAME.NAME'=>$arFilter[$key]), 'select'=>array('CODE')))->Fetch())
						{
							$arFilter[$key] = array(
								$arFilter[$key],
								$arLoc['CODE']
							);
						}
					}
				}
			}
		}
	}
	
    public static function PrepareExportFieldShare($field, $val, $arSettings, $arItem)
	{
		if(!is_array($val) && strlen($val) > 0 && is_array($arSettings) && $arSettings['REL_FIELD'])
		{
			if(preg_match('/^IE_ORDERPROPERTY_(\d+)\.VALUE$/', $field, $m))
			{
				$propId = $m[1];
				if(!isset(self::$orderProps[$propId]))
				{
					self::$orderProps[$propId] = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('filter'=>array('ID'=>$propId)))->Fetch();
					if(!is_array(self::$orderProps[$propId])) self::$orderProps[$propId] = array();
				}
				$arProp = self::$orderProps[$propId];
				if($arProp['TYPE']=='LOCATION' && class_exists('\Bitrix\Sale\Location\LocationTable'))
				{
					$arSelect = array('REL_FIELD' => $arSettings['REL_FIELD']);
					if($arSettings['REL_FIELD']=='NAME') $arSelect = array('REL_FIELD'=>'NAME.NAME');
					if($arLoc = \Bitrix\Sale\Location\LocationTable::getList(array('filter'=>array('=CODE'=>$val, '=NAME.LANGUAGE_ID'=>LANGUAGE_ID), 'select'=>$arSelect))->Fetch())
					{
						$val = $arLoc['REL_FIELD'];
					}
					else $val = '';
				}
			}
		}
		return $val;
    }
	
	public static function PrepareExportField_DATE_STATUS($field, $val, $arSettings, $arItem)
	{
		if($arItem['ID'] && is_array($arSettings) && /*$arSettings['STATUS_FROM'] && */$arSettings['STATUS_TO'])
		{
			$val = '';
			$dbRes = \Bitrix\Sale\Internals\OrderChangeTable::getList(array('filter'=>array('ORDER_ID'=>$arItem['ID'], 'TYPE'=>'ORDER_STATUS_CHANGED'), 'select'=>array('DATE_CREATE', 'DATA'), 'order'=>array('ID'=>'ASC')));
			$find = false;
			while(!$find && ($arr = $dbRes->Fetch()))
			{
				$arRecData = \Bitrix\EsolAie\Utils::Unserialize($arr['DATA']);
				if(is_array($arRecData) && in_array($arRecData['STATUS_ID'], $arSettings['STATUS_TO']))
				{
					$val = $arr['DATE_CREATE'];
					$find = true;
				}
			}
		}
		return $val;
	}
	
	public static function SaveRelatedEntities($ID, $arFieldsElement, $secondUpdate=false)
	{
		if(isset($arFieldsElement['IE_SALE_USER_PROPS_ID']) && (int)$arFieldsElement['IE_SALE_USER_PROPS_ID'] > 0)
		{
			$dbRes = \Bitrix\Sale\Internals\UserPropsValueTable::getList(array('filter'=>array('USER_PROPS_ID'=>(int)$arFieldsElement['IE_SALE_USER_PROPS_ID'])));
			while($arr = $dbRes->Fetch())
			{
				if(isset($arFieldsElement['IE_ORDERPROPERTY_'.$arr['ORDER_PROPS_ID'].'.VALUE'])) continue;
				$arFieldsElement['IE_ORDERPROPERTY_'.$arr['ORDER_PROPS_ID'].'.VALUE'] = $arr['VALUE'];
			}
		}
		
		$arPropKeys = array_map(array(__CLASS__, 'GetOrderPropertyId'), preg_grep('/^IE_ORDERPROPERTY_\d+\.(VALUE|VALUE_ENUM\.NAME)$/', array_keys($arFieldsElement)));
		if(!empty($arPropKeys))
		{
			$arProps = array();
			$dbRes = \Bitrix\Sale\Internals\OrderPropsTable::getList(array('order'=>array('ID'=>'ASC'), 'filter'=>array('ID'=>$arPropKeys)));
			while($arr = $dbRes->Fetch())
			{
				$arProps[$arr['ID']] = $arr;
			}
			
			$arOrderProps = array();
			$dbRes = \Bitrix\Sale\Internals\OrderPropsValueTable::getList(array('filter'=>array('ORDER_ID'=>$ID, 'ORDER_PROPS_ID'=>$arPropKeys)));
			while($arr = $dbRes->Fetch())
			{
				$arOrderProps[$arr['ORDER_PROPS_ID']] = $arr;
			}
			
			foreach($arProps as $propKey=>$arProp)
			{
				$propVal = trim($arFieldsElement['IE_ORDERPROPERTY_'.$propKey.'.VALUE']);
				if($arProp['TYPE']=='ENUM')
				{
					$propVal = trim($arFieldsElement['IE_ORDERPROPERTY_'.$propKey.'.VALUE_ENUM.NAME']);
					if($arEnumVal = \Bitrix\Sale\Internals\OrderPropsVariantTable::getList(array('filter'=>array('=ORDER_PROPS_ID'=>$arProp['ID'], '=NAME'=>$propVal)))->Fetch())
					{
						$propVal = $arEnumVal['VALUE'];
					}
					else continue;
				}
				if($arProp['MULTIPLE']=='Y' && $secondUpdate)
				{
					$oldVal = (is_array($arOrderProps[$propKey]['VALUE']) ? $arOrderProps[$propKey]['VALUE'] : array($arOrderProps[$propKey]['VALUE']));
					if(strlen($propVal) > 0) $propVal = array_merge($oldVal, array($propVal));
					else $propVal = $oldVal;
				}
				
				if(isset($arOrderProps[$propKey]))
				{
					if(strlen($propVal) > 0 || is_array($propVal)) \Bitrix\Sale\Internals\OrderPropsValueTable::update($arOrderProps[$propKey]['ID'], array('VALUE'=>$propVal));
					else \Bitrix\Sale\Internals\OrderPropsValueTable::delete($arOrderProps[$propKey]['ID']);
				}
				elseif(strlen($propVal) > 0)
				{
					$arPropFields = array(
						'ORDER_ID' => $ID, 
						'ORDER_PROPS_ID' => $propKey, 
						'NAME' => $arProp['NAME'], 
						'VALUE' => $propVal, 
						'CODE' => $arProp['CODE']
					);
					if(self::$propsEntityType)
					{
						$arPropFields['ENTITY_ID'] = $ID;
						$arPropFields['ENTITY_TYPE'] = 'ORDER';
					}
					\Bitrix\Sale\Internals\OrderPropsValueTable::add($arPropFields);
				}
			}
		}
	}
	
	public static function GetOrderPropertyId($n)
	{
		return substr($n, 17, -6);
	}
	
	public static function AfterUpdate($ID, $arFields)
	{
		$order = \Bitrix\Sale\Order::load($ID);
		if(array_key_exists('STATUS_ID', $arFields)) $order->setField('STATUS_ID', $arFields['STATUS_ID']);
		if(array_key_exists('ALLOW_DELIVERY', $arFields))
		{
			foreach ($order->getShipmentCollection() as $shipment)
			{
				if(!$shipment->isSystem())
				{
					$shipment->setField('ALLOW_DELIVERY', $arFields['ALLOW_DELIVERY']);
				}
			}
		}
		
		if(array_key_exists('PAYED', $arFields) && in_array($arFields['PAYED'], array('N', 'Y')))
		{
			foreach ($order->getPaymentCollection() as $paymentItem)
			{
				$paymentItem->setField('PAID', $arFields['PAYED']);
			}
		}
		
		$arBasket = array();
		foreach($arFields as $k=>$v)
		{
			if(strpos($k, 'BASKET.')!==false) $arBasket[substr($k, 7)] = $v;
		}
		if(count($arBasket) > 0 && array_key_exists('PRODUCT_ID', $arBasket) && $arBasket['PRODUCT_ID'] > 0)
		{
			/*$arBasket['ORDER_ID'] = $ID;
			$arBasket['LID'] = $order->getField('LID');
			if(!$arBasket['CURRENCY']) $arBasket['CURRENCY'] = $order->getField('CURRENCY');
			if(!$arBasket['FUSER_ID']) $arBasket['FUSER_ID'] = 0;
			if(\Bitrix\Main\Loader::includeModule('iblock') && ($arElement = \CIblockElement::GetList(array(), array('ID'=>$arBasket['PRODUCT_ID']), false, false, array('DETAIL_PAGE_URL'))->GetNext()))
			{
				if(!array_key_exists('DETAIL_PAGE_URL', $arBasket)) $arBasket['DETAIL_PAGE_URL'] = $arElement['DETAIL_PAGE_URL'];
			}
			$basketClass = '\Bitrix\Sale\Internals\EsolIESaleBasketTable';
			if($arBasketItem = $basketClass::getList(array('filter'=>array('ORDER_ID'=>$arBasket['ORDER_ID'], 'PRODUCT_ID'=>$arBasket['PRODUCT_ID']), 'select'=>array('ID')))->fetch())
			{
				$basketClass::update($arBasketItem['ID'], $arBasket);
			}
			else
			{
				$dbRes2 = $basketClass::add($arBasket);
			}*/
			
			$lid = $order->getField('LID');
			$productId = $arBasket['PRODUCT_ID'];
			$quantity = ((float)$arBasket['QUANTITY'] > 0 ? (float)$arBasket['QUANTITY'] : 1);
			$basket = $order->getBasket();
			
			$arBasketItemFields = array();
			if((float)$arBasket['QUANTITY'] > 0) $arBasketItemFields['QUANTITY'] = $arBasket['QUANTITY'];
			if(array_key_exists('NAME', $arBasket)) $arBasketItemFields['NAME'] = $arBasket['NAME'];
			if(array_key_exists('CURRENCY', $arBasket)) $arBasketItemFields['CURRENCY'] = $arBasket['CURRENCY'];
			if(array_key_exists('VAT_INCLUDED', $arBasket)) $arBasketItemFields['VAT_INCLUDED'] = $arBasket['VAT_INCLUDED'];
			if(array_key_exists('VAT_RATE', $arBasket)) $arBasketItemFields['VAT_RATE'] = $arBasket['VAT_RATE'];
			else $arBasketItemFields['CURRENCY'] = $order->getField('CURRENCY');
			if(array_key_exists('PRICE', $arBasket))
			{
				$arBasket['PRICE'] = (float)$arBasket['PRICE'];
				$arBasketItemFields['PRICE'] = $arBasket['PRICE'];
				$arBasketItemFields['CUSTOM_PRICE'] = 'Y';
			}
			if(\Bitrix\Main\Loader::includeModule('iblock') && ($arElement = \CIblockElement::GetList(array(), array('ID'=>$arBasket['PRODUCT_ID']), false, false, array('DETAIL_PAGE_URL'))->GetNext()))
			{
				$arBasketItemFields['DETAIL_PAGE_URL'] = $arElement['DETAIL_PAGE_URL'];
			}
			
			if(is_numeric($productId) && ($basketItem = $basket->getExistsItem('catalog', $productId)))
			{
				$arBasketItem = $arBasketItemFields;
				foreach($arBasketItem as $k=>$v)
				{
					$basketItem->setField($k, $v);
				}
				if($arBasketItem['CUSTOM_PRICE']=='Y') $basketItem->markFieldCustom('PRICE');
			}
			else
			{
				if(is_numeric($productId))
				{
					$basketItem = \Bitrix\Sale\BasketItem::create($basket, 'catalog', $productId);
					$arBasketItem = \Bitrix\Sale\TradingPlatform\Helper::getProductById($productId, $quantity, $lid);
				}
				else
				{
					$basketItem = \Bitrix\Sale\BasketItem::create($basket, 'catalog', $productId);
					$arBasketItem = array(
						'MODULE' => 'catalog',
						'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
						'PRODUCT_XML_ID' => $productId
					);
				}
				$arBasketItem['QUANTITY'] = $quantity;
				$arBasketItem['XML_ID'] = (isset($arBasket['XML_ID']) ? $arBasket['XML_ID'] : uniqid('bx_'));
				if(!array_key_exists('CURRENCY', $arBasketItem)) $arBasketItem['CURRENCY'] = $arBasketItemFields['CURRENCY'];
				
				$arNeedFields = array('QUANTITY', 'MODULE', 'PRODUCT_PROVIDER_CLASS', 'PRODUCT_XML_ID', 'CURRENCY', 'XML_ID');
				$arKeys = array_keys($arBasketItem);
				$countFields = 0;
				foreach($arKeys as $key)
				{
					if(!in_array($key, $arNeedFields))
					{
						unset($arBasketItem[$key]);
					}
					else
					{
						$countFields++;
					}
				}
				if($countFields >= count($arNeedFields))
				{
					$arBasketItem = array_merge($arBasketItem, $arBasketItemFields);
					$basketItem->initFields($arBasketItem);
					if($arBasketItem['CUSTOM_PRICE']=='Y') $basketItem->markFieldCustom('PRICE');
					$basket->addItem($basketItem);
				}
			}
			$order->refreshData();
		}
		
		$order->save();
		
		if($arFields['DATE_UPDATE'])
		{
			parent::Update($ID, array('DATE_UPDATE'=>$arFields['DATE_UPDATE']));
		}
	}
	
	public static function PrepareFieldsForAddImport(&$arFields)
	{
		if(!isset($arFields['DATE_UPDATE'])) $arFields['DATE_UPDATE'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['DATE_INSERT'])) $arFields['DATE_INSERT'] = ConvertTimeStamp(false, "FULL");
		if(!isset($arFields['DATE_STATUS'])) $arFields['DATE_STATUS'] = ConvertTimeStamp(false, "FULL");
	}
	
	public static function PrepareFieldsCustom(&$arFields)
	{
		$arFields = array_diff_key($arFields, array_flip(preg_grep('/^IE_ORDERPROPERTY_/', array_keys($arFields))));
		if(isset($arFields['PRICE'])) $arFields['PRICE'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['PRICE']);
		if(isset($arFields['PRICE_DELIVERY'])) $arFields['PRICE_DELIVERY'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['PRICE_DELIVERY']);
		if(isset($arFields['SUM_PAID'])) $arFields['SUM_PAID'] = \Bitrix\EsolAie\Entity\Utils::GetFloatVal($arFields['SUM_PAID']);
		if(isset($arFields['PAYED'])) $arFields['PAYED'] = \Bitrix\EsolAie\Import\Utils::GetBoolValue($arFields['PAYED'], false, 'N');
		if(isset($arFields['IE_SALE_USER_PROPS_ID'])) unset($arFields['IE_SALE_USER_PROPS_ID']);
		if($arFields['DATE_UPDATE'] && is_string($arFields['DATE_UPDATE']))
		{
			$time = strtotime($arFields['DATE_UPDATE']);
			if($time!==false)
			{
				$format = 'd.m.Y H:i:s';
				$arFields['DATE_UPDATE'] = new \Bitrix\Main\Type\DateTime(date($format, $time), $format);
			}
		}
	}
	
	public static function GetUpdatableFields($arFields)
	{
		if(isset($arFields['ALLOW_DELIVERY'])) unset($arFields['ALLOW_DELIVERY']);
		if(isset($arFields['STATUS_ID'])) unset($arFields['STATUS_ID']);
		return $arFields;
	}
	
	public static function CheckCntFilterFields($fieldName)
	{
		if(strpos($fieldName, 'IE_ORDERPROPERTY_')!==false) return false;
		return true;
	}
	
	public static function getEntityOptions()
	{
		return array(
			array(
				'CODE' => 'SEND_MESSAGE',
				'NAME' => Loc::getMessage('ESOL_AIE_ORDER_OPTION_SEND_MESSAGE'),
				'TYPE' => 'CHECKBOX'
			),
			array(
				'CODE' => 'CREATE_PAYMENT',
				'NAME' => Loc::getMessage('ESOL_AIE_ORDER_OPTION_CREATE_PAYMENT'),
				'TYPE' => 'CHECKBOX'
			),
			array(
				'CODE' => 'CREATE_SHIPMENT',
				'NAME' => Loc::getMessage('ESOL_AIE_ORDER_OPTION_CREATE_SHIPMENT'),
				'TYPE' => 'CHECKBOX'
			)
		);
	}
	
	public static function setProfileParams($params=array())
	{
		self::$profParams = $params;
	}
	
	public static function AfterSaveHandler(&$arParams, $ID=0, $bAdd=false)
	{		
		if(isset($arParams['element_last_created_id']) && $arParams['element_last_created_id']!=$ID)
		{
			$lastId = $arParams['element_last_created_id'];
			
			if(self::$profParams['CREATE_PAYMENT']=='Y' || self::$profParams['CREATE_SHIPMENT']=='Y')
			{
				$order = \Bitrix\Sale\Order::load($lastId);
				$paySystemId = $order->getField('PAY_SYSTEM_ID');
				if(self::$profParams['CREATE_PAYMENT']=='Y' && $paySystemId)
				{
					$paySystem = \Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId);
					$paymentCollection = $order->getPaymentCollection();
					$payment = $paymentCollection->createItem($paySystem);
					$payment->setField('SUM', $order->getField('PRICE'));
				}
				
				$deliveryId = $order->getField('DELIVERY_ID');
				if(self::$profParams['CREATE_SHIPMENT']=='Y' && $deliveryId)
				{
					$service = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId);
					$shipmentCollection = $order->getShipmentCollection();

					$dlvRes = \Bitrix\Sale\Helpers\Admin\Blocks\OrderShipment::updateData($order, array(
						1 => array
							(
								'SHIPMENT_ID' => '',
								'CUSTOM_PRICE_DELIVERY' => 'N',
								'CUSTOM_WEIGHT_DELIVERY' => 'N',
								'BASE_PRICE_DELIVERY' => '0',
								'CALCULATED_PRICE' => '0',
								'CALCULATED_WEIGHT' => '0',
								'DEDUCTED' => 'N',
								'ALLOW_DELIVERY' => 'N',
								'PRICE_DELIVERY' => '0',
								'WEIGHT' => '0',
								'TRACKING_NUMBER' => '',
								'DELIVERY_DOC_NUM' => '',
								'DELIVERY_DOC_DATE' => '',
								'DELIVERY_ID' => $deliveryId
							)
					));
				}
				$order->save();
			}
			
			if(self::$profParams['SEND_MESSAGE']=='Y')
			{
				if($arOrderNew = \CSaleOrder::GetByID($lastId))
				{
					$BASE_LANG_CURRENCY = \CSaleLang::GetLangCurrency(LANGUAGE_ID);
					$strOrderList = "";
					$dbBasketItems = \CSaleBasket::GetList(
						array("ID" => "ASC"),
						array("ORDER_ID" => $lastId),
						false,
						false,
						array("ID", "NAME", "QUANTITY", "PRICE")
					);
					$arBasketItems = array();
					while ($val = $dbBasketItems->Fetch())
					{
						$arBasketItems[] = $val;
					}
					$arBasketItems = getMeasures($arBasketItems);
					
					foreach ($arBasketItems as $val)
					{
						$measure = (isset($val["MEASURE_TEXT"])) ? $val["MEASURE_TEXT"] : GetMessage("ESOL_AIE_ORDER_PRODUCT_SHT");
						$strOrderList .= $val["NAME"]." - ".$val["QUANTITY"]." ".$measure." x ".SaleFormatCurrency($val["PRICE"], $BASE_LANG_CURRENCY);
						$strOrderList .= "<br>";
					}
					
					$FIO = "";
					$rsUser = \CUser::GetByID($arOrderNew['USER_ID']);
					if($arUser = $rsUser->Fetch())
					{
						if ($arUser["LAST_NAME"] != "")
							$FIO .= $arUser["LAST_NAME"]." ";
						if ($arUser["NAME"] != "")
							$FIO .= $arUser["NAME"];
					}

					$arUserEmail = array("PAYER_NAME" => $FIO, "USER_EMAIL" => $arUser["EMAIL"]);

					//send mail
					$arEmailFields = array(
						//"ORDER_ID" => $arOrderNew["ACCOUNT_NUMBER"],
						"ORDER_ID" => $lastId,
						"ORDER_DATE" => ConvertTimeStamp(false, 'PART'),
						"ORDER_USER" => $arUserEmail["PAYER_NAME"],
						"PRICE" => SaleFormatCurrency($arOrderNew["PRICE"], $BASE_LANG_CURRENCY),
						"BCC" => \Bitrix\Main\Config\Option::get("sale", "order_email", "order@".$_SERVER['HTTP_HOST']),
						"EMAIL" => $arUserEmail["USER_EMAIL"],
						"ORDER_LIST" => $strOrderList,
						"SALE_EMAIL" => \Bitrix\Main\Config\Option::get("sale", "order_email", "order@".$_SERVER['HTTP_HOST']),
						"DELIVERY_PRICE" => $arOrderNew["DELIVERY_PRICE"],
					);
					
					$eventName = "SALE_NEW_ORDER";

					$bSend = true;
					foreach(GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent)
						if (ExecuteModuleEventEx($arEvent, array($lastId, &$eventName, &$arEmailFields))===false)
							$bSend = false;

					if($bSend)
					{
						$event = new \CEvent;
						$event->Send($eventName, \CSite::GetDefSite(), $arEmailFields, "N");
					}
				}
			}
			unset($arParams['element_last_created_id']);
		}
		
		if($bAdd && $ID > 0) $arParams['element_last_created_id'] = $ID;
	}
	
	public static function Add(array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Add($arFields);
		if($result->isSuccess())
		{
			$ID = $result->getId();
			
			if(!$arFields['ACCOUNT_NUMBER'])
			{
				$order = \Bitrix\Sale\Order::load($ID);
				$accountNumber = \Bitrix\Sale\Internals\AccountNumberGenerator::generateForOrder($order);
				if($accountNumber===false) $accountNumber = $ID;
				$order->setField('ACCOUNT_NUMBER', $accountNumber);
				$order->save();
			}
			
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		self::PrepareFieldsCustom($arFields);
		$result = parent::Update($ID, self::GetUpdatableFields($arFields));
		if($result->isSuccess())
		{
			self::AfterUpdate($ID, $arFields);
		}
		return $result;
	}
}
