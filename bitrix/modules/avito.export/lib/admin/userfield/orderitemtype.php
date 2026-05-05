<?php
/** @noinspection PhpUnused */
namespace Avito\Export\Admin\UserField;

use Avito\Export\Admin;

class OrderItemType extends StringType
{
	/**
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlUnknownAttribute
	 */
	public static function getAdminListViewHtmlMulty($userField, $htmlControl) : string
	{
		$values = Helper\Value::asMultiple($userField, $htmlControl);
		$partials = [];

		foreach ($values as $item)
		{
			$title = !empty($item['ID']) ? sprintf('[%s] %s', $item['ID'], $item['TITLE']) : $item['TITLE'];
			$value = sprintf('%s&nbsp;x&nbsp;%s', $item['COUNT'], htmlspecialcharsbx($title));

			if (!empty($item['SERVICE_URL']))
			{
				$value .= '&nbsp;';
				$value .= sprintf(
					'<a href="%s" target="_blank"><img src="%s" width="16" height="16" style="vertical-align: bottom" alt="" /></a>',
					$item['SERVICE_URL'],
					'/bitrix/js/avitoexport/trading/i/share_icon.svg'
				);
			}

			if (!empty($item['CHAT_URL']))
			{
				$onClick = '';

				if ($item['CHAT_ENABLE'])
				{
					$onClick = sprintf(
						'onclick=\'BX.util.popup("%s", 500, 800); return false;\'',
						Admin\Path::moduleUrl('chat', [
							'lang' => LANGUAGE_ID,
							'view' => 'window',
							'setup' => $userField['SETTINGS']['SETUP_ID'],
							'chatId' => $item['CHAT_ID'],
						])
					);
				}

				$value .= '&nbsp;';
				$value .= sprintf(
					'<a href="%s" target="_blank" %s><img src="%s" width="16" height="16" style="vertical-align: bottom" alt="" /></a>',
					$item['CHAT_URL'],
					$onClick,
					'/bitrix/js/avitoexport/trading/i/chat_icon.svg'
				);
			}

			$partials[] = $value;
		}

		return implode('<br />', $partials);
	}
}