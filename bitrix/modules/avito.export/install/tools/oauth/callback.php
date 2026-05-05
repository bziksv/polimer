<?php

use Bitrix\Main;
use Avito\Export\Api\OAuth;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php';

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException('avito.export is required');
	}

	$inviteRequest = new OAuth\Invite\Request();
	$incomingData = Main\Context::getCurrent()->getRequest()->getQueryList()->toArray();

	$inviteRequest->validateResponse($incomingData);
	$incoming = $inviteRequest->buildResponse($incomingData);

	$message = new CAdminMessage([
		'TYPE' => 'OK',
		'MESSAGE' => 'OK',
	]);
	$message->Show();

	$response = [
		'code' => $incoming->code(),
	];
}
catch (Main\SystemException $exception)
{
	$message = new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
	$message->Show();

	$response = [
		'error' => $exception->getMessage(),
	];
}

?>
<script>
	if (window.opener) {
		window.opener.postMessage(<?= Main\Web\Json::encode($response + [
			'sign' => 'avitoOAuthCallback',
		]) ?>, '*');

		window.close();
	}
</script>
<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php';