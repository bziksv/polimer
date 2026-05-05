<?php

namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;

class BooleanType
{
	use Concerns\HasLocale;

	public const VALUE_N = '0';
	public const VALUE_Y = '1';

	/** @noinspection PhpUnusedParameterInspection */
	public static function GetAdminListViewHTML(array $userField, array $htmlControl = []) : string
	{
		$value = (bool)($htmlControl['VALUE'] ?? false);
		$suffix = $value ? 'Y' : 'N';

		return self::getLocale('VALUE_' . $suffix, null, $suffix);
	}

	public static function GetEditFormHTML($userField, $htmlControl) : string
	{
		$value = (string)$htmlControl['VALUE'] !== '' || $userField['ENTITY_VALUE_ID'] >= 1
			? (int)$htmlControl['VALUE']
			: (int)$userField['SETTINGS']['DEFAULT_VALUE'];
		$attributes = '';

		if ($value > 0)
		{
			$attributes .= ' checked';
		}

		if ($userField['EDIT_IN_LIST'] !== 'Y')
		{
			$attributes .= ' disabled';
		}

		/** @noinspection HtmlUnknownAttribute */
		return sprintf(<<<CONTENT
			<input type="hidden" value="%s" name="{$htmlControl["NAME"]}" />
			<input class="adm-designed-checkbox" type="checkbox" value="%s" name="{$htmlControl["NAME"]}" id="{$htmlControl["NAME"]}" {$attributes} />
			<label class="adm-designed-checkbox-label" for="{$htmlControl["NAME"]}"></label>
CONTENT
			,
			static::VALUE_N,
			static::VALUE_Y
		);
	}
}
