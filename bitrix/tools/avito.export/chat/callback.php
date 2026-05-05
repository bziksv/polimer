<?php

const STOP_STATISTICS = true;
const NO_AGENT_CHECK = true;
const NOT_CHECK_PERMISSIONS = true;
const DisableEventsCheck = true;
const PUBLIC_AJAX_MODE = true;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main;
use Avito\Export;

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('Module avito.export is required');
	}

	$page = new Export\Admin\Page\ChatCallback();
	$page->handle();
}
catch (\Throwable $exception)
{
	trigger_error($exception->getMessage(), E_USER_WARNING);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';