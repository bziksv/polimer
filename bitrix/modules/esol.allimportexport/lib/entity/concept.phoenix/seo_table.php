<?php
namespace Bitrix\EsolAie\Entity\ConceptPhoenix;

use Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);

class SeoTable extends Entity\DataManager
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
		return 'phoenix_seo';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return array(
			'id' => new Entity\IntegerField('id', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_SEO_ID')
			)),
			'site_id' => new Entity\StringField('site_id', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_SEO_SITE_ID')
			)),
			'url' => new Entity\StringField('url', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_SEO_URL')
			)),
			'noindex' => new Entity\IntegerField('noindex', array(
				'default_value' => '0',
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_NOINDEX')
			)),
			'title' => new Entity\StringField('title', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_TITLE')
			)),
			'keywords' => new Entity\StringField('keywords', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_KEYWORDS')
			)),
			'description' => new Entity\StringField('description', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_DESCRIPTION')
			)),
			'h1' => new Entity\StringField('h1', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_H1')
			)),
			'og_title' => new Entity\StringField('og_title', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_OG_TITLE')
			)),
			'og_description' => new Entity\StringField('og_description', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_OG_DESCRIPTION')
			)),
			'og_image' => new Entity\StringField('og_image', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_OG_IMAGE')
			)),
			'og_url' => new Entity\StringField('og_url', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_OG_URL')
			)),
			'og_type' => new Entity\StringField('og_type', array(
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_OG_TYPE')
			)),
			'meta_tags' => new Entity\TextField('meta_tags', array(
				'default_value' => '',
				'title' => Loc::getMessage('ESOL_AIE_CONCEPT_PHOENIX_META_TAGS')
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