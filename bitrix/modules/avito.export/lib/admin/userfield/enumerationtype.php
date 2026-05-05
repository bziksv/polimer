<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Bitrix\Main;
use Avito\Export\Admin\View;
use Avito\Export\Concerns as GlobalConcerns;
use Bitrix\Main\Web\Json;

class EnumerationType
{
	use GlobalConcerns\HasLocale;
	use Concerns\HasCompatibleExtends;

	public static function getCommonExtends() : string
	{
		return Main\UserField\Types\EnumType::class;
	}

	public static function getCompatibleExtends() : string
	{
		/** @noinspection PhpDeprecationInspection */
		return \CUserTypeEnum::class;
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

	public static function GetList($userField) : \CDBResult
	{
		$result = new \CDBResult();
		$result->InitFromArray($userField['VALUES']);

		return $result;
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		if (isset($userField['SETTINGS']['DISPLAY']) && $userField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			return static::callParent('GetEditFormHTML', [$userField, $htmlControl]);
		}

		return static::editSelect($userField, $htmlControl);
	}

	public static function GetEditFormHTMLMulty($userField, $htmlControl) : string
	{
        Main\UI\Extension::load('avitoexport.ui.admin.collection');

		if (isset($userField['SETTINGS']['DISPLAY']) && $userField['SETTINGS']['DISPLAY'] === 'CHECKBOX')
		{
			return static::callParent('GetEditFormHTMLMulty', [$userField, $htmlControl]);
		}

		$layout = $userField['SETTINGS']['LAYOUT'] ?? 'DEFAULT';

		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$values = array_filter($values, static function($value) { return (string)$value !== ''; });
		$values = array_unique($values);
		$inputName = preg_replace('/\[]$/', '', $htmlControl['NAME']);
        $elementId = str_replace(['[', ']'], '_', $inputName);

		$result = sprintf(
			'<table id="%s" style="%s">',
            'table_' . $elementId,
			$layout === 'INLINE' ? 'display: inline-table; vertical-align: top;' : ''
		);

		if (empty($values)) { $values[] = null; }

		foreach ($values as $index => $value)
		{
			$result .= '<tr class="js-input-collection__item"><td>';
			$result .= static::editSelect(
                $userField,
                [
                    'VALUE' => $value,
                    'NAME' => $inputName . '[' . (int)$index . ']',
			    ],
                [
                    'class' => 'js-input-collection__select',
                    'data-index' => (int) $index,
                ]
            );
            $result .= '<a href="#" class="avito-collection-button avito-collection-delete-button js-input-collection__delete"></a>';

            if ((int) $index >= count($values) - 1)
            {
                $result .= '<a href="#" class="avito-collection-button avito-collection-add-button js-input-collection__add"></a>';
            }

			$result .= '</td></tr>';
		}

		$result .= '</table>';

        /** @noinspection BadExpressionStatementJS */
        $result .= sprintf(<<<SCRIPT
            <script>new BX.AvitoExport.Ui.Admin.Collection('%s', %s)</script>
SCRIPT
            ,
            '#table_' . $elementId,
            Json::encode([ 'inputName' => $inputName ])
        );

		return $result;
	}

	protected static function editSelect($userField, $htmlControl, array $attributes = []) : string
	{
		$query = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'getList'], $userField);
		$variants = Helper\Variants::toArray($query);
		$attributes += [
			'name' => $htmlControl['NAME'],
			'disabled' => $userField['EDIT_IN_LIST'] !== 'Y',
			'style' => 'max-width: 300px;',
			'onchange' => $userField['SETTINGS']['ONCHANGE'] ?? null,
		];
		$attributes += $userField['SETTINGS']['ATTRIBUTES'] ?? [];

		if ($userField['SETTINGS']['LIST_HEIGHT'] > 1)
		{
			$attributes['size'] = $userField['SETTINGS']['LIST_HEIGHT'];
		}
		else
		{
			$htmlControl['VALIGN'] = 'middle';
		}

		if (
			(string)$htmlControl['VALUE'] === ''
			&& (!isset($userField['ENTITY_VALUE_ID']) || (int)$userField['ENTITY_VALUE_ID'] < 1)
		)
		{
			$htmlControl['VALUE'] = static::searchDefaultValue($userField, $variants);
		}

		if (!isset($userField['SETTINGS']['ALLOW_NO_VALUE']))
		{
			$userField['SETTINGS']['ALLOW_NO_VALUE'] = ($userField['MANDATORY'] !== 'Y');
		}
		else if (is_string($userField['SETTINGS']['ALLOW_NO_VALUE']))
		{
			$userField['SETTINGS']['ALLOW_NO_VALUE'] = ($userField['SETTINGS']['ALLOW_NO_VALUE'] === 'Y');
		}

		return View\Select::edit($variants, $htmlControl['VALUE'], $attributes, $userField['SETTINGS'] ?? []);
	}

	public static function GetAdminListViewHTML($userField, $htmlControl) : string
	{
		if (empty($htmlControl['VALUE'])) { return '&nbsp;'; }

		$result = '[' . htmlspecialcharsbx($htmlControl['VALUE']) . ']';
		$query = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'getList'], $userField);

		while ($option = $query->Fetch())
		{
			if ((string)$option['ID'] === (string)$htmlControl['VALUE'])
			{
				$result = htmlspecialcharsbx($option['VALUE']);
				break;
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public static function GetAdminListViewHTMLMulty($userField, $htmlControl) : string
	{
		if (empty($htmlControl['VALUE'])) { return '&nbsp;'; }

		$partials = [];
		$query = call_user_func([$userField['USER_TYPE']['CLASS_NAME'], 'getList'], $userField);
		$valueList = (array)$htmlControl['VALUE'];
		$valueMap = array_flip($valueList);

		while ($option = $query->Fetch())
		{
			if (isset($valueMap[$option['ID']]))
			{
				$partials[] = htmlspecialcharsbx($option['VALUE']);
			}
		}

		return !empty($partials) ? implode(' / ', $partials) : '&nbsp;';
	}

	protected static function searchDefaultValue(array $userField, array $variants)
	{
		if (isset($userField['SETTINGS']['DEFAULT_VALUE']) && (string)$userField['SETTINGS']['DEFAULT_VALUE'] !== '')
		{
			$result = $userField['SETTINGS']['DEFAULT_VALUE'];
		}
		else
		{
			$result = null;

			foreach ($variants as $variant)
			{
				if ($variant['DEF'] === 'Y')
				{
					$result = $variant['ID'];
					break;
				}
			}
		}

		return $result;
	}
}
