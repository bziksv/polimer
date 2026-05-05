<?php
namespace Avito\Export\Admin\UserField\Helper;

class Renderer
{
	public static function editHtml(array $field, $value = null) : string
	{
		global $USER_FIELD_MANAGER;

		$field['VALUE'] = $value;

		/** @noinspection PhpCastIsUnnecessaryInspection */
		$html = (string)$USER_FIELD_MANAGER->GetEditFormHTML(false, null, $field);
		$parsed = static::parseEditHtml($html);

		return $parsed['CONTROL'];
	}

	protected static function parseEditHtml(string $html) : array
	{
		$result = [
			'ROW_CLASS' => '',
			'VALIGN' => '',
			'CONTROL' => $html,
		];

		if (preg_match('/^<tr(.*?)>(?:<td(.*?)>.*?<\/td>)?<td.*?>(.*)<\/td><\/tr>$/s', $html, $match))
		{
			$rowAttributes = trim($match[1]);
			$rowClassName = '';
			$titleAttributes = trim($match[2]);
			$titleVerticalAlign = null;

			if (preg_match('/class="(.*?)"/', $rowAttributes, $rowMatches))
			{
				$rowClassName = $rowMatches[1];
			}

			if (preg_match('/valign="(.*?)"/', $titleAttributes, $titleMatches))
			{
				$titleVerticalAlign = $titleMatches[1];
			}
			else if (mb_strpos($titleAttributes, 'adm-detail-valign-top') !== false)
			{
				$titleVerticalAlign = 'top';
			}

			$result['ROW_CLASS'] = $rowClassName;
			$result['VALIGN'] = $titleVerticalAlign;
			$result['CONTROL'] = $match[3];
		}

		return $result;
	}
}