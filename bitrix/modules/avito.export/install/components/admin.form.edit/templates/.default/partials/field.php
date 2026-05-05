<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Avito\Export;

/** @var $field array */
/** @var $component \Avito\Export\Components\AdminFormEdit */

$rowAttributes = [];
$fieldControl = $component->getFieldHtml($field, null, true);
$hasDescription = isset($field['DESCRIPTION']);
$hasNote = isset($field['NOTE']);
$hasAdditionalRow = ($hasDescription || $hasNote);
$fieldVerticalAlign = $fieldControl['VALIGN'] ?? 'middle';
$fieldControlCount = (
	mb_substr_count($fieldControl['CONTROL'], ' type="text"')
	+ mb_substr_count($fieldControl['CONTROL'], '<select')
	+ mb_substr_count($fieldControl['CONTROL'], '<textarea')
);
$fieldPushLabel = (
	$fieldVerticalAlign === 'top'
	&& $fieldControlCount === 1
);

include __DIR__ . '/field-depend.php';

if (isset($field['INTRO']))
{
	?>
	<tr class="avito-field-intro">
		<td class="adm-detail-content-cell-l pos-inner--bottom" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r pos-inner--bottom" width="60%">
			<small><?= $field['INTRO'] ?></small>
		</td>
	</tr>
	<?php
}
?>
	<tr <?=Export\Admin\View\Attributes::stringify($rowAttributes)?>>
		<td <?= Export\Admin\View\Attributes::stringify([
			'class' => array_keys(array_filter([
				'adm-detail-content-cell-l' => true,
				'pos-inner--bottom' => $hasAdditionalRow,
				'push--top' => $fieldPushLabel,
			])),
			'width' => '40%',
			'align' => 'right',
			'valign' => $fieldVerticalAlign,
		]) ?>>
			<?php
			include __DIR__ . '/field-title.php';
			?>
		</td>
		<td class="adm-detail-content-cell-r <?=$hasAdditionalRow ? 'pos-inner--bottom' : '' ?>" width="60%">
			<?= $fieldControl['CONTROL'] ?? '' ?>
		</td>
	</tr>
<?php

if ($hasAdditionalRow)
{
	?>
	<tr class="avito-field-additional">
		<td class="adm-detail-content-cell-l pos-inner--top" width="40%" align="right" valign="top">&nbsp;</td>
		<td class="adm-detail-content-cell-r pos-inner--top" width="60%">
			<?php
			if ($hasDescription)
			{
				echo '<small>' . $field['DESCRIPTION'] . '</small>';
			}

			if ($hasNote)
			{
				echo BeginNote();
				echo $field['NOTE'];
				echo EndNote();
			}
			?>
		</td>
	</tr>
	<?php
}
