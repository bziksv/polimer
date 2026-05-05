<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main\Localization\Loc;

/**
 * @var $component Avito\Export\Components\AdminFormEdit
 * @var array $arResult
 * @var array $arParams
 */

global $APPLICATION;

if (empty($arResult['ITEM']['IBLOCK']))
{
	ShowError('need select iblock');
	return;
}

?>
<tr>
	<td colspan="2">
		<?php
		foreach ($arResult['ITEM']['IBLOCK'] as $iblockId)
		{
			$iblock = ($arResult['IBLOCK_DATA'][$iblockId] ?? null);
			$iblockValues = $arResult['ITEM']['FILTER'][$iblockId] ?? [];
			?>
			<div class="filter-section-iblock">
				<span class="b-adm-filter-title">
					<?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_IBLOCK', [
						'#IBLOCK_NAME#' => $iblock !== null ? '&laquo;' . $iblock['NAME'] . '&raquo;' : '#' . $iblock['ID'],
					])?>
				</span>
				<?php
				$APPLICATION->IncludeComponent('avito.export:admin.feed.filter', '', [
					'IBLOCK_ID' => $iblockId,
					'REGION_ID' => $arResult['ITEM']['REGION'],
					'VALUE' => $iblockValues,
					'NAME' => sprintf('FILTER[%s]', $iblockId),
				], $this->getComponent());
				?>
			</div>
			<?php
		}
		?>
	</td>
</tr>
<tr>
	<td colspan="2">
		<hr class="b-adm-hr" />
		<span class="b-adm-restriction-title"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT') ?></span>
		<?php
		$APPLICATION->IncludeComponent('avito.export:admin.category.limit', '', [
			'VALUES' => $arResult['ITEM']['CATEGORY_LIMIT'] ?? [],
		], $this->getComponent());
		?>
	</td>
</tr>
