<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

/** @var array $iblockValues */

$iblockValuesMap = [];

foreach ($iblockValues as $iblockValue)
{
	$code = (string)($iblockValue['CODE'] ?? '');

	if ($code === '') { continue; }

	if (!isset($iblockValuesMap[$code]))
	{
		$iblockValuesMap[$code] = [];
	}

	$iblockValuesMap[$code][] = $iblockValue;
}
