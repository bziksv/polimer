<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class EsolIEElementPropertyTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_element_property';
	}
	
	public static function getMap(): array
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'IBLOCK_PROPERTY_ID' => new Entity\IntegerField('IBLOCK_PROPERTY_ID', array(

			)),
			'IBLOCK_ELEMENT_ID' => new Entity\IntegerField('IBLOCK_ELEMENT_ID', array(

			)),
			'VALUE' => new Entity\TextField('VALUE', array(
				'default_value' => ''
			)),
			'VALUE_TYPE' => new Entity\StringField('VALUE_TYPE', array(
				'default_value' => 'text'
			)),
			'VALUE_ENUM' => new Entity\IntegerField('VALUE_ENUM', array(

			)),
			'VALUE_NUM' => new Entity\FloatField('VALUE_NUM', array(

			)),
			'DESCRIPTION' => new Entity\StringField('DESCRIPTION', array(

			)),
		);
	}
	
	public static function getIEFieldsRel()
	{
		$arMap = self::getMap();
		return array(
			'VALUE' => $arMap['VALUE'],
			'DESCRIPTION' => $arMap['DESCRIPTION'],
		);
	}
	
	public static function getMapForList($propId)
	{
		$arMap = self::getMap();
		$arMap['VALUE_ENUM'] = new \Bitrix\Main\Entity\ReferenceField(
			'VALUE_ENUM',
			'Bitrix\Iblock\PropertyEnumerationTable',
			array(
				'=ref.ID' => 'this.VALUE'
			)
		);
		return $arMap;
	}
	
	public static function getMapForDirectory($tableName)
	{
		$arMap = self::getMap();
		$arMap['VALUE_DIR'] = new \Bitrix\Main\Entity\ReferenceField(
			'VALUE_DIR',
			$tableName,
			array(
				'=ref.UF_XML_ID' => 'this.VALUE'
			)
		);
		return $arMap;
	}
	
	public static function getMapForIblockElement($propId)
	{
		$arMap = self::getMap();
		$arMap['VALUE_ELEMENT'] = new \Bitrix\Main\Entity\ReferenceField(
			'VALUE_ELEMENT',
			'\Bitrix\Iblock\ElementTable',
			array(
				'=ref.ID' => 'this.VALUE'
			)
		);
		return $arMap;
	}
}
