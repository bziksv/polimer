<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Main\Localization\Loc::loadMessages(__DIR__ . '/index.php');

global $APPLICATION;

$APPLICATION->SetTitle(Loc::getMessage('AVITO_EXPORT_INSTALLED_TITLE', [ "#MODULE_NAME#" => Loc::getMessage('AVITO_EXPORT_MODULE_NAME') ]));

$message = new CAdminMessage([
	'TYPE' => 'OK',
	'MESSAGE' => Loc::getMessage("AVITO_EXPORT_INSTALLED_INTRO", [
		"#MODULE_NAME#" => Loc::getMessage('AVITO_EXPORT_MODULE_NAME'),
	]),
]);
echo $message->Show();

echo Loc::getMessage("AVITO_EXPORT_INSTALLED_TEXT", [
	'#LANGUAGE#' => LANGUAGE_ID,
]);

?>
<div id="loader" style="position: relative; width: 150px;"></div>
<script>BX.showWait('loader');</script>
<iframe style=" width: 100%; min-height: 800px; border: none"
		src="https://docs.google.com/document/d/e/2PACX-1vSYjIrjvC0k4qvTWc5203yePDCc6KzmQLRCR5DhgTFpXhTzVSz9nFHyYgfJtCgySmNL4lr1jMxhHtZ9/pub?embedded=true"
		onload="document.getElementById('loader').style.display='none'; BX.closeWait('loader');"></iframe>
<br/><br/>
<form action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
	<input type="submit" value="<?= Loc::getMessage('AVITO_EXPORT_INSTALLED_CLOSE') ?>">
</form>