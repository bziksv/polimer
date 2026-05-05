<?php
namespace Bitrix\Catalog;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEProductTable extends \Bitrix\Catalog\ProductTable
{
	static $propFields = null;
	static $propFieldTitles = null;
	static $priceFields = null;
	static $priceFieldTitles = null;
	
	public static function getMap(): array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap);
		
		$arMap['IE_BASKET'] = array(
			'data_type' => '\Bitrix\Sale\Internals\BasketTable',
			'reference' => array(
				'=this.ID' => 'ref.PRODUCT_ID',
			)
		);
		
		/*$arMap['IE_PRICE'] = array(
			'data_type' => '\Bitrix\Catalog\PriceTable',
			'reference' => array(
				'=this.ID' => 'ref.PRODUCT_ID',
			)
		);*/
		
		$arMap['IE_STORE_DOCS_ELEMENT'] = array(
			'data_type' => '\Bitrix\EsolAie\Entity\StoreDocsElementTable',
			'reference' => array(
				'=this.ID' => 'ref.ELEMENT_ID',
			)
		);

		$arMap['IE_BASKET_QNT'] = array(
			'title' => Loc::getMessage('ESOL_AIE_PRODUCT_FIELD_BASKET_QNT'),
			'data_type' => 'float',
			'expression' => array(
				'SUM(%s)', 'IE_BASKET.QUANTITY'
			)
		);
		
		$arMap['IE_BASKET_ORDER_QNT'] = array(
			'title' => Loc::getMessage('ESOL_AIE_PRODUCT_FIELD_BASKET_ORDER_QNT'),
			'data_type' => 'float',
			'expression' => array(
				'SUM(%s*IF(%s,1,0))', 'IE_BASKET.QUANTITY', 'IE_BASKET.ORDER.ID'
			)
		);
		
		$arMap['IE_STORE_DOCS_ELEMENT_QNT'] = array(
			'title' => Loc::getMessage('ESOL_AIE_PRODUCT_FIELD_STORE_DOCS_ELEMENT_QNT'),
			'data_type' => 'float',
			'expression' => array(
				'SUM(%s)', 'IE_STORE_DOCS_ELEMENT.AMOUNT'
			)
		);
		
		$arUtsMap = \Bitrix\Catalog\EsolIEProductUtsTable::getMap();
		if(array_key_exists('UF_PRODUCT_GROUP', $arUtsMap))
		{
			$arMap['IE_PRODUCT_UTS'] = array(
				'data_type' => '\Bitrix\Catalog\EsolIEProductUtsTable',
				'reference' => array(
					'=this.ID' => 'ref.VALUE_ID',
				)
			);
			$arMap['IE_PRODUCT_UTS.UF_PRODUCT_GROUP_TBL.UF_NAME'] = new \Bitrix\Main\Entity\StringField(
				'IE_PRODUCT_UTS.UF_PRODUCT_GROUP.UF_NAME', 
				array(
					'title' => $arUtsMap['UF_PRODUCT_GROUP']->getTitle()
				)
			);
		}
		
		self::initPropertyFields();
		if(is_array(self::$propFields))
		{
			foreach(self::$propFields as $k=>$v)
			{
				$arMap[$k] = $v;
			}
		}
		
		self::initPriceFields();
		if(is_array(self::$priceFields))
		{
			foreach(self::$priceFields as $k=>$v)
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
		unset(
			$arMap['TRIAL_IBLOCK_ELEMENT'],
			$arMap['TRIAL_PRODUCT']
		);
		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(IE_PRODUCTPROPERTY_|IE_PRICE_|IE_PRODUCT_UTS)[^\.]*$/', array_keys($arMap))));
		if($type=='import')
		{
			$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(IE_PRODUCTPROP|IE_PRODUCTPROPERTY_|IE_PRICE_|IE_PRODUCT_UTS)/', array_keys($arMap))));
		}
		
		if(isset($arMap['IE_BASKET']) && is_array($arMap['IE_BASKET']))
		{
			$arMap['IE_BASKET']['ALLOW_REFS'] = array('ORDER');
		}
		if(isset($arMap['IE_STORE_DOCS_ELEMENT']) && is_array($arMap['IE_STORE_DOCS_ELEMENT']))
		{
			$arMap['IE_STORE_DOCS_ELEMENT']['ALLOW_REFS'] = array('DOC_ID_DOC');
		}
		
		return $arMap;
	}
	
	public static function getRelTitles()
	{
		self::initPropertyFields();
		$arResult = self::$propFieldTitles;
		if(!is_array($arResult)) $arResult = array();
		
		self::initPriceFields();
		$arResult2 = self::$priceFieldTitles;
		if(!is_array($arResult2)) $arResult2 = array();
		
		return array_merge($arResult, $arResult2);
	}
	
	public static function prepareDefaultFields(&$arFields)
	{
		$arFields = array_diff($arFields, preg_grep('/^(IE_PRODUCTPROPERTY_|IE_PRICE_)/', $arFields));
	}
	
	public static function initPriceFields()
	{
		if(!isset(self::$priceFields))
		{
			self::$priceFields = array();
			self::$priceFieldTitles = array();
			
			$arGroups = array();
			$dbRes = \Bitrix\Catalog\GroupTable::getList(array('select'=>array('ID', 'NAME', 'BASE')));
			while($arr = $dbRes->Fetch())
			{
				if($arr['BASE']=='Y') array_unshift($arGroups, $arr);
				else array_push($arGroups, $arr);
			}
			
			foreach($arGroups as $arGroup)
			{
				self::$priceFields['IE_PRICE_'.$arGroup['ID']] = new \Bitrix\Main\Entity\ReferenceField(
					'IE_PRICE_'.$arGroup['ID'],
					'Bitrix\Catalog\PriceTable',
					array(
						'=ref.PRODUCT_ID' => 'this.ID',
						'=ref.CATALOG_GROUP_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arGroup['ID']),
					)
				);
				self::$priceFields['IE_PRICE_'.$arGroup['ID'].'.PRICE'] = new \Bitrix\Main\Entity\StringField(
					'IE_PRICE_'.$arGroup['ID'].'.PRICE', 
					array(
						'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PRICE").' '.$arGroup['NAME']
					)
				);
				self::$priceFields['IE_PRICE_'.$arGroup['ID'].'.CURRENCY'] = new \Bitrix\Main\Entity\StringField(
					'IE_PRICE_'.$arGroup['ID'].'.CURRENCY', 
					array(
						'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PRICE_CURRENCY").' '.$arGroup['NAME']
					)
				);
			}
		}
	}
	
	public static function initPropertyFields()
	{
		if(!isset(self::$propFields))
		{
			self::$propFields = array();
			self::$propFieldTitles = array();
			
			$arProductIblocks = array();
			$dbRes = \Bitrix\Catalog\CatalogIblockTable::getList(array('filter'=>array('>PRODUCT_IBLOCK_ID'=>'0', '>SKU_PROPERTY_ID'=>'0'), 'select'=>array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID')));
			while($arr = $dbRes->Fetch())
			{
				$arProductIblocks[$arr['PRODUCT_IBLOCK_ID']] = $arr;
			}

			$dbRes = \Bitrix\Iblock\PropertyTable::getList(array('order'=>array('IBLOCK_ID'=>'ASC', 'SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>array(/*'VERSION'=>1,*/ '!CATALOG_IBLOCK.IBLOCK_ID'=>false), 'select'=>array(/*'ID', 'IBLOCK_ID', 'NAME', 'CODE', 'VERSION', 'MULTIPLE', 'PROPERTY_TYPE', 'USER_TYPE', 'USER_TYPE_SETTINGS_LIST'*/ '*', 'IBLOCK_NAME'=>'IBLOCK.NAME'), 'runtime'=>array(
				new \Bitrix\Main\Entity\ReferenceField(
					'IBLOCK',
					'Bitrix\Iblock\IblockTable',
					array(
						'=this.IBLOCK_ID' => 'ref.ID'
					)
				),
				new \Bitrix\Main\Entity\ReferenceField(
					'CATALOG_IBLOCK',
					'Bitrix\Catalog\CatalogIblockTable',
					array(
						'=this.IBLOCK_ID' => 'ref.IBLOCK_ID'
					)
				),
			)));
			$arIblockProps = array();
			while($arr = $dbRes->Fetch())
			{
				if($arr['VERSION']==1)
				{
					/*self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
						'IE_PRODUCTPROPERTY_'.$arr['ID'],
						'Bitrix\Iblock\EsolIEElementPropertyTable',
						array(
							'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
							'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
						)
					);
					self::$propFieldTitles['IE_PRODUCTPROPERTY_'.$arr['ID']] = Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' - '.$arr['IBLOCK_NAME'].' ['.$arr['ID'].']';*/

					$addField = false;
					if($arr['PROPERTY_TYPE']=='L')
					{
						$className = 'EsolIEElementPropertyID'.$arr['ID'].'Table';
						$fullClassName = '\Bitrix\Iblock\\'.$className;
						$command = 'namespace Bitrix\Iblock;'."\r\n".
							'class '.$className.' extends EsolIEElementPropertyTable{'."\r\n".
								'public static function getMap(): array{return parent::getMapForList("'.$arr['ID'].'");}'.
							'}';
						eval($command);
						self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
							'IE_PRODUCTPROPERTY_'.$arr['ID'],
							$fullClassName,
							array(
								'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
								'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
							)
						);
						self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_ENUM.VALUE'] = new \Bitrix\Main\Entity\StringField(
							'IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_ENUM.VALUE', 
							array(
								'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']'
							)
						);
						$addField = true;
					}
					elseif($arr['PROPERTY_TYPE']=='E')
					{
						$className = 'EsolIEElementPropertyID'.$arr['ID'].'Table';
						$fullClassName = '\Bitrix\Iblock\\'.$className;
						$command = 'namespace Bitrix\Iblock;'."\r\n".
							'class '.$className.' extends EsolIEElementPropertyTable{'."\r\n".
								'public static function getMap(): array{return parent::getMapForIblockElement("'.$arr['ID'].'");}'.
							'}';
						eval($command);
						self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
							'IE_PRODUCTPROPERTY_'.$arr['ID'],
							$fullClassName,
							array(
								'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
								'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
							)
						);
						self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_ELEMENT.NAME'] = new \Bitrix\Main\Entity\StringField(
							'IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_ELEMENT.NAME', 
							array(
								'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']'
							)
						);
						$addField = true;
					}
					elseif($arr['PROPERTY_TYPE']=='S' && $arr['USER_TYPE']=='directory' && $arr['USER_TYPE_SETTINGS_LIST']['TABLE_NAME'] && Loader::includeModule('highloadblock'))
					{
						$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$arr['USER_TYPE_SETTINGS_LIST']['TABLE_NAME'])))->fetch();
						if($hlblock)
						{
							$dbRes2 = \CUserTypeEntity::GetList(array(), array('ENTITY_ID'=>'HLBLOCK_'.$hlblock['ID']));
							$arHLFields = array();
							while($arHLField = $dbRes2->Fetch()) $arHLFields[$arHLField['FIELD_NAME']] = $arHLField;
							if(isset($arHLFields['UF_XML_ID']) && isset($arHLFields['UF_NAME']))
							{
								$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
								$className = 'EsolIEElementPropertyID'.$arr['ID'].'Table';
								$fullClassName = '\Bitrix\Iblock\\'.$className;
								$command = 'namespace Bitrix\Iblock;'."\r\n".
									'class '.$className.' extends EsolIEElementPropertyTable{'."\r\n".
										'public static function getMap(): array{return parent::getMapForDirectory("'.$entity->getDataClass().'");}'.
									'}';
								eval($command);
								self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
									'IE_PRODUCTPROPERTY_'.$arr['ID'],
									$fullClassName,
									array(
										'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
										'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
									)
								);
								self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_DIR.UF_NAME'] = new \Bitrix\Main\Entity\StringField(
									'IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE_DIR.UF_NAME', 
									array(
										'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']'
									)
								);
								$addField = true;
							}
						}
					}
					
					if(!$addField)
					{
						if(false /*array_key_exists($arr['IBLOCK_ID'], $arProductIblocks) && $arProductIblocks[$arr['IBLOCK_ID']]['SKU_PROPERTY_ID'] > 0*/)
						{
							$productTable = self::getTableName();
							if(strpos($productTable, 'b_')===0) $productTable = substr($productTable, 2);
							$skuPropId = $arProductIblocks[$arr['IBLOCK_ID']]['SKU_PROPERTY_ID'];
							
							self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID'].'_SKU'] = new \Bitrix\Main\Entity\ReferenceField(
								'IE_PRODUCTPROPERTY_'.$arr['ID'].'_SKU',
								'Bitrix\Iblock\EsolIEElementPropertyTable',
								array(
									'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
									'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $skuPropId)
								)
							);
							self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
								'IE_PRODUCTPROPERTY_'.$arr['ID'],
								'Bitrix\Iblock\EsolIEElementPropertyTable',
								array(
									'=this.IBLOCK_ELEMENT.ID' => new \Bitrix\Main\DB\SqlExpression('?#.?#', $productTable, 'ID'),
									'=ref.IBLOCK_ELEMENT_ID' => new \Bitrix\Main\DB\SqlExpression('IF(?#.?#=?i, ?#.?#, ?#.?#)', $productTable.'_iblock_element', 'IBLOCK_ID', $arr['IBLOCK_ID'], $productTable, 'ID', $productTable.'_prop2', 'VALUE'),
									'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
								)
							);
						}
						else
						{
							self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\ReferenceField(
								'IE_PRODUCTPROPERTY_'.$arr['ID'],
								'Bitrix\Iblock\EsolIEElementPropertyTable',
								array(
									'=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
									'=ref.IBLOCK_PROPERTY_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $arr['ID'])
								)
							);
						}
						self::$propFields['IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE'] = new \Bitrix\Main\Entity\StringField(
							'IE_PRODUCTPROPERTY_'.$arr['ID'].'.VALUE', 
							array(
								'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']'
							)
						);
					}
				}
				elseif($arr['VERSION']==2)
				{
					if($arr['MULTIPLE']!='Y')
					{
						$arIblockProps[$arr['IBLOCK_ID']]['IBLOCK_NAME'] = $arr['IBLOCK_NAME'].' ['.$arr['ID'].']';
						$arIblockProps[$arr['IBLOCK_ID']]['PROP_IDS'] = $arr['ID'];
					}
					
						self::$propFields['IE_PRODUCTPROPTABLE_'.$arr['IBLOCK_ID'].'.PROPERTY_'.$arr['ID']] = new \Bitrix\Main\Entity\StringField(
							'IE_PRODUCTPROPTABLE_'.$arr['IBLOCK_ID'].'.PROPERTY_'.$arr['ID'],
							array(
								'title' => Loc::getMessage("ESOL_AIE_FL_PRODUCT_PROPERTY").' '.$arr['NAME'].' ['.$arr['CODE'].'] - '.$arr['IBLOCK_NAME'].' ['.$arr['IBLOCK_ID'].']'
							)
						);
				}
			}
			
			foreach($arIblockProps as $iblockId=>$arIblockPropsItem)
			{
				$className = 'EsolIEElementPropertyV2Ib'.$iblockId.'Table';
				$fullClassName = '\Bitrix\EsolAie\Entity\\'.$className;
				$command = 'namespace Bitrix\EsolAie\Entity;'."\r\n".
					'class '.$className.' extends EsolIEElementPropertyV2Table{'."\r\n".
						'public static function getTableName(){return parent::getTableName()."'.$iblockId.'";}'.
						'public static function getMap(): array{return parent::getMapByIblockId("'.$iblockId.'");}'.
					'}';
				eval($command);
				self::$propFields['IE_PRODUCTPROPTABLE_'.$iblockId] = new \Bitrix\Main\Entity\ReferenceField(
					'IE_PRODUCTPROPTABLE_'.$iblockId,
					$fullClassName,
					array(
						'=ref.IBLOCK_ELEMENT_ID' => 'this.ID'
					)
				);
				self::$propFieldTitles['IE_PRODUCTPROPTABLE_'.$iblockId] = Loc::getMessage("ESOL_AIE_FL_IBLOCK_PROPERTIES").' '.$arIblockPropsItem['IBLOCK_NAME'];
			}
		}
	}
	
	public static function GetExportFieldSettings($field)
	{
		$arData = array();
		if($field=='IE_BASKET_ORDER_QNT')
		{
			$arData[] = array(
				'TITLE' => Loc::getMessage("ESOL_AE_FL_ORDER_DATE_INSERT_DAYS"),
				'NAME' => 'DATE_INSERT_DAYS',
				'TYPE' => 'STRING',
				'MULTIPLE' => 'N',
			);
		}
		return $arData;
	}
	
	public static function PrepareExportField_IE_BASKET_ORDER_QNT($field, $val, $arSettings, $arItem)
	{
		if($val > 0 && $arItem['ID'] && is_array($arSettings) && $arSettings['DATE_INSERT_DAYS'])
		{
			$val = '';
			if($arr = self::getList(array('filter'=>array('ID'=>$arItem['ID'], '>=IE_BASKET.ORDER.DATE_INSERT'=>ConvertTimeStamp(time()-(float)$arSettings['DATE_INSERT_DAYS']*24*60*60, "FULL")), 'select'=>array('IE_BASKET_ORDER_QNT')))->Fetch())
			{
				$val = $arr['IE_BASKET_ORDER_QNT'];
			}
		}
		return $val;
	}
}
