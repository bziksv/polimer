<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

const NOT_CHECK_FILE_PERMISSIONS = true;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;

$request = Main\Context::getCurrent()->getRequest();
$requestView = $request->get('view');
$assets = Main\Page\Asset::getInstance();

if ($requestView === 'dialog')
{
	$assets = $assets->setAjax();
	$APPLICATION->oAsset = $assets;
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$controller = new Export\Admin\Page\Activity();
	$controller->show();
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

if ($requestView === 'dialog')
{
	echo $assets->getCss();
	echo $assets->getJs();
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_before.php';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';