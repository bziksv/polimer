<?php

global $APPLICATION;

$APPLICATION->SetTitle(GetMessage('AVITO_EXPORT_UNINSTALL'));
?>
<form action="<?=$APPLICATION->GetCurPage();?>">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID;?>">
	<input type="hidden" name="id" value="avito.export">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<p><input type="checkbox" name="savedata" id="savedata" value="Y" checked><label
			for="savedata"><?=GetMessage('AVITO_EXPORT_UNINSTALL_SAVE_DATA')?></label></p>
	<input type="submit" name="inst" value="<?=GetMessage('AVITO_EXPORT_UNINSTALL_RUN');?>">
</form>
