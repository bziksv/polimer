<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Feed\Source\Field;

/** @var array $arResult */
/** @var array $value */
/** @var string $name */
/** @var array $selectedField */
/** @var string $selectedCompare */

$isCompareMultiple = Field\Condition::isMultiple($selectedCompare);

if ($selectedField['TEMPLATE'] === 'autocomplete')
{
	/** @var Field\Field|Field\Autocompletable $field */
	$field = $arResult['AUTOCOMPLETE'][$selectedField['ID']];
	/** @noinspection DuplicatedCode */
	$valueSelected = is_array($value['VALUE']) ? $value['VALUE'] : [ $value['VALUE'] ];
	$valueSelected = array_filter($valueSelected, static function($one) { return is_scalar($one) && (string)$one !== ''; });

	?>
	<select name="<?= $name ?>[VALUE]<?= $isCompareMultiple ? '[]' : '' ?>" <?= $isCompareMultiple ? 'multiple' : '' ?> data-entity="value">
		<?php
		foreach ($field->display($valueSelected) as $variant)
		{
			?>
			<option value="<?= htmlspecialcharsbx($variant['ID']) ?>" selected>
				<?= htmlspecialcharsbx($variant['VALUE']) ?>
			</option>
			<?php
		}
		?>
	</select>
	<?php
}
else if ($selectedField['TEMPLATE'] === 'select')
{
	/** @noinspection DuplicatedCode */
	$valueSelected = is_array($value['VALUE']) ? $value['VALUE'] : [ $value['VALUE'] ];
	$valueSelected = array_filter($valueSelected, static function($one) { return is_scalar($one) && (string)$one !== ''; });

	?>
	<select name="<?= $name ?>[VALUE]<?= $isCompareMultiple ? '[]' : '' ?>" <?= $isCompareMultiple ? 'multiple' : '' ?> data-entity="value">
		<?php
		foreach ($selectedField['VARIANTS'] as $variant)
		{
			/** @noinspection TypeUnsafeArraySearchInspection */
			$isSelected = in_array($variant['ID'], $valueSelected);

			?>
			<option value="<?= htmlspecialcharsbx($variant['ID']) ?>" <?= $isSelected ? 'selected' : '' ?>>
				<?= htmlspecialcharsbx($variant['VALUE']) ?>
			</option>
			<?php
		}
		?>
	</select>
	<?php
}
else if ($selectedField['TEMPLATE'] === 'datetime')
{
	?>
	<div class="adm-input-wrap adm-input-wrap-calendar" data-entity="valueAnchor">
		<input
			class="adm-input adm-input-calendar"
			type="text"
			name="<?= $name ?>[VALUE]"
			value="<?= htmlspecialcharsbx($value['VALUE']) ?>"
			data-entity="value"
		/>
		<span class="adm-calendar-icon"></span>
	</div>
	<?php
}
else if (Field\Condition::isMultiple($selectedCompare))
{
	/** @noinspection DuplicatedCode */
	$valueSelected = is_array($value['VALUE']) ? $value['VALUE'] : [ $value['VALUE'] ];
	$valueSelected = array_filter($valueSelected, static function($one) { return is_scalar($one) && (string)$one !== ''; });

	?>
	<select name="<?= $name ?>[VALUE][]" multiple data-entity="value">
		<?php
		foreach ($valueSelected as $one)
		{
			?>
			<option selected><?= htmlspecialcharsbx($one) ?></option>
			<?php
		}
		?>
	</select>
	<?php
}
else
{
	?>
	<input type="text" name="<?= $name ?>[VALUE]" value="<?= htmlspecialcharsbx($value['VALUE']) ?>" data-entity="value" />
	<?php
}
