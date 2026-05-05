<?php
namespace Bitrix\EsolAie\Entity\AelitaTest;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class AnswerTable extends Entity\DataManager
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
		return 'b_aelita_test_answer';
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
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_ID')
			)),
			'XML_ID' => new Entity\StringField('XML_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_XML_ID')
			)),
			'QUESTION_ID' => new Entity\IntegerField('QUESTION_ID', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_QUESTION_ID')
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_NAME')
			)),
			'ACTIVE' => new Entity\EnumField('ACTIVE', array(
				'values' => array('Y', 'N'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_ACTIVE')
			)),
			'CORRECT' => new Entity\StringField('CORRECT', array(
				'values' => array('Y', 'N'),
				'default_value' => 'N',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_CORRECT')
			)),
			'PICTURE' => new Entity\IntegerField('PICTURE', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_PICTURE')
			)),
			'ALT' => new Entity\StringField('ALT', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_ALT')
			)),
			'DESCRIPTION' => new Entity\TextField('DESCRIPTION', array(
				'default_value' => '',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_DESCRIPTION')
			)),
			'DESCRIPTION_TYPE' => new Entity\EnumField('DESCRIPTION_TYPE', array(
				'values' => array('text', 'html'),
				'default_value' => 'text',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_DESCRIPTION_TYPE')
			)),
			'SORT' => new Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_SORT')
			)),
			'SCORES' => new Entity\IntegerField('SCORES', array(
				'default_value' => 0,
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_SCORES')
			)),
			'CORRECT_DESCRIPTION' => new Entity\TextField('CORRECT_DESCRIPTION', array(
				'default_value' => '',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_CORRECT_DESCRIPTION')
			)),
			'CORRECT_DESCRIPTION_TYPE' => new Entity\EnumField('CORRECT_DESCRIPTION_TYPE', array(
				'values' => array('text', 'html'),
				'default_value' => 'text',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_CORRECT_DESCRIPTION_TYPE')
			)),
			'ERROR_DESCRIPTION' => new Entity\TextField('ERROR_DESCRIPTION', array(
				'default_value' => '',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_ERROR_DESCRIPTION')
			)),
			'ERROR_DESCRIPTION_TYPE' => new Entity\EnumField('ERROR_DESCRIPTION_TYPE', array(
				'values' => array('text', 'html'),
				'default_value' => 'text',
				'title' => Loc::getMessage('ESOL_AIE_AELITA_TEST_ANSWER_ERROR_DESCRIPTION_TYPE')
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