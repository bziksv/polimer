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

    if (!$httpRequest->isPost() || !check_bitrix_sessid())
    {
        throw new Main\AccessDeniedException();
    }

    $httpRequestData = $httpRequest->getPostList()->toArray();
    $testParameters = array_intersect_key($httpRequestData, [
        'url' => true,
        'site' => true,
    ]);

    $site = trim($testParameters['site'] ?? '');
    $siteRow = \Bitrix\Main\SiteTable::getRowById($site);

    if ($siteRow === null) {
        throw new \Bitrix\Main\SystemException('Unknown site ID.');
    }

    $url = trim($testParameters['url'] ?? '');
    $parts = parse_url($url);
    $host = preg_replace('/:\d+$/', '', (string)($parts['host'] ?? ''));
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $allowedHost = preg_replace('/:\d+$/', '', trim((string)($siteRow['SERVER_NAME'] ?? '')));

    if (
        $url === ''
        || !filter_var($url, FILTER_VALIDATE_URL)
        || !in_array($scheme, ['http', 'https'], true)
        || $host === ''
        || $allowedHost === ''
        || strcasecmp($host, $allowedHost) !== 0
    )
    {
        throw new Main\SystemException('URL must match current site host (SERVER_NAME).');
    }

    if (
        filter_var($host, FILTER_VALIDATE_IP)
        && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    )
    {
        throw new Main\SystemException('Private/reserved hosts are not allowed.');
    }

    $testParameters['url'] = $url;
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