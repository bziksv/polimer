<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Avito\Export\Admin;

/** @var array $arResult */
/** @var array $arParams */

$basketNumber = 1;

if (isset($arResult['ACTIVITIES']['setMarkings']))
{
	?>
	<div class="avito-export-section-hgroup">
		<h2 class="avito-export-section-hgroup__inline avito-export-section-title"><?= Loc::getMessage('AVITO_EXPORT_BASKET_TITLE') ?></h2>
		<div class="avito-export-section-hgroup__inline">
			<?php
			$button = $arResult['ACTIVITIES']['setMarkings'];

			include __DIR__ . '/activity-button.php';
			?>
		</div>
	</div>
	<?php
}
else
{
	?>
	<h2 class="avito-export-section-title"><?= Loc::getMessage('AVITO_EXPORT_BASKET_TITLE') ?></h2>
	<?php
}
?>
<div class="avito-export-basket-wrapper adm-s-order-table-ddi">
	<table class="avito-export-basket-table adm-s-order-table-ddi-table adm-s-bus-ordertable-option">
		<thead>
		<tr>
			<td class="avito-export-basket-col for--number tal"><?=Loc::getMessage('AVITO_EXPORT_BASKET_NUMBER')?></td>
			<td class="avito-export-basket-col for--name tal"><?=Loc::getMessage('AVITO_EXPORT_BASKET_NAME')?></td>
			<td class="avito-export-basket-col for--price tal"><?=Loc::getMessage('AVITO_EXPORT_BASKET_PRICE')?></td>
			<td class="avito-export-basket-col for--quantity tal"><?=Loc::getMessage('AVITO_EXPORT_BASKET_QUANTITY')?></td>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ($arResult['BASKET_ROWS'] as $row)
		{
			?>
			<tr class="bdb-line">
				<td class="tal"><?= $basketNumber ?></td>
				<td class="tal">
					<?php
					$links = [
						'SERVICE_URL' => 'share',
						'CHAT_URL' => 'chat',
					];

					foreach ($links as $fieldName => $iconName)
					{
						if (empty($row[$fieldName])) { continue; }

						$onClick = '';

						if ($fieldName === 'CHAT_URL' && $row['CHAT_ENABLE'])
						{
							$onClick = sprintf(
								'onclick=\'BX.util.popup("%s", 500, 800); return false;\'',
								Admin\Path::moduleUrl('chat', [
									'lang' => LANGUAGE_ID,
									'view' => 'window',
									'setup' => $arParams['SETUP_ID'],
									'chatId' => $row['CHAT_ID'],
								])
							);
						}

						?><a class="avito-export-link-icon" <?= $onClick?> href="<?= $row[$fieldName] ?>" target="_blank"><?php
						?><img class="avito-export-link-icon__icon" src="/bitrix/js/avitoexport/trading/i/<?= $iconName ?>_icon.svg" alt="" /><?php
						?></a><?php
					}
					?>
					<?=$row['NAME']?>
				</td>
				<td class="tal">
					<?= $row['PRICE_FORMATTED'] ?><br />
					<small><?= Loc::getMessage('AVITO_EXPORT_BASKET_COMMISSION') ?>: <?= '-' . $row['COMMISSION_FORMATTED'] ?></small>
					<?php
					if (!empty($row['DISCOUNTS']))
					{
						?>
						<details>
							<summary><small><?= Loc::getMessage('AVITO_EXPORT_BASKET_DISCOUNT') . ': -' . $row['DISCOUNT_FORMATTED'] ?></small></summary>
							<?php
							foreach ($row['DISCOUNTS'] as $discount)
							{
								echo '<small>' . $discount['TYPE'] . ':&nbsp;-' . $discount['VALUE_FORMATTED'] . '</small><br>';
							}
							?>
						</details>
						<?php
					}
					else if ($row['DISCOUNT'] > 0)
					{
						echo '<small>' . Loc::getMessage('AVITO_EXPORT_BASKET_DISCOUNT') . ': -' . $row['DISCOUNT_FORMATTED'] . '</small>';
					}
					?>
				</td>
				<td class="tal"><?=$row['QUANTITY'] . '&nbsp;' . Loc::getMessage('AVITO_EXPORT_BASKET_QUANTITY_UNIT')?></td>
			</tr>
			<?php

			++$basketNumber;
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td class="avito-export-basket-summary" colspan="4">
					<?php
					foreach ($arResult['BASKET_TOTAL'] as $item)
					{
						echo $item['NAME'] . ':&nbsp;' . $item['VALUE_FORMATTED'] . '<br>';
					}
					?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
