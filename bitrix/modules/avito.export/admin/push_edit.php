<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Admin;
use Avito\Export\Push;
use Avito\Export\Glossary;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_TITLE'));

try
{
	if (!Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$request = Main\Context::getCurrent()->getRequest();
	$primary = $request->getQuery('id');
	$baseQuery = [
		'lang' => LANGUAGE_ID,
	];
	$componentParameters = [
		'TITLE' => Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_TITLE'),
		'FORM_ID' => 'AVITO_EXPORT_ADMIN_PUSH_EDIT',
		'ALLOW_SAVE' => CMain::GetGroupRight('avito.export') >= 'W',
		'PRIMARY' => $primary,
		'LIST_URL' => BX_ROOT . '/admin/avito_export_push.php?' . http_build_query($baseQuery),
		'PROVIDER_TYPE' => Admin\Component\Push\EditForm::class,
		'DATA_CLASS_NAME' => Push\Setup\RepositoryTable::class,
		'CONTEXT_MENU' => [
			[
				'ICON' => 'btn_list',
				'LINK' => BX_ROOT . '/admin/avito_export_push.php?' . http_build_query($baseQuery),
				'TEXT' => Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_CONTEXT_MENU_LIST'),
			],
		],
	];

	if ($primary === null)
	{
		$componentParameters['BUTTONS'] = [
			[ 'BEHAVIOR' => 'save' ],
		];
		$componentParameters['BTN_SAVE'] = Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_ADD_BUTTON');
		$componentParameters['SAVE_URL'] = $APPLICATION->GetCurPageParam('saved=Y&id=#ID#');
	}

	if ($primary !== null && $request->getQuery('saved') === 'Y')
	{
		$componentParameters['FORM_ACTION_URI'] = $APPLICATION->GetCurPageParam('', [ 'saved' ]);
		$componentParameters['MESSAGE'] = [
			'TYPE' => 'OK',
			'MESSAGE' => Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_ADDED_TITLE'),
			'DETAILS' => Loc::getMessage('AVITO_EXPORT_ADMIN_PUSH_EDIT_PAGE_ADDED_DETAILS', [
				'#LOG_URL#' => 'avito_export_log.php?' . http_build_query([
					'lang' => LANGUAGE_ID,
					'find_setup_id' => Glossary::SERVICE_PUSH . ':' . (int)$primary,
					'set_filter' => 'Y',
					'apply_filter' => 'Y',
				]),
			]),
			'HTML' => true,
		];
	}

	$APPLICATION->IncludeComponent('avito.export:admin.form.edit', '', $componentParameters);
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';