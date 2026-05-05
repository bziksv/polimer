<?php
namespace Bitrix\Crm;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEContactTable extends \Bitrix\Crm\ContactTable
{	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Crm');
		
		if(!isset($arMap['IE_UTS_FIELDS']))
		{
			$arMap = array_merge(array('IE_UTS_FIELDS' => new \Bitrix\Main\Entity\ReferenceField(
				'IE_UTS_FIELDS',
				'\Bitrix\Crm\EsolIEContactUtsTable',
				array(
					'=ref.VALUE_ID' => 'this.ID',
				)
			)), $arMap);
		}
		
		foreach(array('PHONE_WORK', 'PHONE_OTHER', 'EMAIL_WORK', 'EMAIL_OTHER') as $v)
		{
			$arMap['IE_'.$v] = new \Bitrix\Main\Entity\ReferenceField(
				'IE_'.$v,
				'\Bitrix\Crm\FieldMultiTable',
				array(
					'=ref.ELEMENT_ID' => 'this.ID',
					'=ref.ENTITY_ID' => new \Bitrix\Main\DB\SqlExpression('?s', 'CONTACT'),
					'=ref.COMPLEX_ID' => new \Bitrix\Main\DB\SqlExpression('?s', $v),
				)
			);
			$arMap['IE_'.$v.'.VALUE'] = new \Bitrix\Main\Entity\StringField(
				'IE_'.$v.'.VALUE', 
				array(
					'title' => Loc::getMessage('ESOL_AIE_FL_'.$v)
				)
			);
		}

		return $arMap;
	}
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();
		$arMap = array_diff_key($arMap, array_flip(preg_grep('/^IE_(PHONE_WORK|PHONE_OTHER|EMAIL_WORK|EMAIL_OTHER)[^\.]*$/', array_keys($arMap))));

		return $arMap;
	}
	
	public static function getImportExtraFields()
	{
		$arMap = \Bitrix\Crm\EsolIEContactUtsTable::getMap();
		unset($arMap['VALUE_ID']);
		
		$dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'CRM_CONTACT', 'LANG' => LANGUAGE_ID, 'MULTIPLE'=>'Y'));
		while($arr = $dbRes->Fetch())
		{
			$arMap[$arr['FIELD_NAME']] = new \Bitrix\Main\Entity\StringField($arr['FIELD_NAME'], array(
				'title' => $arr['EDIT_FORM_LABEL'].' ['.$arr['FIELD_NAME'].']'
			));
		}
		
		return $arMap;
	}
}
