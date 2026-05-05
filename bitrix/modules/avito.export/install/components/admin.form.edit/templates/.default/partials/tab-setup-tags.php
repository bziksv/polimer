<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var $APPLICATION \CMain */
/** @var $this \CBitrixComponentTemplate */
/** @var $tab array */
/** @var $fields array */
/** @var $component Avito\Export\Components\AdminFormEdit */

if (empty($arResult['ITEM']['IBLOCK']))
{
	ShowError('need select iblock');
	return;
}

foreach ($arResult['ITEM']['IBLOCK'] as $iblockId)
{
	$iblock = ($arResult['IBLOCK_DATA'][$iblockId] ?? null);
	$iblockValues = $arResult['ITEM']['TAGS'][$iblockId] ?? [];

	?>
	<tr class="heading">
		<td colspan="2">
			<?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_IBLOCK', [
				'#IBLOCK_NAME#' => $iblock !== null ? '&laquo;' . $iblock['NAME'] . '&raquo;' : '#' . $iblock['ID']
			]) ?>
			<?php
			if (in_array('SITE', $fields, true))
			{
				include __DIR__ . '/special-site.php';
			}
			?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<?php
			$APPLICATION->IncludeComponent('avito.export:admin.feed.tag', '', [
				'IBLOCK_ID' => $iblockId,
				'SITE_ID' => $arResult['ITEM']['SITE'],
				'REGION_ID' => $arResult['ITEM']['REGION'],
				'VALUE' => $iblockValues,
				'NAME' => sprintf('TAGS[%s]', $iblockId),
			], $this->getComponent());
			?>
		</td>
	</tr>
	<?php
}
