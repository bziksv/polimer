<?php
namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;

class CategoryField implements FeedFieldExtension
{
	use Concerns\HasLocale;

	public const USER_TYPE_ID = 'avito_category';

	public static function getUserTypeDescription() : array
	{
		return [
			'BASE_TYPE' => 'string',
			'USER_TYPE_ID' => static::USER_TYPE_ID,
			'DESCRIPTION' => self::getLocale('DESCRIPTION', null, 'Avito: Category'),
			'CLASS_NAME' => static::class,
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function getDbColumnType($userField) : string
	{
		return 'text';
	}

	public static function avitoExportFeedFields($userField) : array
	{
		return CategoryProvider::exportFields();
	}

	public static function avitoExportFeedValue($userField, $value, $field)
	{
		return CategoryProvider::exportValue($value, $field);
	}

	public static function prepareSettings($property = []) : array
	{
		return [
			'ALLOW_NO_VALUE' => ($property['MANDATORY'] !== 'Y'),
		];
	}

	/** @noinspection PhpUnused */
	public static function getEditFormHTML($userField, $htmlControl) : string
	{
		try
		{
			return self::editComponent(static::editDefaults($userField, $htmlControl['NAME']) + [
				'MULTIPLE' => 'N',
				'CONTROL_NAME' => $htmlControl['NAME'],
				'VALUE' => $htmlControl['VALUE'],
				'PARENT_VALUE' => self::parentValue($userField)
			]);
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}
	}

	/** @noinspection PhpUnused */
	public static function getEditFormHtmlMulty($userField, $htmlControl) : string
	{
		try
		{
			return self::editComponent(static::editDefaults($userField, $htmlControl['NAME']) + [
				'MULTIPLE' => 'Y',
				'CONTROL_NAME' => $htmlControl['NAME'],
				'VALUE' => is_array($htmlControl['VALUE']) ? $htmlControl['VALUE'] : [],
				'PARENT_VALUE' => self::parentValue($userField)
			]);
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}
	}

	protected static function editDefaults($property, $controlName) : array
	{
		return [
			'PROPERTY' => $property,
			'CONTROL' => $controlName,
		] + static::prepareSettings($property);
	}

	protected static function editComponent(array $parameters) : string
	{
		global $APPLICATION;

		return (string)$APPLICATION->IncludeComponent(
			'avito.export:admin.property.category',
			'.default',
			$parameters,
			false,
			[
				'HIDE_ICONS' => 'Y',
			]
		);
	}

	protected static function parentValue($userField) : ?string
	{
		$iblockId = Utils\SectionField::parseIblockId($userField);
		$sectionId = Utils\SectionField::parseUrlSectionId();

		if ($sectionId === null) { return null; }

		return Utils\SectionField::parentValue($userField['FIELD_NAME'], $iblockId, $sectionId);
	}
}