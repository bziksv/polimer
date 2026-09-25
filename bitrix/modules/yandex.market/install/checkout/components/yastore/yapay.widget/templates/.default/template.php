<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);
?>
<div id="ya-pay-widget"></div>
<script>
    var YaPayWidget = new YaPayWidget(<?=CUtil::PhpToJSObject($arParams, false, true)?>, <?=CUtil::PhpToJSObject($arResult, false, true)?>);
</script>
