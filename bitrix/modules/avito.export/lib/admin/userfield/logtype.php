<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Psr\Logger\LogLevel;

class LogType extends EnumerationType
{
	protected static $optionCache;
	protected static $levels = [
		LogLevel::CRITICAL => 'red',
		LogLevel::EMERGENCY => 'red',
		LogLevel::ALERT => 'red',
		LogLevel::ERROR => 'red',
		LogLevel::WARNING => 'yellow',
		LogLevel::INFO => 'green',
	];

	/** @noinspection HtmlUnknownTarget */
	public static function GetAdminListViewHTML($userField, $htmlControl) : string
	{
		$option = static::getOption($userField, $htmlControl['VALUE']);

		if ($option === null) { return ''; }

		$imgType = static::$levels[$htmlControl['VALUE']] ?? 'green';

		$result = '<span style="white-space: nowrap">';
		$result .= sprintf('<img class="b-log-icon" src="/bitrix/images/avito.export/%s.gif" width="14" height="14" alt="" style="vertical-align: text-top" />', $imgType);
		$result .= '&nbsp;';
		$result .= $option['VALUE'];
		$result .= '</span>';

		return $result;
	}

	protected static function getOption($arUserField, $id)
	{
		if (static::$optionCache === null)
		{
			static::$optionCache = [];

			$query = call_user_func([$arUserField['USER_TYPE']['CLASS_NAME'], 'getList'], $arUserField);

			while ($option = $query->fetch())
			{
				static::$optionCache[$option['ID']] = $option;
			}
		}

		return static::$optionCache[$id] ?? null;
	}
}
