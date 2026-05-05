<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Admin\View\Attributes;
use Avito\Export\Feed\Source\Field\Condition;

/** @var array $arResult */
/** @var array $value */
/** @var array $selectedField */
/** @var string $name */
/** @var null $selectedCompare */

?>
<select class="avito-filter-compare" name="<?= $name ?>[COMPARE]" data-entity="compare">
	<?php
	foreach (Condition::all() as $type => $rules)
	{
		$attributes = [
			'value' => $type,
			'selected' => false,
			'disabled' => !in_array($type, $selectedField['CONDITIONS'], true),
			'data-multiple' => Condition::isMultiple($type),
		];

		if ($value['COMPARE'] === $type)
		{
			$attributes['selected'] = true;
			$selectedCompare = $type;
		}
		else if ($selectedCompare === null)
		{
			$selectedCompare = $type;
		}

		?>
		<option <?= Attributes::stringify($attributes) ?>><?= Condition::title($type) ?></option>
		<?php
	}
	?>
</select>