<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class StoreDocsTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_store_docs';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		Loader::includeModule('catalog');
		$arDocsTypes = array("A", "M", "R", "D", "U");
		if(class_exists('\CCatalogDocs') && isset(\CCatalogDocs::$types)) $arDocsTypes = array_keys(\CCatalogDocs::$types);
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ESOL_AIE_SD_ID')
			)),
			'DOC_TYPE' => new Entity\EnumField('DOC_TYPE', array(
				'required' => true,
				'values' => $arDocsTypes,
				'title' => Loc::getMessage('ESOL_AIE_SD_DOC_TYPE')
			)),
			'SITE_ID' => new Entity\StringField('SITE_ID', array(
				'required' => true,
				'size' => 2,
				'title' => Loc::getMessage('ESOL_AIE_SD_SITE_ID')
			)),
			'CONTRACTOR_ID' => new Entity\IntegerField('CONTRACTOR_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_CONTRACTOR_ID')
			)),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_DATE_MODIFY')
			)),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_DATE_CREATE')
			)),
			'CREATED_BY' => new Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_CREATED_BY')
			)),
			'MODIFIED_BY' => new Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_MODIFIED_BY')
			)),
			'CURRENCY' => new Entity\StringField('CURRENCY', array(
				'size' => 3,
				'title' => Loc::getMessage('ESOL_AIE_SD_CURRENCY')
			)),
			'STATUS' => new Entity\EnumField('STATUS', array(
				'required' => true,
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ESOL_AIE_SD_STATUS')
			)),
			'DATE_STATUS' => new Entity\DatetimeField('DATE_STATUS', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_DATE_STATUS')
			)),
			'DATE_DOCUMENT' => new Entity\DatetimeField('DATE_DOCUMENT', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_DATE_DOCUMENT')
			)),
			'STATUS_BY' => new Entity\IntegerField('STATUS_BY', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_STATUS_BY')
			)),
			'TOTAL' => new Entity\FloatField('TOTAL', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_TOTAL')
			)),
			'COMMENTARY' => new Entity\TextField('COMMENTARY', array(
				'title' => Loc::getMessage('ESOL_AIE_SD_COMMENTARY')
			))
		);
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	
	public static function UpdateStatus($ID, $arFields)
	{
		if(!$ID || !isset($arFields['STATUS'])) return false;
		if(!($arFields['STATUS'] = \Bitrix\EsolAie\Import\Utils::GetBoolValue($arFields['STATUS']))) return false;
		if(!($arDoc = self::getList(array('filter'=>array('ID'=>$ID), 'select'=>array('STATUS')))->Fetch())) return false;
		if($arDoc['STATUS']!=$arFields['STATUS'])
		{
			$userId = (isset($arFields['STATUS_BY']) && $arFields['STATUS_BY'] > 0 ? $arFields['STATUS_BY'] : $GLOBALS['USER']->GetId());
			if($arFields['STATUS']=='Y') $result = \CCatalogDocs::conductDocument($ID, $userId);
			else $result = \CCatalogDocs::cancellationDocument($ID, $userId);
			return true;
		}
		return false;
	}
	
	public static function Add(array $arFields)
	{
		$result = parent::add(array_merge($arFields, array('STATUS'=>'N')));
		if($result->isSuccess() && ($ID = $result->getId()))
		{
			if(self::UpdateStatus($ID, $arFields))
			{
				self::Update($ID, array('STATUS'=>$arFields['STATUS']));
			}
		}
		return $result;
	}
	
	public static function Update($ID, array $arFields)
	{
		self::UpdateStatus($ID, $arFields);
		return parent::update($ID, $arFields);
	}
}