<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

try
{
	if (!Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$request = Main\Context::getCurrent()->getRequest();
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];

	$APPLICATION->IncludeComponent('avito.export:admin.form.edit', '', [
		'TITLE' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROFILE_FEED_EDIT_TITLE_EDIT'),
		'TITLE_ADD' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROFILE_FEED_EDIT_TITLE_ADD'),
		'FORM_ID' => 'AVITO_EXPORT_ADMIN_FEED_EDIT',
		'ALLOW_SAVE' => CMain::GetGroupRight('avito.export') >= 'W',
		'PRIMARY' => $request->getQuery('id'),
		'COPY' => $request->getQuery('copy') === 'Y',
		'LIST_URL' => BX_ROOT . '/admin/avito_export_feeds.php?' . http_build_query($baseQuery),
		'SAVE_URL' => BX_ROOT . '/admin/avito_export_feed_run.php?id=#ID#&' . http_build_query($baseQuery),
		'FORM_BEHAVIOR' => 'steps',
		'PROVIDER_TYPE' => Export\Admin\Component\Feed\EditForm::class,
		'DATA_CLASS_NAME' => Avito\Export\Feed\Setup\RepositoryTable::class,
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => BX_ROOT . '/admin/avito_export_feeds.php?' . http_build_query($baseQuery),
				'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROFILE_FEED_EDIT_CONTEXT_MENU_LIST'),
			],
		],
		'TABS' => [
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_FEED_EDIT_TAB_COMMON'),
				'fields' => [
					'NAME',
					'FILE_NAME',
					'HTTPS',
					'IBLOCK',
					'REGION',
					'AUTO_UPDATE',
					'REFRESH_PERIOD',
					'REFRESH_TIME',
				]
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_FEED_EDIT_TAB_FILTER'),
				'layout' => 'setup-filter',
				'fields' => [
					'FILTER',
					'CATEGORY_LIMIT',
				]
			],
			[
				'name' => Loc::getMessage('AVITO_EXPORT_ADMIN_FEED_EDIT_TAB_TAGS'),
				'layout' => 'setup-tags',
				'final' => true,
				'fields' => [
					'TAGS',
					'SITE',
				]
			],
		],
	]);
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';