<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);
?>
<div class="ya-pay-badge-split"></div>
<div class="ya-pay-badge-ultimate"></div>
<script>
    var YaPayBadge = new YaPayBadge(<?= CUtil::PhpToJSObject($arParams, false, true) ?>, <?= CUtil::PhpToJSObject($arResult, false, true) ?>);
</script>
