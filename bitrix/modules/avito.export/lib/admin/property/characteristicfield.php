<?php
namespace Avito\Export\Admin\Property;

use Bitrix\Main;
use Avito\Export\Concerns;

class CharacteristicField implements FeedFieldExtension
{
	use Concerns\HasLocale;

	public const USER_TYPE_ID = 'avito_characteristic';

	public static function getUserTypeDescription() : array
	{
		return [
			'BASE_TYPE' => 'string',
			'USER_TYPE_ID' => static::USER_TYPE_ID,
			'DESCRIPTION' => self::getLocale('DESCRIPTION', null, 'Avito: Characteristic'),
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
		return CharacteristicProvider::exportFields(self::USER_TYPE_ID);
	}

	public static function avitoExportFeedValue($userField, $value, $field)
	{
		$value['VALUE'] = self::parseFieldValue($value['VALUE']);
		return CharacteristicProvider::exportValue($userField, $value, $field);
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function prepareSettings($property) : array
	{
		return [];
	}

	/** @noinspection PhpUnused */
	public static function OnBeforeSave($arUserField, $value) {
		if ($arUserField['MULTIPLE'] === 'Y') { return $value; }

		if (isset($value) && is_array($value))
		{
			$value = serialize($value);
		}

		return $value;
	}

	public static function parseFieldValue($value) : array
	{
		if (is_string($value) && $value !== '')
		{
			$value = htmlspecialcharsback($value);
			$unserialized = unserialize($value, [
				'allowed_classes' => false,
			]);

			$value = is_array($unserialized) ? $unserialized : [];
		}
		else
		{
			$value = [];
		}

		return $value;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function GetAdminListViewHTML($userField, $htmlControl) : string
	{
		if ($userField['MULTIPLE'] === 'Y')
		{
			return '';
		}

		$value = self::parseFieldValue($htmlControl['VALUE']);

		$partials = [];

		foreach ($value as $name => $one)
		{
			if (isset($partials[$name])) { continue; }

			if (is_array($one))
			{
				$one = implode(', ', $one);
			}

			$partials[$name] = sprintf('%s: %s', $name, $one);
		}

		$result = implode('<br />', $partials);

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function getEditFormHTML($userField, $htmlControl) : string
	{
		$value = self::parseFieldValue($htmlControl['VALUE']);

		try
		{
			$result = self::editComponent(static::editDefaults($userField, $htmlControl['NAME']) + [
				'MULTIPLE' => 'N',
				'VALUE' => $value,
			]);
		}
		catch (Main\SystemException $exception)
		{
			$result = $exception->getMessage();
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function getEditFormHtmlMulty($userField, $htmlControl) : string
	{
		return self::getLocale('MULTIPLE_NOT_SUPPORTED');
	}

	protected static function editDefaults($property, $controlName) : array
	{
		$categoryOptions = self::categoryOptions($property, $controlName);

		return [
			'PROPERTY' => $property,
			'CONTROL' => ['VALUE' => $controlName],
			'CATEGORY_OPTIONS' => $categoryOptions,
		];
	}

	protected static function editComponent(array $parameters) : string
	{
		global $APPLICATION;

		return (string)$APPLICATION->IncludeComponent(
			'avito.export:admin.property.characteristic',
			'',
			$parameters,
			false,
			[
				'HIDE_ICONS' => 'Y',
			]
		);
	}

	protected static function categoryOptions($userField, $controlName) : array
	{
		$iblockId = Utils\SectionField::parseIblockId($userField);
		$categoryProperties = $controlName['CATEGORY_PROPERTIES'] ?? ValueInherit\Category::sectionProperties($iblockId);
		$options = [];

		foreach ($categoryProperties as [$type, $categoryPropertyId])
		{
			$behavior = FormCategory\Registry::make($type);

			$options[] = [ 'type' => $type ] + $behavior->options($userField, $categoryPropertyId);
		}

		return $options;
	}
}