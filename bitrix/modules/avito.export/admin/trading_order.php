<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION;

$request = Main\Context::getCurrent()->getRequest();

$assets = Main\Page\Asset::getInstance();
$assets = $assets->setAjax();
$APPLICATION->oAsset = $assets;

$template = $request->get('template');
if ($template === null)
{
	$template = '.default';
}

$APPLICATION->IncludeComponent('avito.export:trading.order.view', $template, [
	'EXTERNAL_ID' => $request->get('externalId'),
	'EXTERNAL_NUMBER' => $request->get('externalNumber'),
	'SETUP_ID' => $request->get('setupId'),
], false, [ 'HIDE_ICONS' => 'Y' ]);

echo $assets->getCss();
echo $assets->getJs();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php';