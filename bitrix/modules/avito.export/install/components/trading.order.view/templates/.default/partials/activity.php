<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var array $arResult */

if (empty($arResult['ACTIVITIES'])) { return; }

$attentionOpen = false;
$skip = [
	'setMarkings' => true,
];

echo '<div class="avito-export-activity-buttons">';

if (!empty($arResult['ATTENTION']))
{
	if (isset($arResult['ACTIVITIES']['reject']) && count(array_diff_key($arResult['ACTIVITIES'], $skip)) === 1)
	{
		echo BeginNote('style="margin-bottom: 10px"');
		echo $arResult['ATTENTION'];
		echo EndNote();
	}
	else
	{
		$attentionOpen = true;

		echo BeginNote();
		echo $arResult['ATTENTION'];
	}
}

foreach ($arResult['ACTIVITIES'] as $name => $button)
{
	if (isset($skip[$name])) { continue; }

	include __DIR__ . '/activity-button.php';
}

if ($attentionOpen)
{
	echo EndNote();
}

echo '</div>';