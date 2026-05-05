<?php
namespace Bitrix\EsolAie\Entity;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class StoreDocsElementTable extends Entity\DataManager
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
		return 'b_catalog_docs_element';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ESOL_AIE_SDE_ID')
			)),
			'DOC_ID' => new Entity\IntegerField('DOC_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_DOC_ID')
			)),
			'STORE_FROM' => new Entity\IntegerField('STORE_FROM', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_STORE_FROM')
			)),
			'STORE_TO' => new Entity\IntegerField('STORE_TO', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_STORE_TO')
			)),
			'ELEMENT_ID' => new Entity\IntegerField('ELEMENT_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_ELEMENT_ID')
			)),
			'AMOUNT' => new Entity\FloatField('AMOUNT', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_AMOUNT')
			)),
			'PURCHASING_PRICE' => new Entity\FloatField('PURCHASING_PRICE', array(
				'title' => Loc::getMessage('ESOL_AIE_SDE_PURCHASING_PRICE')
			)),
			'DOC_ID_DOC' => new Entity\ReferenceField(
				'DOC_ID_DOC',
				'\Bitrix\EsolAie\Entity\StoreDocsTable',
				array('=this.DOC_ID' => 'ref.ID')
			),
			'STORE_FROM_STORE' => new Entity\ReferenceField(
				'STORE_FROM_STORE',
				'\Bitrix\Catalog\StoreTable',
				array('=this.STORE_FROM' => 'ref.ID')
			),
			'STORE_TO_STORE' => new Entity\ReferenceField(
				'STORE_TO_STORE',
				'\Bitrix\Catalog\StoreTable',
				array('=this.STORE_TO' => 'ref.ID')
			),
			'ELEMENT_ID_ELEMENT' => new Entity\ReferenceField(
				'ELEMENT_ID_ELEMENT',
				'\Bitrix\Iblock\ElementTable',
				array('=this.ELEMENT_ID' => 'ref.ID')
			),
			'ELEMENT_ID_PRODUCT' => new Entity\ReferenceField(
				'ELEMENT_ID_PRODUCT',
				'\Bitrix\Catalog\EsolIEProductTable',
				array('=this.ELEMENT_ID' => 'ref.ID')
			),
		);
	}
	
	public static function getRelTitles()
	{
		return array(
			'DOC_ID_DOC' => Loc::getMessage('ESOL_AIE_SDE_DOC_REL'),
			'STORE_FROM_STORE' => Loc::getMessage('ESOL_AIE_SDE_STORE_FROM_REL'),
			'STORE_TO_STORE' =>  Loc::getMessage('ESOL_AIE_SDE_STORE_TO_REL'),
			'ELEMENT_ID_ELEMENT' => Loc::getMessage('ESOL_AIE_SDE_ELEMENT_ID_REL')
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
}