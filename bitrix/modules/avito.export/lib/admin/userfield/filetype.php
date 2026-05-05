<?php

namespace Avito\Export\Admin\UserField;

use Avito\Export\Concerns;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;

class FileType
{
	use Concerns\HasLocale;

	/** @noinspection HtmlUnknownTarget */
	public static function GetAdminListViewHTML(array $userField, array $htmlControl = []) : string
	{
		$name = (string)($htmlControl['VALUE'] ?? '');
		$name = trim($name);

		if ($name === '') { return ''; }

		$path = Path::combine(BX_ROOT . '/catalog_export', $name);
		$pathAbsolute = Path::convertRelativeToAbsolute($path);
		$file = new File($pathAbsolute);

		if ($file->isExists())
		{
			$contents = sprintf('<a href="%s" target="_blank">%s</a>', $path, $name);
			$status = 'green';
		}
		else if (isset($userField['SETTINGS']['RESTORE_URL']))
		{
			$restoreUrl = $userField['SETTINGS']['RESTORE_URL'];
			$restoreUrl = str_replace('#ID#', $userField['ENTITY_VALUE_ID'], $restoreUrl);

			$contents = sprintf('%s, <a href="%s">%s</a>', $name, $restoreUrl, self::getLocale('RESTORE_LINK'));
			$status = 'red';
		}
		else
		{
			$contents = $name;
			$status = 'red';
		}

		return <<<CONTENT
			<span style="white-space: nowrap">
				<img class="b-log-icon" src="/bitrix/images/avito.export/{$status}.gif" width="14" height="14" alt="" style="vertical-align: text-top" />&nbsp;{$contents}
			</span>
CONTENT;
	}
}