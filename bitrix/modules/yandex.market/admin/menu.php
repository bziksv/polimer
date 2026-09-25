<?php

/** @global CMain $APPLICATION */
use Bitrix\Main\Localization\Loc;

$accessLevel = (string)CMain::GetGroupRight('yandex.market');

if ($accessLevel <= 'D') { return false; }

Loc::loadMessages(__FILE__);

$yaMenu = [
	[
		'parent_menu' => 'global_menu_services',
		'section' => 'yamarket_origin',
		'sort' => 1050,
		'text' => Loc::getMessage('YANDEX_MARKET_MENU_ORIGIN_ROOT'),
		'title' => Loc::getMessage('YANDEX_MARKET_MENU_ORIGIN_ROOT'),
		'icon' => 'yamarket_assortment_icon',
		'items_id' => 'menu_yamarket',
		'items' => [
			[
				'text' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
				'title' => Loc::getMessage('YANDEX_MARKET_MENU_SETUP'),
				'url' => 'yamarket_setup_list.php?lang=' . LANGUAGE_ID . '&find_group=0&set_filter=Y&apply_filter=Y',
				'more_url' => [
					'yamarket_setup_list.php',
					'yamarket_setup_edit.php',
					'yamarket_setup_group_edit.php',
					'yamarket_setup_run.php',
					'yamarket_migration.php',
					'yamarket_checker.php',
				],
				'rights' => 'PE',
			],
			[
				'text' => Loc::getMessage('YANDEX_MARKET_MENU_COLLECTION'),
				'title' => Loc::getMessage('YANDEX_MARKET_MENU_COLLECTION'),
				'url' => 'yamarket_collection_list.php?lang=' . LANGUAGE_ID,
				'more_url' => [
					'yamarket_collection_edit.php',
					'yamarket_collection_run.php',
					'yamarket_collection_result.php',
				],
				'rights' => 'PE',
			],
			[
				'text' => Loc::getMessage('YANDEX_MARKET_MENU_PROMO'),
				'title' => Loc::getMessage('YANDEX_MARKET_MENU_PROMO'),
				'url' => 'yamarket_promo_list.php?lang=' . LANGUAGE_ID,
				'more_url' => [
					'yamarket_promo_list.php',
					'yamarket_promo_edit.php',
					'yamarket_promo_run.php',
					'yamarket_promo_result.php',
				],
				'rights' => 'PE',
			],
			[
				'text' => Loc::getMessage('YANDEX_MARKET_MENU_LOG'),
				'title' => Loc::getMessage('YANDEX_MARKET_MENU_LOG'),
				'url' => 'yamarket_log.php?lang=' . LANGUAGE_ID,
				'more_url' => [
					'yamarket_log.php',
				],
				'rights' => 'PE',
			],
			[
				'text' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
				'title' => Loc::getMessage('YANDEX_MARKET_MENU_HELP'),
				'url' => 'https://yandex.ru/support/market-cms/',
				'more_url' => [],
				'rights' => 'PE',
			],
		],
	],
	[
		'parent_menu' => 'global_menu_services',
		'section' => 'yamarket_ycp_settings',
		'sort' => 1055,
		'text' => Loc::getMessage('YANDEX_MARKET_MENU_YCP_SETTINGS'),
		'title' => Loc::getMessage('YANDEX_MARKET_MENU_YCP_SETTINGS'),
		'icon' => 'yamarket_basket_icon',
		'url' => 'yamarket_kit_options.php?lang=' . LANGUAGE_ID,
		'more_url' => [
			'yamarket_kit_options.php',
		],
		'rights' => 'PE',
	],
];

// filter items by access rights

foreach ($yaMenu as $yaRootLevelKey => &$yaRootLevel)
{
	if (!empty($yaRootLevel['hidden']))
	{
		unset($yaMenu[$yaRootLevelKey]);
		continue;
	}

	if (isset($yaRootLevel['rights']))
	{
		if ($accessLevel[0] < $yaRootLevel['rights'][0])
		{
			$isMatchModuleRights = false;
		}
		else if ($accessLevel[0] > $yaRootLevel['rights'][0])
		{
			$isMatchModuleRights = true;
		}
		else
		{
			$isMatchModuleRights = ($accessLevel === $yaRootLevel['rights']);
		}

		if (!$isMatchModuleRights)
		{
			unset($yaMenu[$yaRootLevelKey]);
			continue;
		}
	}

	if (!isset($yaRootLevel['items']))
	{
		continue;
	}

	foreach ($yaRootLevel['items'] as $yaItemKey => $yaItem)
	{
		// hidden

		if (!empty($yaItem['hidden']))
		{
			unset($yaRootLevel['items'][$yaItemKey]);
			continue;
		}

		// access

		$yaItemRights = isset($yaItem['rights']) ? $yaItem['rights'] : 'R';

		if ($accessLevel[0] < $yaItemRights[0])
		{
			$isMatchModuleRights = false;
		}
		else if ($accessLevel[0] > $yaItemRights[0])
		{
			$isMatchModuleRights = true;
		}
		else
		{
			$isMatchModuleRights = ($accessLevel === $yaItemRights);
		}

		if (!$isMatchModuleRights)
		{
			unset($yaRootLevel['items'][$yaItemKey]);
		}
	}

	if (isset($yaRootLevel['items']) && empty($yaRootLevel['items']))
	{
		unset($yaMenu[$yaRootLevelKey]);
	}
}
unset($yaRootLevel);

return $yaMenu;
