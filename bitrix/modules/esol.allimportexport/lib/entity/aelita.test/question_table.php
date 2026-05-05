<?php
namespace Bitrix\EsolAie\Entity\AelitaTest;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class QuestionTable extends Entity\DataManager
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
		return 'b_aelita_test_question';
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
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_ID')
			)),
			'XML_ID' => new Entity\StringField('XML_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_XML_ID')
			)),
			'TEST_ID' => new Entity\IntegerField('TEST_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_TEST_ID')
			)),
			'TEST_GROUP_ID' => new Entity\IntegerField('TEST_GROUP_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_TEST_GROUP_ID')
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_NAME')
			)),
			'ACTIVE' => new Entity\EnumField('ACTIVE', array(
				'values' => array('Y', 'N'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_ACTIVE')
			)),
			'PICTURE' => new Entity\IntegerField('PICTURE', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_PICTURE')
			)),
			'ALT' => new Entity\StringField('ALT', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_ALT')
			)),
			'DESCRIPTION' => new Entity\TextField('DESCRIPTION', array(
				'default_value' => '',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_DESCRIPTION')
			)),
			'DESCRIPTION_TYPE' => new Entity\EnumField('DESCRIPTION_TYPE', array(
				'values' => array('text', 'html'),
				'default_value' => 'text',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_DESCRIPTION_TYPE')
			)),
			'SORT' => new Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_SORT')
			)),
			'TEST_TYPE' => new Entity\EnumField('TEST_TYPE', array(
				'values' => array('radio', 'check', 'input'),
				'default_value' => 'radio',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_TEST_TYPE')
			)),
			'CORRECT_ANSWER' => new Entity\StringField('CORRECT_ANSWER', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_CORRECT_ANSWER')
			)),
			'SCORES' => new Entity\IntegerField('SCORES', array(
				'default_value' => 0,
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_SCORES')
			)),
			'SHOW_COMMENTS' => new Entity\EnumField('SHOW_COMMENTS', array(
				'values' => array('Y', 'N'),
				'default_value' => 'N',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_QUESTION_SHOW_COMMENTS')
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
}