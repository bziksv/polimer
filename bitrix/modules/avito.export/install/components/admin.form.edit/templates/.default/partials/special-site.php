<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Bitrix\Iblock;
use Bitrix\Main\Localization\Loc;

/** @var array $arResult */
/** @var int $iblockId */

if (empty($arResult['FIELDS']['SITE']['VALUES']) || !Main\Loader::includeModule('iblock')) { return; }

$query = Iblock\IblockSiteTable::getList([
	'filter' => [ '=IBLOCK_ID' => $iblockId, ],
	'select' => [ 'SITE_ID' ],
]);

$iblockSites = $query->fetchAll();
$iblockSites = array_column($iblockSites, 'SITE_ID', 'SITE_ID');

if (empty($iblockSites)) { return; }

if (count($iblockSites) > 1)
{
	Loc::loadMessages(__FILE__);

	Main\UI\Extension::load('avitoexport.ui.labelselect');

	$selectedSiteId = $arResult['ITEM']['SITE'][$iblockId] ?? null;
	$htmlId = 'avito-iblock-site-' . $iblockId;
	$selectedSiteName = null;
	$siteSelectOptions = [];

	foreach ($arResult['FIELDS']['SITE']['VALUES'] as $option)
	{
		if (!isset($iblockSites[$option['ID']])) { continue; }

		$isSelected = (string)$option['ID'] === (string)$selectedSiteId;

		if ($isSelected || $selectedSiteName === null)
		{
			$selectedSiteName = $option['VALUE'];
		}

		/** @noinspection HtmlUnknownAttribute */
		$siteSelectOptions[] = sprintf('<option value="%s" %s>%s</option>', htmlspecialcharsbx($option['ID']), $isSelected ? 'selected' : '', htmlspecialcharsbx($option['VALUE']));
	}
	?>
	<label class="avito-label-select" id="<?= $htmlId ?>">
		<select name="SITE[<?= $iblockId ?>]" class="avito-label-select__select">
			<?= implode('', $siteSelectOptions) ?>
		</select>
		<?= Loc::getMessage('AVITO_EXPORT_ADMIN_SITE_FOR_LABEL') ?> &laquo;<span class="avito-label-select__value js-avito-label-select__value"><?=$selectedSiteName?></span>&raquo;
	</label>
	<script>
		new BX.AvitoExport.Ui.LabelSelect('#<?= $htmlId ?>');
	</script>
	<?php
}
else
{
	?>
	<input type="hidden" name="SITE[<?= $iblockId ?>]" value="<?= htmlspecialcharsbx(reset($iblockSites)) ?>" />
	<?php
}