<?php
namespace Bitrix\Crm;

use Bitrix\EsolAie\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEDealTable extends \Bitrix\Crm\DealTable
{	
	public static function getMap(): array
	{
		$arMap = parent::getMap();
		\Bitrix\EsolAie\Entity\Utils::PrepareMap($arMap, '\Bitrix\Crm');
		
		if(!isset($arMap['IE_UTS_FIELDS']))
		{
			$arMap = array_merge(array('IE_UTS_FIELDS' => new \Bitrix\Main\Entity\ReferenceField(
				'IE_UTS_FIELDS',
				'\Bitrix\Crm\EsolIEDealUtsTable',
				array(
					'=ref.VALUE_ID' => 'this.ID',
				)
			)), $arMap);
		}
		
		foreach($arMap as $k=>$v)
		{
			if($v instanceOf \Bitrix\Main\ORM\Fields\Relations\Reference)
			{
				if($v->getDataType()=='\Bitrix\Crm\Contact' && $v->getName()=='CONTACT_BY')
				{
					unset($arMap[$k]);
				}
				elseif($v->getDataType()=='\Bitrix\Crm\Company' && $v->getName()=='COMPANY_BY')
				{
					unset($arMap[$k]);
				}
				elseif($v->getDataType()=='\Bitrix\Crm\Contact' && $v->getName()=='CONTACT')
				{
					unset($arMap[$k]);
					$arMap[] = new \Bitrix\Main\Entity\ReferenceField(
						'CONTACT',
						'\Bitrix\Crm\EsolIEContactTable',
						array(
							'=ref.ID' => 'this.CONTACT_ID',
						)
					);
				}
				elseif($v->getDataType()=='\Bitrix\Crm\Company' && $v->getName()=='COMPANY')
				{
					unset($arMap[$k]);
					$arMap[] = new \Bitrix\Main\Entity\ReferenceField(
						'COMPANY',
						'\Bitrix\Crm\EsolIECompanyTable',
						array(
							'=ref.ID' => 'this.COMPANY_ID',
						)
					);
				}
				elseif($v->getDataType()=='\Bitrix\Crm\Lead' && $v->getName()=='LEAD_BY')
				{
					unset($arMap[$k]);
					$arMap[] = new \Bitrix\Main\Entity\ReferenceField(
						'LEAD_BY',
						'\Bitrix\Crm\EsolIELeadTable',
						array(
							'=ref.ID' => 'this.LEAD_ID',
						)
					);
				}
				elseif($v->getDataType()=='\Bitrix\Crm\ProductRow' && $v->getName()=='PRODUCT_ROW')
				{
					unset($arMap[$k]);
					/*$arMap['PRODUCT_ROW'] = new \Bitrix\Main\Entity\ReferenceField(
						'PRODUCT_ROW',
						'\Bitrix\Crm\EsolIEProductRowTable',
						\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.OWNER_ID')->where('ref.OWNER_TYPE', \CCrmOwnerTypeAbbr::Deal)
					);*/
					$arMap['PRODUCT_ROW'] = array(
						'data_type' => '\Bitrix\Crm\EsolIEProductRowTable',
						'reference' => array(
							'=this.ID' => 'ref.OWNER_ID',
							'=ref.OWNER_TYPE' => new \Bitrix\Main\DB\SqlExpression('?s', \CCrmOwnerTypeAbbr::Deal)
						),
					);
				}
			}
		}
		
		return $arMap;
	}
	
	public static function getIEFields($type='export')
	{
		$arMap = self::getMap();
		
		if($type=='export')
		{
			if(isset($arMap['PRODUCT_ROW']) && is_array($arMap['PRODUCT_ROW']))
			{
				$arMap['PRODUCT_ROW']['ALLOW_REFS'] = array('IE_PRODUCT');
			}
		}
		
		return $arMap;
	}
	
	public static function getImportExtraFields()
	{
		$arMap = \Bitrix\Crm\EsolIEDealUtsTable::getMap();
		unset($arMap['VALUE_ID']);
		
		return $arMap;
	}
	
	public static function Add(array $arFields)
	{
		$deal = new \CCrmDeal(false);
		$ID = $deal->Add($arFields);
		$result = new \Bitrix\Main\Entity\AddResult();
		if($ID > 0)
		{
			$result->setId($ID);
			
			$arErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Deal,
				$ID,
				\CCrmBizProcEventType::Create,
				$arErrors
			);
		}
		else $result->addError(new \Bitrix\Main\Error($deal->LAST_ERROR));
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		$deal = new \CCrmDeal(false);
		if($res = $deal->Update($ID, $arFields))
		{
			$arErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				\CCrmOwnerType::Deal,
				$ID,
				\CCrmBizProcEventType::Edit,
				$arErrors
			);
		}
		$result = new \Bitrix\Main\Entity\UpdateResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($deal->LAST_ERROR));
		return $result;
	}
	
	public static function Delete($ID)
	{
		$deal = new \CCrmDeal(false);
		$res = $deal->Delete($ID);
		$result = new \Bitrix\Main\Entity\DeleteResult();
		if(!$res) $result->addError(new \Bitrix\Main\Error($deal->LAST_ERROR));
		return $result;
	}
}
