<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Admin\UserField;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_TOOLS_OAUTH_REFRESH_MODULE_REQUIRED'));
	}

	if (CMain::GetGroupRight('avito.export') < 'R')
	{
		throw new Main\AccessDeniedException(Loc::getMessage('AVITO_EXPORT_TOOLS_OAUTH_REFRESH_ACCESS_DENIED'));
	}

	$clientId = Main\Context::getCurrent()->getRequest()->getPost('clientId');

	Assert::notNull($clientId, 'clientId');

	$variants = [];

	$query = UserField\OAuthTokenType::GetList([
		'ROW' => [
			'COMMON_SETTINGS' => [ 'CLIENT_ID' => $clientId ],
		],
	]);

	while ($row = $query->Fetch())
	{
		$variants[] = $row;
	}

	$response = [
		'success' => true,
		'variants' => $variants,
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
