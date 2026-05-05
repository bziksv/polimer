<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Api;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_TOOLS_OAUTH_EXCHANGE_MODULE_REQUIRED'));
	}

	if (CMain::GetGroupRight('avito.export') < 'W')
	{
		throw new Main\AccessDeniedException(Loc::getMessage('AVITO_EXPORT_TOOLS_OAUTH_EXCHANGE_ACCESS_DENIED'));
	}

	$incomingData = Main\Context::getCurrent()->getRequest()->getPostList()->toArray();
	$controller = new Api\OAuth\ClientCredentials\Controller($incomingData);

	$token = $controller->run();
	$token->installAgent();

	$response = [
		'success' => true,
		'id' => $token->getServiceId(),
		'name' => $token->getName(),
	];
}
catch (\Throwable $exception)
{
	$response = [
		'success' => false,
		'message' => $exception->getMessage(),
	];
}

\CMain::FinalActions(Main\Web\Json::encode($response));
