<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */

$isFirst = true;

foreach ($arResult['PROPERTIES'] as $block)
{
	if (empty($block['FIELDS'])) { continue; }

	?>
	<h3 class="avito-export-properties-title <?= $isFirst ? 'pos--top' : '' ?>"><?= $block['NAME'] ?></h3>
	<div>
		<?php
		foreach ($block['FIELDS'] as $property)
		{
			?>
			<div class="avito-export-property">
				<div class="avito-export-property__title"><?= $property['NAME'] ?></div>
				<div class="avito-export-property__value"><?= $property['VALUE'] ?></div>
			</div>
			<?php
		}
		?>
	</div>
	<?php

	$isFirst = false;
}