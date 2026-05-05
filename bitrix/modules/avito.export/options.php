<?php

use Avito\Export;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

try
{
	if (!Main\Loader::includeModule('avito.export'))
	{
		throw new Main\SystemException(Loc::getMessage('AVITO_EXPORT_NO_MODULE'));
	}

	( new Export\Admin\Page\Options() )->renderPage();
}
catch (Main\SystemException $exception)
{
	\CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => $exception->getMessage(),
	]);
}

