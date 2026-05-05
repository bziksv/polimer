<?php

use Bitrix\Main;
use Avito\Export\Admin;
use Avito\Export\Assert;

const BX_SECURITY_SESSION_READONLY = true;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

session_write_close();

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('require module avito.export');
	}

	if (!Admin\Access::isReadAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$httpRequest = Main\Context::getCurrent()->getRequest();
	$personTypeId = $httpRequest->getPost('personTypeId');

	Assert::notNull($personTypeId, 'personTypeId');

	$enum = Admin\UserField\OrderPropertyType::variants((int)$personTypeId);

	$data = [
		'status' => 'ok',
		'enum' => $enum,
	];
}
catch (Main\SystemException $exception)
{
	$data = [
		'status' => 'error',
		'message' => $exception->getMessage(),
	];
}

Main\Application::getInstance()->end(0, new Main\Engine\Response\Json($data));
