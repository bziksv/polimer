<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;

header('PageSpeed: off');

$request = Main\Context::getCurrent()->getRequest();
$assets = Main\Page\Asset::getInstance();
$requestView = $request->get('view');

if ($requestView === 'window')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_ADMIN_DOCUMENTS_PAGE_TITLE'));

try
{
    if (!Main\Loader::includeModule('avito.export'))
	{
        $message = Loc::getMessage('AVITO_EXPORT_NO_MODULE');
        throw new Main\SystemException($message);
    }

    $controller = new Export\Admin\Page\Chat();
    $controller->renderPage($requestView);
}
catch (Export\Admin\Exception\UserException $exception)
{
    echo (new CAdminMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => $exception->getMessage(),
        'DETAILS' => $exception->getDetails(),
        'HTML' => true,
    ]))->Show();
}
catch (Main\SystemException $exception)
{
    echo (new CAdminMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => $exception->getMessage(),
    ]))->Show();
}

if ($requestView === 'window')
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_before.php';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';