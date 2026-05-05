<?php
namespace Avito\Export\DB;

use Avito\Export\Admin\UserField;
use Bitrix\Main;

abstract class Table extends Main\ORM\Data\DataManager
{
	public static function getTableIndexes() : array
	{
		return [];
	}

	public static function getMapDescription() : array
	{
		$result = [];

		/** @var Main\Entity\Field $field */
		foreach (static::getEntity()->getFields() as $field)
		{
			$fieldName = $field->getName();
			$userField = [];
			$userType = null;

			if (isset($result[$fieldName])) { continue; }

			switch (true)
			{
				case ($field instanceof Main\Entity\EnumField): // enum

					$userType = 'enumeration';
					$userField['VALUES'] = [];
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					foreach ($field->getValues() as $option)
					{
						$userField['VALUES'][] = [
							'ID' => $option,
							'VALUE' => static::getFieldEnumTitle($fieldName, $option, $field),
						];
					}

					break;

				case ($field instanceof Main\Entity\DatetimeField): // datetime

					$userType = 'datetime';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;

				case ($field instanceof Main\Entity\DateField): // date

					$userType = 'date';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;

				case ($field instanceof Main\Entity\IntegerField): // int

					$userType = 'integer';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;

				case ($field instanceof Main\Entity\FloatField): // double

					$userType = 'double';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;

				case ($field instanceof Main\Entity\BooleanField): // boolean

					$userType = 'boolean';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;

				case ($field instanceof Main\Entity\ScalarField): // string
				case ($field instanceof Main\Entity\ExpressionField): // expression

					$userType = 'string';
					$userField['SETTINGS'] = [
						'DEFAULT_VALUE' => $field->getDefaultValue(),
					];

					break;
			}

			if (!isset($userType)) { continue; }

			$userField += [
				'USER_TYPE' => UserField\Registry::description($userType),
				'FIELD_NAME' => $fieldName,
				'LIST_COLUMN_LABEL' => $field->getTitle(),
				'DESCRIPTION' => Main\Localization\Loc::getMessage($field->getLangCode() . '_DESCRIPTION'),
				'HELP_MESSAGE' => Main\Localization\Loc::getMessage($field->getLangCode() . '_HELP_MESSAGE'),
				'MANDATORY' => (method_exists($field, 'isRequired') && $field->isRequired() ? 'Y' : 'N'),
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => (method_exists($field, 'isAutocomplete') && $field->isAutocomplete() ? 'N' : 'Y'),
			];

			$result[$fieldName] = $userField;
		}

		return $result;
	}

	public static function getFieldEnumTitle(string $fieldName, string $optionValue, Main\ORM\Fields\Field $field = null) : ?string
	{
		$result = null;

		if ($field === null)
		{
			$field = static::getEntity()->getField($fieldName);
		}

		if ($field)
		{
			$fieldEnumLangKey = $field->getLangCode() . '_ENUM_';
			$optionValueLangKey = str_replace(['.', ' ', '-'], '_', $optionValue);
			$optionValueLangKey = strtoupper($optionValueLangKey);

			$result = Main\Localization\Loc::getMessage($fieldEnumLangKey . $optionValueLangKey);
		}

		if ($result === null)
		{
			$result = $optionValue;
		}

		return $result;
	}
}
