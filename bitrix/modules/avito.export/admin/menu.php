<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (CMain::GetGroupRight('avito.export') < 'R') { return false; }

global $USER;

Loc::loadMessages(__FILE__);

return [
	'parent_menu' => 'global_menu_services',
	'section' => 'avitoexport',
	'sort' => 1000,
	'text' => Loc::getMessage('AVITO_EXPORT_MENU_CONTROL'),
	'title' => Loc::getMessage('AVITO_EXPORT_MENU_TITLE'),
	'icon' => 'avito_menu_icon',
	'items_id' => 'menu_avito_export',
	'items' => array_filter([
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_FEEDS'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_FEEDS'),
			'url' => 'avito_export_feeds.php?lang=' . LANGUAGE_ID,
			'more_url' => [
				'avito_export_feed_edit.php',
				'avito_export_feed_run.php',
				'avito_export_feeds.php',
			]
		],
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_EXCHANGE'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_EXCHANGE'),
			'url' => 'avito_export_exchange.php?lang=' . LANGUAGE_ID,
			'more_url' => [
				'avito_export_exchange_edit.php',
				'avito_export_exchange.php',
			],
		],
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_DOCUMENTS'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_DOCUMENTS'),
			'url' => 'avito_export_documents.php?lang=' . LANGUAGE_ID,
			'more_url' => [
				'avito_export_documents.php',
			],
			'hidden' => !(Main\ModuleManager::isModuleInstalled('sale'))
		],
        [
            'text' => Loc::getMessage('AVITO_EXPORT_MENU_CHAT'),
            'title' => Loc::getMessage('AVITO_EXPORT_MENU_CHAT'),
            'url' => 'avito_export_chat.php?lang=' . LANGUAGE_ID,
            'more_url' => [
                'avito_export_chat.php',
            ],
	        'hidden' => (Main\Config\Option::get('avito.export', 'enable_chat', 'Y') !== 'Y')
        ],
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_LOGS'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_LOGS'),
			'url' => 'avito_export_log.php?lang=' . LANGUAGE_ID,
		],
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_SETTINGS'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_SETTINGS'),
			'url' => 'settings.php?mid=avito.export&lang=' . LANGUAGE_ID,
			'hidden' => !($USER instanceof CUser && $USER->IsAdmin()),
		],
		[
			'text' => Loc::getMessage('AVITO_EXPORT_MENU_HELP'),
			'title' => Loc::getMessage('AVITO_EXPORT_MENU_HELP'),
			'url' => 'avito_export_help.php?lang=' . LANGUAGE_ID,
		],
	], static function(array $item) {
		return empty($item['hidden']);
	}),
];
