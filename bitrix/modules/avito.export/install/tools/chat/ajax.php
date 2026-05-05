<?php

const STOP_STATISTICS = true;
const NO_AGENT_CHECK = true;
const NOT_CHECK_PERMISSIONS = true;
const DisableEventsCheck = true;
const PUBLIC_AJAX_MODE = true;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main;
use Avito\Export\Chat;
use Avito\Export\Api;

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('Module avito.export is required');
	}

	$request = Main\Context::getCurrent()->getRequest();
	$setupId = $request->get('setupId');
	$chatId = $request->get('chatId');
	$userId = $request->get('userId');

	$chatSetup = Chat\Informer\Actualizer::chatSetup($setupId);

	Chat\Informer\Actualizer::markRead($setupId, [$chatId]);
	Chat\Informer\Actualizer::scheduleClear($setupId, [$chatId]);

	$chatRequest = new Api\Messenger\V1\Accounts\Chats\Messages\Read\Request();
	$chatRequest->token($chatSetup->getSettings()->commonSettings()->token());
	$chatRequest->userId($userId);
	$chatRequest->chatId($chatId);
	$chatRequest->execute();
}
catch (\Throwable $exception)
{
	trigger_error($exception->getMessage(), E_USER_WARNING);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';