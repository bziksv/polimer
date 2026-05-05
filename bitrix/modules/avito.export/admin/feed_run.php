<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

const BX_SESSION_ID_CHANGE = false;
const NOT_CHECK_FILE_PERMISSIONS = true;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$controller = new Export\Admin\Page\FeedRun();

	$controller->setTitle();
	$controller->loadModules();

	if ($controller->hasRequest())
	{
		$controller->processRequest();
	}

	$controller->checkReadAccess();

	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

	$controller->showForm();
}
catch (Main\AccessDeniedException $exception)
{
	$APPLICATION->AuthForm('');
}
catch (Main\SystemException $exception)
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';