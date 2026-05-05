<?php
namespace Bitrix\Crm;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIELeadTable extends \Bitrix\Crm\LeadTable
{	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Crm');
		
		if(!isset($arMap['IE_UTS_FIELDS']))
		{
			$arMap = array_merge(array('IE_UTS_FIELDS' => new \Bitrix\Main\Entity\ReferenceField(
				'IE_UTS_FIELDS',
				'\Bitrix\Crm\EsolIELeadUtsTable',
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
					'=ref.ENTITY_ID' => new \Bitrix\Main\DB\SqlExpression('?s', 'LEAD'),
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
		$arMap = \Bitrix\Crm\EsolIELeadUtsTable::getMap();
		unset($arMap['VALUE_ID']);
		
		return $arMap;
	}
	
	public static function Add(array $arFields)
	{
		$lead = new \CCrmLead(false);
		$ID = $lead->Add($arFields);
		$result = new \Bitrix\Main\Entity\AddResult();
		if($ID > 0)
		{
			$result->setId($ID);
			
			$arErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Lead,
				$ID,
				\CCrmBizProcEventType::Create,
				$arErrors
			);
		}
		else $result->addError(new \Bitrix\Main\Error($lead->LAST_ERROR));
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$lead = new \CCrmLead(false);
		if($res = $lead->Update($ID, $arFields))
		{
			$arErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Lead,
				$ID,
				\CCrmBizProcEventType::Edit,
				$arErrors
			);
		}
		$result = new \Bitrix\Main\Entity\UpdateResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($lead->LAST_ERROR));
		return $result;
	}
	
	public static function Delete($ID)
	{
		$lead = new \CCrmLead(false);
		$res = $lead->Delete($ID);
		$result = new \Bitrix\Main\Entity\DeleteResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($lead->LAST_ERROR));
		return $result;
	}
}
