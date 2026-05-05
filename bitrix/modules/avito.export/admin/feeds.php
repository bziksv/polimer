<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$title = Loc::getMessage('AVITO_EXPORT_ADMIN_FEEDS_PAGE_TITLE');

	global $APPLICATION;
	$APPLICATION->SetTitle($title);

	$controller = new Export\Admin\Page\Feeds();
	$controller->renderPage();
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
