<?php

use Yandex\Market;
use Bitrix\Main;

define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

try
{
    global $APPLICATION;

    if ($APPLICATION->GetGroupRight('yandex.market') !== 'W') {
        throw new \Bitrix\Main\AccessDeniedException();
    }

    if (!Main\Loader::includeModule('yandex.market'))
    {
        throw new Main\SystemException('require module yandex.market');
    }

    if (!Market\Ui\Access::isReadAllowed())
    {
        throw new Main\AccessDeniedException();
    }

    $httpRequest = Main\Context::getCurrent()->getRequest();

    if (!$httpRequest->isPost())
    {
        throw new Main\SystemException('Only POST requests are allowed.');
    }

    $httpRequestData = $httpRequest->getPostList()->toArray();
    $testParameters = array_intersect_key($httpRequestData, [
        'url' => true,
        'site' => true,
    ]);

    $url = trim($testParameters['url'] ?? '');
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL))
    {
        throw new Main\SystemException('Invalid or empty URL.');
    }

    $site = trim($testParameters['site'] ?? '');
    $siteRow = \Bitrix\Main\SiteTable::getRowById($site);

    if ($siteRow === null) {
        throw new \Bitrix\Main\SystemException('Unknown site ID.');
    }

    $test = new Market\Ui\Trading\HelloTest($testParameters);

    $testResult = $test->run();
    $test->show($testResult);
}
catch (Main\SystemException $exception)
{
    \CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => $exception->getMessage()
    ]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';