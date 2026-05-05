<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEElementPropertyV2Table extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_element_prop_s';
	}
	
	public static function getMap(): array
	{
		return array();
	}

	public static function getMapByIblockId($iblockId)
	{
		$arMap = array(
			'IBLOCK_ELEMENT_ID' => new Entity\IntegerField('IBLOCK_ELEMENT_ID', array(
				'primary' => true,
				'title' => Loc::getMessage('ESOL_AIE_P2_IBLOCK_ELEMENT_ID')
			))
		);
		$dbRes = \Bitrix\Iblock\PropertyTable::getList(array('order'=>array('IBLOCK_ID'=>'ASC', 'SORT'=>'ASC', 'ID'=>'ASC'), 'filter'=>array('IBLOCK_ID'=>$iblockId, 'VERSION'=>2, 'MULTIPLE'=>'N')/*, 'select'=>array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'VERSION', 'MULTIPLE')*/));
		while($arr = $dbRes->Fetch())
		{
			$addField = false;
			if($arr['PROPERTY_TYPE']=='L')
			{
				$arMap['PROPERTY_'.$arr['ID'].'_HIDDEN_FIELD'] = new Entity\StringField('PROPERTY_'.$arr['ID'], array(
					'title' => $arr['NAME'].' ['.$arr['CODE'].'] - ID'
				));
				$arMap['PROPERTY_'.$arr['ID'].'_VALUE'] = new \Bitrix\Main\Entity\ReferenceField(
					'PROPERTY_'.$arr['ID'].'_VALUE',
					'Bitrix\Iblock\PropertyEnumerationTable',
					array(
						'=ref.ID' => 'this.PROPERTY_'.$arr['ID'].'_HIDDEN_FIELD'
					)
				);
				$arMap['PROPERTY_'.$arr['ID'].'_VALUE.VALUE'] = new Entity\StringField('PROPERTY_'.$arr['ID'].'_VALUE.VALUE', array(
					'title' => $arr['NAME'].' ['.$arr['CODE'].']'
				));
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
						$arMap['PROPERTY_'.$arr['ID'].'_HIDDEN_FIELD'] = new Entity\StringField('PROPERTY_'.$arr['ID'], array(
							'title' => $arr['NAME'].' ['.$arr['CODE'].'] - XML_ID'
						));
						$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
						$arMap['PROPERTY_'.$arr['ID'].'_VALUE'] = new \Bitrix\Main\Entity\ReferenceField(
							'PROPERTY_'.$arr['ID'].'_VALUE',
							$entity->getDataClass(),
							array(
								'=ref.UF_XML_ID' => 'this.PROPERTY_'.$arr['ID'].'_HIDDEN_FIELD'
							)
						);
						$arMap['PROPERTY_'.$arr['ID'].'_VALUE.UF_NAME'] = new Entity\StringField('PROPERTY_'.$arr['ID'].'_VALUE.UF_NAME', array(
							'title' => $arr['NAME'].' ['.$arr['CODE'].']'
						));
						$addField = true;
					}
				}
			}
			if(!$addField)
			{
				$arMap['PROPERTY_'.$arr['ID']] = new Entity\StringField('PROPERTY_'.$arr['ID'], array(
					'title' => $arr['NAME'].' ['.$arr['CODE'].']'
				));
			}
		}
		return $arMap;
	}
}
