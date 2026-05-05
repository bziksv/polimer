<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;

class TimeType extends StringType
{
	use Concerns\HasLocale;

	/** @noinspection HtmlUnknownTarget */
	public static function GetEditFormHTML(array $userField, array $htmlControl = []) : string
	{
		$size = !empty($userField['SETTINGS']['SIZE']) ? (int)$userField['SETTINGS']['SIZE'] : 5;

		if ($userField['ENTITY_VALUE_ID'] < 1 && (string)$userField['SETTINGS']['DEFAULT_VALUE'] !== '')
		{
			$htmlControl['VALUE'] = htmlspecialcharsbx($userField['SETTINGS']['DEFAULT_VALUE']);
		}

		/** @noinspection PhpArrayWriteIsNotUsedInspection */
		$htmlControl['VALIGN'] = 'middle';

		$attributes = 'size="' . $size . '"';
		$attributes .= ($userField['SETTINGS']['MAX_LENGTH'] > 0 ? 'maxlength="'. $userField['SETTINGS']['MAX_LENGTH'] . '"': '');
		$attributes .= ($userField['EDIT_IN_LIST'] !== 'Y' ? ' disabled="disabled"': '');

		return <<<CONTENT
			<input class="adm-input" type="time" name="{$htmlControl['NAME']}" {$attributes} value="{$htmlControl['VALUE']}">
CONTENT;
	}
}