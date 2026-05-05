<?php

define("PUBLIC_AJAX_MODE", true);

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin.php';

use Avito\Export\Admin;
use Bitrix\Main;

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('avito.export is required');
	}

	$modal = new Admin\UseCase\MassiveEdit\Modal();
	$modal->loadModules();

	if ($modal->hasRequest())
	{
		$modal->processRequest();
	}
	else
	{
		$modal->show();
	}
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage($exception->getMessage()))->Show();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';