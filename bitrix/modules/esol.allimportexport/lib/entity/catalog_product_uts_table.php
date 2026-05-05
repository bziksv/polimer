<?php
namespace Bitrix\Catalog;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEProductUtsTable extends Entity\DataManager
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
		return 'b_uts_product';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		$arMap = array(
			'VALUE_ID' => new Entity\IntegerField('VALUE_ID', array(
				'primary' => true,
				'autocomplete' => true
			))
		);
		
		$dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'PRODUCT', 'LANG' => LANGUAGE_ID));
		while($arr = $dbRes->Fetch())
		{
			$arMap[$arr['FIELD_NAME']] = new Entity\StringField($arr['FIELD_NAME'], array(
				'title' => $arr['EDIT_FORM_LABEL']
			));
		}
		
		if(array_key_exists('UF_PRODUCT_GROUP', $arMap) && Loader::includeModule('highloadblock') && ($hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('NAME'=>'ProductMarkingCodeGroup')))->fetch()))
		{
			$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
			$entityDataClass = $entity->getDataClass();
			$arMap['UF_PRODUCT_GROUP_TBL'] = new \Bitrix\Main\Entity\ReferenceField(
				'UF_PRODUCT_GROUP_TBL',
				$entityDataClass,
				array(
					'=ref.ID' => 'this.UF_PRODUCT_GROUP',
				)
			);			
		}
		
		return $arMap;
	}
}