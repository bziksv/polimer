<?php

use Bitrix\Main;
use Yandex\Market;

define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

try
{
	if (!Main\Loader::includeModule('yandex.market'))
	{
		throw new Main\SystemException('require module yandex.market');
	}

	if (!Market\Ui\Access::isWriteAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();

	if (!$httpRequest->isPost() || !check_bitrix_sessid())
	{
		throw new Main\AccessDeniedException();
	}

	$setupId = (int)$httpRequest->getPost('setup');
	$path = (string)$httpRequest->getPost('path');
	$payload = (array)$httpRequest->getPost('payload');

	if ($path === '' || !preg_match('#^[a-z0-9_./-]+$#i', $path))
	{
		throw new Main\ArgumentException('Unknown action path');
	}

	$setup = Market\Trading\Setup\Model::loadById($setupId);
	$router = $setup->wakeupService()->getRouter();
	$action = $router->getDataAction($path, $setup->getEnvironment(), $payload);

	$action->process();

	$response = [
		'status' => 'ok',
	];
}
catch (Main\SystemException $exception)
{
	$response = [
		'status' => 'error',
		'message' => $exception->getMessage()
	];
}

Market\Utils\HttpResponse::sendJson($response);
