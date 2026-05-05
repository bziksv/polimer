<?php

use Bitrix\Main;
use Avito\Export\Admin;
use Avito\Export\Assert;
use Avito\Export\Trading\Entity as TradingEntity;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('require module avito.export');
	}

	if (!Admin\Access::isWriteAllowed())
	{
		throw new Main\AccessDeniedException();
	}

	$environment = TradingEntity\Registry::environment();
	$httpRequest = Main\Context::getCurrent()->getRequest();

	$personTypeId = $httpRequest->getQuery('personTypeId');
	$userId = $httpRequest->getQuery('userId');
	$profileId = $httpRequest->getQuery('id');

	Assert::notNull($personTypeId, 'personTypeId');
	Assert::notNull($userId, 'userId');

	if ($profileId === null)
	{
		$addResult = $environment->buyerProfile()->create($userId, $personTypeId);

		Assert::result($addResult);

		$profileId = $addResult->getId();
	}

	$editUrl = $environment->buyerProfile()->editUrl($profileId);

	Assert::notNull($editUrl, 'editUrl');

	LocalRedirect($editUrl);
}
catch (Main\SystemException $exception)
{
	$message = new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage()
	]);

	echo $message->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';