<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

// outlet VALUE may contain a safe map <a> built in adminview (text already htmlspecialcharsbx)
$allowHtmlProperties = [
	'outlet' => true,
];

?>
<h3 class="yamarket-properties-title"><?= Loc::getMessage('YANDEX_MARKET_T_TRADING_ORDER_VIEW_DELIVERY_TITLE') ?></h3>
<div class="js-yamarket-order__area" data-type="deliveryProperties">
	<?php
	foreach ($arResult['DELIVERY'] as $property)
	{
		$printValue = isset($allowHtmlProperties[$property['ID']])
			? $property['VALUE']
			: htmlspecialcharsbx($property['VALUE'], ENT_COMPAT, false);

		?>
		<div class="yamarket-property">
			<div class="yamarket-property__title"><?= htmlspecialcharsbx((string)$property['NAME']); ?></div>
			<div class="yamarket-property__value">
				<?= $printValue ?>
				<?php
				if (isset($property['ACTIVITY_ACTION']))
				{
					include __DIR__ . '/property-activity.php';
				}
				?>
			</div>
		</div>
		<?php
	}
	?>
</div>
