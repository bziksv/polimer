<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Main\Entity;
Loc::loadMessages(__FILE__);

class EsolIEPropertyEnumerationTable extends \Bitrix\Iblock\PropertyEnumerationTable
{
	public static function getMap(): array
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_ID_FIELD'),
			),
			'PROPERTY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_PROPERTY_ID_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_VALUE_FIELD'),
			),
			'DEF' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_DEF_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_SORT_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_XML_ID_FIELD'),
			),
			'TMP_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENUM_ENTITY_TMP_ID_FIELD'),
			),
			'PROPERTY' => array(
				'data_type' => 'Bitrix\Iblock\EsolIEPropertyTable',
				'reference' => array('=this.PROPERTY_ID' => 'ref.ID'),
			),
			'SECTION_PROP' => array(
				'data_type' => 'Bitrix\Iblock\SectionPropertyTable',
				'reference' => array('=this.PROPERTY_ID' => 'ref.PROPERTY_ID'),
			),
		);
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
		
		/*if(isset($arMap['PROPERTY']) && is_array($arMap['PROPERTY']))
		{
			$arMap['PROPERTY']['ALLOW_REFS'] = array('SECTION_PROP');
		}*/
		
		return $arMap;
	}
	
	public static function validateXmlId()
	{
		return array(
			new Entity\Validator\Length(null, 200),
		);
	}
}
