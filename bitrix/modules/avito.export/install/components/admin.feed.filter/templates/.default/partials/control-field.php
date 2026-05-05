<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $arResult */
/** @var array $value */
/** @var string $name */
/** @var null $selectedField */

?>
<select name="<?= $name ?>[FIELD]" data-entity="field">
	<?php
	$previousFieldGroup = null;

	foreach ($arResult['FIELDS'] as $field)
	{
		if ($previousFieldGroup !== $field['GROUP'])
		{
			if ($previousFieldGroup !== null) { echo '</optgroup>'; }

			$previousFieldGroup = $field['GROUP'];

			echo sprintf('<optgroup label="%s">', htmlspecialcharsbx($field['GROUP']));
		}

		$isSelected = ($value['FIELD'] === $field['ID']);

		if ($isSelected)
		{
			$selectedField = $field;
			$foundSelected = true;
		}
		else if ($selectedField === null)
		{
			$selectedField = $field;
		}

		/** @noinspection HtmlUnknownAttribute */
		echo sprintf(
			'<option value="%s" %s>%s</option>',
			htmlspecialcharsbx($field['ID']),
			$isSelected ? 'selected' : '',
			htmlspecialcharsbx($field['NAME'])
		);
	}

	if ($previousFieldGroup !== null) { echo '</optgroup>'; }
	?>
</select>
