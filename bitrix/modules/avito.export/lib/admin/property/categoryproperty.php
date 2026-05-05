<?php
namespace Avito\Export\Admin\Property;

use Avito\Export\Concerns;

class CategoryProperty implements FeedPropertyExtension
{
	use Concerns\HasLocale;

	public const USER_TYPE = 'avito_category';

	public static function getUserTypeDescription() : array
	{
		return [
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE' => static::USER_TYPE,
			'DESCRIPTION' => self::getLocale('DESCRIPTION', null, 'Avito: Category'),
			'GetPropertyFieldHtml' => [static::class, 'getPropertyFieldHtml'],
			'GetPropertyFieldHtmlMulty' => [static::class, 'getPropertyFieldHtmlMulty'],
			'avitoExportFeedFields' => [static::class, 'avitoExportFeedFields'],
			'avitoExportFeedValue' => [static::class, 'avitoExportFeedValue'],
		];
	}

	public static function avitoExportFeedFields($property) : array
	{
		return CategoryProvider::exportFields();
	}

	public static function avitoExportFeedValue($property, $value, $field)
	{
		return CategoryProvider::exportValue($value, $field);
	}

	public static function prepareSettings($property) : array
	{
		return [
			'ALLOW_NO_VALUE' => ($property['IS_REQUIRED'] !== 'Y'),
		];
	}

	public static function getPropertyFieldHtml($property, $value, $controlName) : string
	{
		return self::editComponent(static::editDefaults($property, $controlName) + [
			'MULTIPLE' => 'N',
			'CONTROL_NAME' => $controlName['VALUE'],
			'VALUE' => $value['VALUE'],
			'PARENT_VALUE' => static::needParentValue($controlName)
				? self::parentValue($property, $controlName)
				: null
		]);
	}

	public static function getPropertyFieldHtmlMulty($property, $values, $controlName) : string
	{
		return self::editComponent(static::editDefaults($property, $controlName) + [
			'MULTIPLE' => 'Y',
			'CONTROL_NAME' => $controlName['VALUE'] . '[]',
			'VALUE' => is_array($values) ? array_column($values, 'VALUE') : [],
			'PARENT_VALUE' => static::needParentValue($controlName)
				? self::parentValue($property, $controlName)
				: null
		]);
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

	protected static function needParentValue(array $controlName) : bool
	{
		return $controlName['MODE'] === 'FORM_FILL';
	}

	protected static function parentValue(array $property, array $controlName)
	{
		$elementId = Utils\PropertyElement::findElementId($controlName);

		if ($elementId === null) { return null; }

		return ValueInherit\Category::parentValue(
			$elementId,
			$property['IBLOCK_ID'],
			(string)$property['ID']
		);
	}
}