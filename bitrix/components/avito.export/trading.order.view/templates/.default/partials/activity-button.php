<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Trading\Entity\Sale\AdminExtension;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Security\Random;

/** @var array $arResult */
/** @var array $button */

Extension::load('avitoexport.trading.activity');

$htmlId = sprintf('avito-export-order-%s-%s-%s', $arResult['ORDER_ID'], $button['BEHAVIOR'], Random::getString(3, true));

?>
<input class="adm-btn <?= $button['REQUIRED'] ? 'adm-btn-save' : '' ?>" type="button" id="<?= $htmlId ?>" value="<?= $button['TITLE'] ?>" />
<script>
	(function() {
		const view = new BX.AvitoExport.Trading.Activity.View.Tab('#<?= $htmlId ?>', <?= Json::encode([
			'tabElement' => '#' . AdminExtension::ORDER_INFO_BLOCK_ID,
		]) ?>);

		BX.AvitoExport.Trading.Activity.Factory.make('<?=$button['BEHAVIOR']?>', view, <?= Json::encode(($button['UI_OPTIONS'] ?? []) + [
			'title' => $button['TITLE'],
			'confirm' => $button['CONFIRM'],
			'url' => $button['URL'],
		]) ?>);
	})();
</script>