<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_ADMIN_EXCHANGE_PAGE_TITLE'));

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		$message = Loc::getMessage('AVITO_EXPORT_NO_MODULE');
		throw new Main\SystemException($message);
	}

	$controller = new Export\Admin\Page\ExchangeGrid();
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
