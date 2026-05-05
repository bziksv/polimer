<?php
namespace Bitrix\Crm;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIECompanyUtsTable extends Entity\DataManager
{
	protected static $ufMap = null;
	
	public static function getFilePath()
	{
		return __FILE__;
	}
	
	public static function getTableName()
	{
		return 'b_uts_crm_company';
	}
	
	public static function getMap(): array
	{
		$arMap = array(
			'VALUE_ID' => new Entity\IntegerField('VALUE_ID', array(
				'primary' => true,
				'title' => 'VALUE_ID'
			)),
		);
		$dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'CRM_Company', 'LANG' => LANGUAGE_ID));
		while($arr = $dbRes->Fetch())
		{
			if($arr['MULTIPLE']!='N') continue;
			$arMap[$arr['FIELD_NAME']] = new Entity\StringField($arr['FIELD_NAME'], array(
				'title' => $arr['EDIT_FORM_LABEL']
			));
			if(is_array($arr['SETTINGS']) && $arr['SETTINGS']['COMPANY']=='Y')
			{
				$arMap[$arr['FIELD_NAME'].'_REF'] = new \Bitrix\Main\Entity\ReferenceField(
					'IE_UF_'.$arr['ID'],
					'Bitrix\Crm\CompanyTable',
					array(
						'=ref.ID' => 'this.'.$arr['FIELD_NAME']
					)
				);
				$arMap[$arr['FIELD_NAME'].'_REF.TITLE'] = new \Bitrix\Main\Entity\StringField(
					'IE_UF_'.$arr['ID'].'.TITLE', 
					array(
						'title' => $arr['EDIT_FORM_LABEL'].' - '.Loc::getMessage("ESOL_AE_FL_COMPANY_NAME")
					)
				);
			}
		}
		return $arMap;
	}
}

class EsolIECompanyUtmTable extends Entity\DataManager
{
	protected static $ufMap = null;
	
	public static function getFilePath()
	{
		return __FILE__;
	}
	
	public static function getTableName()
	{
		return 'b_utm_crm_company';
	}
	
	public static function getMap(): array
	{		
		$arMap = array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true
			)),
			'VALUE_ID' => new Entity\IntegerField('VALUE_ID', array()),
			'FIELD_ID' => new Entity\IntegerField('FIELD_ID', array()),
			'VALUE' => new Entity\StringField('VALUE', array()),
			'VALUE_INT' => new Entity\IntegerField('VALUE_INT', array()),
			'VALUE_DOUBLE' => new Entity\FloatField('VALUE_DOUBLE', array()),
			'VALUE_DATE' => new Entity\DatetimeField('VALUE_DATE', array()),
		);
		return $arMap;
	}
}