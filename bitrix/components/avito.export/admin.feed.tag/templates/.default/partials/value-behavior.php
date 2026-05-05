<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $tagValue */
/** @var string $rowBaseName */
/** @var bool $useTemplates */

$selectedBehavior = $tagValue['BEHAVIOR'] ?? null;
$availableBehaviors = [
	'TEMPLATE' => $useTemplates,
];

foreach ($availableBehaviors as $behavior => $flag)
{
	if (!$flag) { continue; }

	?>
	<input
		class="avito-tag-behavior avito--hidden"
		type="radio"
		name="<?= $rowBaseName ?>[BEHAVIOR]"
		value="<?= $behavior ?>"
		<?= $behavior === $selectedBehavior ? 'checked' : '' ?>
		data-entity="format"
	/>
	<?php
}
