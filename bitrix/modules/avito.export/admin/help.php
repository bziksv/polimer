<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

global $APPLICATION;

Loc::loadMessages(__FILE__);

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_ADMIN_HELP_PAGE_TITLE'));

	?>

	<iframe style=" width: 100%; min-height: 800px; border: none" src="https://docs.google.com/document/d/e/2PACX-1vSYjIrjvC0k4qvTWc5203yePDCc6KzmQLRCR5DhgTFpXhTzVSz9nFHyYgfJtCgySmNL4lr1jMxhHtZ9/pub?embedded=true"></iframe>

	<?php
}
catch (Main\SystemException $exception)
{
	echo (new CAdminMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]))->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
