<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEPropertyTable extends \Bitrix\Iblock\PropertyTable
{
	static $iblockVersions = array();
	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		
		if(isset($arMap['USER_TYPE_SETTINGS_LIST']) && isset($arMap['USER_TYPE_SETTINGS']))
		{
			unset($arMap['USER_TYPE_SETTINGS']);
		}
		
		if(class_exists('\Bitrix\Iblock\SectionPropertyTable'))
		{
			$arMap['SECTION_PROP'] = new \Bitrix\Main\Entity\ReferenceField(
				'SECTION_PROP',
				'\Bitrix\Iblock\SectionPropertyTable',
				array(
					'=ref.PROPERTY_ID' => 'this.ID',
					'=ref.SECTION_ID' => new \Bitrix\Main\DB\SqlExpression('?i', 0),
				)
			);
			$arMap['IE_SECTION_PROPERTY'] = array(
				'title' => Loc::getMessage('ESOL_AIE_PROPERTY_SECTION_PROPERTY'),
				'data_type' => 'string',
				'expression' => array(
					'IF(%s>0 or %s="N","Y","N")','SECTION_PROP.PROPERTY_ID','IBLOCK.SECTION_PROPERTY'
				)
			);
			$arMap['SECTION_PROP.SMART_FILTER'] = new \Bitrix\Main\Entity\StringField(
				'SECTION_PROP.SMART_FILTER', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_PROPERTY_SMART_FILTER')
				)
			);
			$arMap['SECTION_PROP.DISPLAY_TYPE'] = new \Bitrix\Main\Entity\StringField(
				'SECTION_PROP.DISPLAY_TYPE', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_PROPERTY_DISPLAY_TYPE')
				)
			);
			$arMap['SECTION_PROP.DISPLAY_EXPANDED'] = new \Bitrix\Main\Entity\StringField(
				'SECTION_PROP.DISPLAY_EXPANDED', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_PROPERTY_DISPLAY_EXPANDED')
				)
			);
		}
		
		$arFeatures = array();
		if(is_callable(array('\Bitrix\Iblock\Model\PropertyFeature', 'isEnabledFeatures')) && \Bitrix\Iblock\Model\PropertyFeature::isEnabledFeatures())
		{
			$arFeatures = self::getPropertyFeatureList();
			foreach($arFeatures as $arFeature)
			{
				$fieldName = 'IE_FEATURE_'.str_replace('.', '_', $arFeature['MODULE_ID']).'_'.$arFeature['FEATURE_ID'];
				$arMap[$fieldName] = new \Bitrix\Main\Entity\ReferenceField(
					$fieldName,
					'Bitrix\Iblock\PropertyFeatureTable',
					array(
						'=ref.PROPERTY_ID' => 'this.ID',
						'=ref.MODULE_ID' => new \Bitrix\Main\DB\SqlExpression('?s', $arFeature['MODULE_ID']),
						'=ref.FEATURE_ID' => new \Bitrix\Main\DB\SqlExpression('?s', $arFeature['FEATURE_ID'])
					)
				);
				$arMap[$fieldName.'.IS_ENABLED'] = new \Bitrix\Main\Entity\StringField(
					$fieldName.'_'.$arFeature['FEATURE_ID'].'.IS_ENABLED', 
					array(
						'title' => $arFeature['FEATURE_NAME']
					)
				);
			}
		}
		
		$arMap['IE_ELEMENT_CNT'] = array(
			'title' => Loc::getMessage('ESOL_AIE_PROPERTY_IE_ELEMENT_CNT'),
			'data_type' => 'string',
			'expression' => array(
				'CONCAT(%s, "_", %s)', 'ID', 'IBLOCK_ID'
			)
		);
		
		/*$arMap['ENUM_PROP'] = array(
			'data_type' => '\Bitrix\Iblock\EsolIEPropertyEnumerationTable',
			'reference' => array(
				'=this.ID' => 'ref.PROPERTY_ID',
			)
		);*/
		
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
		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(IE_FEATURE_|SECTION_PROP)[^\.]*$/', array_keys($arMap))));
		//$arMap = array_diff_key($arMap, array_flip(preg_grep('/^(IE_FEATURE_)[^\.]*$/', array_keys($arMap))));
		if($type=='import')
		{
			unset($arMap['VERSION']);
			unset($arMap['IE_SECTION_PROPERTY']['expression']);
		}
		return $arMap;
	}
	
	public static function PrepareExportField_IE_ELEMENT_CNT($field, $val, $arSettings, $arItem)
	{
		while(is_array($val))
		{
			$val = array_shift($val);
		}
		if(strlen($val) > 0)
		{
			list($ID, $IBLOCK_ID) = explode('_', $val);
			$val = '';
			if($ID && $IBLOCK_ID)
			{
				$val = \CIblockElement::GetList(array(), array('!PROPERTY_'.$ID=>false, 'IBLOCK_ID'=>$IBLOCK_ID), array());
			}
		}
		return $val;
	}
	
	public static function getPropertyFeatureList()
	{
		$arFeatures = array();
		if(is_callable(array('\Bitrix\Iblock\Model\PropertyFeature', 'isEnabledFeatures')) && \Bitrix\Iblock\Model\PropertyFeature::isEnabledFeatures())
		{
			$arFeatures = \Bitrix\Iblock\Model\PropertyFeature::getPropertyFeatureList(array());
			$arFeaturesKeys = array();
			foreach($arFeatures as $arFeature)
			{
				$arFeaturesKeys[] = $arFeature['MODULE_ID'].'_'.$arFeature['FEATURE_ID'];
			}
			if(\Bitrix\Main\Loader::includeModule('catalog') && class_exists('\Bitrix\Catalog\Product\PropertyCatalogFeature'))
			{
				if(defined('\Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_BASKET_PROPERTY') && !in_array('catalog_'. \Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_BASKET_PROPERTY, $arFeaturesKeys))
				{
					$arFeatures[] = [
						'MODULE_ID' => 'catalog',
						'FEATURE_ID' => \Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_BASKET_PROPERTY,
						'FEATURE_NAME' => Loc::getMessage('PROPERTY_CATALOG_FEATURE_NAME_BASKET_PROPERTY')
					];
				}
				if(defined('\Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_OFFER_TREE_PROPERTY') && !in_array('catalog_'. \Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_OFFER_TREE_PROPERTY, $arFeaturesKeys))
				{
					$arFeatures[] = [
						'MODULE_ID' => 'catalog',
						'FEATURE_ID' => \Bitrix\Catalog\Product\PropertyCatalogFeature::FEATURE_ID_OFFER_TREE_PROPERTY,
						'FEATURE_NAME' => Loc::getMessage('PROPERTY_CATALOG_FEATURE_NAME_SKU_TREE_PROPERTY')
					];
				}
			}
		}
		return $arFeatures;
	}
	
	public static function GetFeatures(&$arFields)
	{
		$arFeatures = self::getPropertyFeatureList();
		
		$arFeaturesFields = array();
		foreach($arFeatures as $arFeature)
		{
			$featureKey = $arFeature['MODULE_ID'].':'.$arFeature['FEATURE_ID'];
			$featureKey2 = 'IE_FEATURE_'.$arFeature['MODULE_ID'].'_'.$arFeature['FEATURE_ID'];
			if(isset($arFields[$featureKey2.'.IS_ENABLED']) && strlen($arFields[$featureKey2.'.IS_ENABLED']) > 0)
			{
				$arFeaturesFields[$featureKey] = array(
					'MODULE_ID' => $arFeature['MODULE_ID'],	
					'FEATURE_ID' => $arFeature['FEATURE_ID'],	
					'IS_ENABLED' => $arFields[$featureKey2.'.IS_ENABLED'] == 'Y' ? 'Y' : 'N'
				);
			}
		}
		$arFields = array_diff_key($arFields, array_flip(preg_grep('/^IE_FEATURE_/', array_keys($arFields))));
		return $arFeaturesFields;
	}
	
	public static function SaveFeatures($arFeatures, $propId)
	{
		if(empty($arFeatures) || !is_callable(array('\Bitrix\Iblock\Model\PropertyFeature', 'isEnabledFeatures')) || !\Bitrix\Iblock\Model\PropertyFeature::isEnabledFeatures()) return;
		foreach($arFeatures as $k=>$v)
		{
			$arFeatures[$k]['PROPERTY_ID'] = $propId;
		}
		\Bitrix\Iblock\Model\PropertyFeature::setFeatures($propId, $arFeatures);
	}
	
	public static function GetSmartFilter(&$arFields)
	{
		$arSmartFilter = array();
		foreach($arFields as $k=>$v)
		{
			if(strpos($k, 'SECTION_PROP.')===0)
			{
				$fieldName = substr($k, 13);
				$fieldVal = trim($v);
				if(in_array($fieldName, array('SMART_FILTER', 'DISPLAY_EXPANDED'))) $fieldVal = (ToUpper($fieldVal)=='Y' ? 'Y' : 'N');
				$arSmartFilter[$fieldName] = $fieldVal;
				unset($arFields[$k]);
			}
		}
		return $arSmartFilter;
	}
	
	public static function SaveSmartFilter($arSmartFilter, $ID)
	{
		if(!empty($arSmartFilter) > 0 && class_exists('\Bitrix\Iblock\SectionPropertyTable'))
		{
			$updateIndex = false;
			$cnt = 0;
			$arProp = self::getList(array('filter'=>array('ID'=>$ID), 'select'=>array('IBLOCK_ID')))->Fetch();
			$dbRes = \Bitrix\Iblock\SectionPropertyTable::getList(array('filter'=>array('PROPERTY_ID'=>$ID), 'select'=>array('IBLOCK_ID', 'SECTION_ID', 'PROPERTY_ID', 'SMART_FILTER')));
			while($arr = $dbRes->Fetch())
			{
				\Bitrix\Iblock\SectionPropertyTable::update(array('IBLOCK_ID'=>$arr['IBLOCK_ID'], 'SECTION_ID'=>$arr['SECTION_ID'], 'PROPERTY_ID'=>$arr['PROPERTY_ID']), $arSmartFilter);
				if(isset($arSmartFilter['SMART_FILTER']) && $arSmartFilter['SMART_FILTER']=='Y' && $arr['SMART_FILTER']!='Y') $updateIndex = true;
				$cnt++;
			}
			
			if($cnt==0)
			{
				\Bitrix\Iblock\SectionPropertyTable::add(array_merge(array('IBLOCK_ID'=>$arProp['IBLOCK_ID'], 'SECTION_ID'=>0, 'PROPERTY_ID'=>$ID, 'SMART_FILTER'=>'N', 'DISPLAY_TYPE'=>'F', 'DISPLAY_EXPANDED'=>'N'), $arSmartFilter));
				if(isset($arSmartFilter['SMART_FILTER']) && $arSmartFilter['SMART_FILTER']=='Y') $updateIndex = true;
			}
			
			if($updateIndex) \Bitrix\Iblock\PropertyIndex\Manager::markAsInvalid($arProp['IBLOCK_ID']);
		}
	}
	
	public static function GetSectionProperty(&$arFields)
	{
		$sectionProperty = '';
		if(array_key_exists('IE_SECTION_PROPERTY', $arFields))
		{
			if(in_array(ToUpper(trim($arFields['IE_SECTION_PROPERTY'])), array('Y', 'N')))
			{
				$sectionProperty = ToUpper(trim($arFields['IE_SECTION_PROPERTY']));
			}
			unset($arFields['IE_SECTION_PROPERTY']);
		}
		return $sectionProperty;
	}
	
	public static function SaveSectionProperty($sectionProperty, $ID)
	{
		$arProp = self::getList(array('filter'=>array('ID'=>$ID), 'select'=>array('IBLOCK_ID', 'IBLOCK_SECTION_PROPERTY'=>'IBLOCK.SECTION_PROPERTY')))->Fetch();
		if($arProp['IBLOCK_SECTION_PROPERTY'] != "Y")
		{
			$ib = new \CIBlock;
			$ib->Update($IBLOCK_ID, array('SECTION_PROPERTY'=>'Y'));
		}
		$arSectionProperty = \Bitrix\Iblock\SectionPropertyTable::getList(array('filter'=>array('PROPERTY_ID'=>$ID, 'SECTION_ID'=>0), 'select'=>array('PROPERTY_ID')))->Fetch();
		if($sectionProperty=='N' && $arSectionProperty)
		{
			\Bitrix\Iblock\SectionPropertyTable::delete(array('IBLOCK_ID'=>$arProp['IBLOCK_ID'], 'SECTION_ID'=>0, 'PROPERTY_ID'=>$ID));
		}
		elseif($sectionProperty=='Y' && !$arSectionProperty)
		{
			\Bitrix\Iblock\SectionPropertyTable::add(array('IBLOCK_ID'=>$arProp['IBLOCK_ID'], 'SECTION_ID'=>0, 'PROPERTY_ID'=>$ID, 'SMART_FILTER'=>'N', 'DISPLAY_TYPE'=>'F', 'DISPLAY_EXPANDED'=>'N'));
		}
	}
	
	public static function CheckCntFilterFields($fieldName)
	{
		if(strpos($fieldName, 'IE_FEATURE_')!==false
			|| strpos($fieldName, 'SECTION_PROP')!==false) return false;
		return true;
	}
	
	public static function GetIblockVersion($IBLOCK_ID)
	{
		if(!$IBLOCK_ID) return 1;
		if(!array_key_exists($IBLOCK_ID, self::$iblockVersions))
		{
			self::$iblockVersions[$IBLOCK_ID] = 1;
			if($arIblock = \Bitrix\Iblock\IblockTable::getList(array('filter'=>array('ID'=>$IBLOCK_ID), 'select'=>array('VERSION')))->Fetch())
			{
				self::$iblockVersions[$IBLOCK_ID] = (int)$arIblock['VERSION'];
			}
		}
		return self::$iblockVersions[$IBLOCK_ID];
	}
	
	public static function Add(array $arFields)
	{
		if($arFields['IBLOCK_ID'] && !$arFields['VERSION']) $arFields['VERSION'] = self::GetIblockVersion($arFields['IBLOCK_ID']);
		$arFeatures = self::GetFeatures($arFields);
		$sectionProperty = self::GetSectionProperty($arFields);
		$arSmartFilter = self::GetSmartFilter($arFields);
		if($arFields['VERSION'] > 1)
		{
			$ibp = new \CIBlockProperty;
			$propId = $ibp->Add($arFields);
			$result = new \Bitrix\Main\Entity\AddResult();
			if($propId > 0)
			{
				self::Update($propId, $arFields);
				$result->setId($propId);
			}
			else $result->addError(new \Bitrix\Main\Error($ibp->LAST_ERROR));
		}
		else
		{
			$result = parent::Add($arFields);
		}
		if($result->isSuccess())
		{
			$ID = $result->getId();
			if(!empty($arFeatures)) self::SaveFeatures($arFeatures, $ID);
			if(!empty($arSmartFilter) > 0) self::SaveSmartFilter($arSmartFilter, $ID);
			if($sectionProperty) self::SaveSectionProperty($sectionProperty, $ID);
		}
		
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$arFeatures = self::GetFeatures($arFields);
		$sectionProperty = self::GetSectionProperty($arFields);
		$arSmartFilter = self::GetSmartFilter($arFields);
		if(empty($arFields)) $arFields['ID'] = $ID;
		$result = parent::Update($ID, $arFields);
		if($result->isSuccess())
		{
			if(!empty($arFeatures)) self::SaveFeatures($arFeatures, $ID);
			if(!empty($arSmartFilter) > 0) self::SaveSmartFilter($arSmartFilter, $ID);
			if($sectionProperty) self::SaveSectionProperty($sectionProperty, $ID);
		}
		
		return $result;
	}
	
	public static function Delete($ID)
	{		
		$result = parent::Delete($ID, $arFields);
		
		return $result;
	}
}
