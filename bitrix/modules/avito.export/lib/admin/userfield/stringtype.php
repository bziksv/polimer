<?php

namespace Avito\Export\Admin\UserField;

use Bitrix\Main;
use Avito\Export\Concerns as GlobalConcerns;

class StringType
{
	use GlobalConcerns\HasLocale;
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends() : string
	{
		return Main\UserField\Types\StringType::class;
	}

	public static function getCompatibleExtends() : string
	{
		/** @noinspection PhpDeprecationInspection */
		return \CUserTypeString::class;
	}

	public static function getUserTypeDescription() : array
	{
		$result = static::callParent('getUserTypeDescription');

		if (!empty($result['USE_FIELD_COMPONENT']))
		{
			$result['USE_FIELD_COMPONENT'] = false;
		}

		return $result;
	}

	public static function CheckFields($arUserField, $value)
	{
		return static::callParent('CheckFields', [$arUserField, $value]);
	}

	/** @noinspection PhpUnused */
	public static function GetFilterHTML($userField, $htmlControl)
	{
		return static::callParent('GetFilterHTML', [$userField, $htmlControl]);
	}

	/** @noinspection PhpUnused */
	public static function GetFilterData($arUserField, $arHtmlControl)
	{
		return static::callParent('GetFilterData', [$arUserField, $arHtmlControl]);
	}

	public static function getAdminListViewHtml(array $userField, ?array $additionalParameters) : string
	{
		$value = (string)($additionalParameters['VALUE'] ?? '');
		$value = htmlspecialcharsback($value);

		return htmlspecialcharsbx($value, ENT_COMPAT, false);
	}

	public static function GetEditFormHTML(array $userField, array $htmlControl) : string
	{
		if (isset($htmlControl['VALUE'], $userField['SETTINGS']['DEFAULT_VALUE']) && $htmlControl['VALUE'] !== '')
		{
			unset($userField['SETTINGS']['DEFAULT_VALUE']);
		}

		return static::makeInput($userField, $htmlControl);
	}

	/** @noinspection PhpUnused */
	public static function GetEditFormHtmlMulty(array $userField, array $htmlControl) : string
	{
		$htmlControl['NAME'] = preg_replace('/\[]$/', '', $htmlControl['NAME']);

		$fieldId = preg_replace('/[^a-z0-9_]/i', '_', $userField['FIELD_NAME']);
		$tableId = 'table_' . $fieldId;
		$result = sprintf('<table id="%s">', $tableId);
		$index = 0;
		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$allowAdd = true;

		if (empty($values)) { $values[] = null; }

		if (!empty($userField['SETTINGS']['MULTIPLE_CNT']))
		{
			$multipleCount = (int)$userField['SETTINGS']['MULTIPLE_CNT'];
			$valuesCount = count($values);

			if ($valuesCount < $multipleCount)
			{
				array_push($values, ...array_fill(0, $multipleCount - $valuesCount, null));
			}

			if (isset($userField['SETTINGS']['MULTIPLE_FIXED']) && $userField['SETTINGS']['MULTIPLE_FIXED'] === 'Y')
			{
				$allowAdd = false;

				if ($valuesCount > $multipleCount)
				{
					array_splice($values, $multipleCount);
				}
			}
		}

		foreach ($values as $value)
		{
			$result .= sprintf(
				'<tr><td>%s</td></tr>',
				static::makeInput(array_diff_key($userField, [
					'VALUE' => true,
				]), [
					'NAME' => $htmlControl['NAME'] . '[' . $index . ']',
					'VALUE' => $value,
				])
			);

			++$index;
		}

		if ($allowAdd)
		{
			$result .= '<tr><td>';
			$result .= sprintf(
				'<input type="button" value="%s" onClick="addNewRow(\'%s\', \'%s\')">',
				self::getLocale('ADD'),
				$tableId,
				implode('|', [
					$fieldId,
					str_replace('[', '\\\[', $htmlControl['NAME']),
				])
			);
			$result .= '</tr></td>';
		}

		$result .= '</table>';

		return $result;
	}

	/** @noinspection HtmlUnknownAttribute */
	protected static function makeInput(array $userField, array $htmlControl) : string
	{
		$value = Helper\Value::asSingle($userField, $htmlControl);

		if ($userField['SETTINGS']['ROWS'] < 2)
		{
			$attributes = [
				'type' => 'text',
				'name' => $htmlControl['NAME'],
			];
			$attributes += array_filter([
				'id' => isset($userField['SETTINGS']['HTML_ID']) ? (string)$userField['SETTINGS']['HTML_ID'] : null,
				'size' => isset($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : null,
				'maxlength' => isset($userField['SETTINGS']['MAX_LENGTH']) ? (int)$userField['SETTINGS']['MAX_LENGTH'] : null,
				'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
				'data-multiple' => $userField['MULTIPLE'] !== 'N',
			]);

			return sprintf(
				'<input %s value="%s" />',
				Helper\Attributes::stringify($attributes),
				htmlspecialcharsbx($value)
			);
		}

		$attributes = [
			'name' => $htmlControl['NAME'],
		];
		$attributes += array_filter([
            'id' => isset($userField['SETTINGS']['HTML_ID']) ? (string)$userField['SETTINGS']['HTML_ID'] : null,
			'cols' => isset($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : null,
			'rows' => isset($userField['SETTINGS']['ROWS']) ? (int)$userField['SETTINGS']['ROWS'] : null,
			'maxlength' => isset($userField['SETTINGS']['MAX_LENGTH']) ? (int)$userField['SETTINGS']['MAX_LENGTH'] : null,
			'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
			'data-multiple' => $userField['MULTIPLE'] !== 'N',
		]);

		return sprintf(
			'<textarea %s>%s</textarea>',
			Helper\Attributes::stringify($attributes),
			htmlspecialcharsbx($value)
		);
	}
}
