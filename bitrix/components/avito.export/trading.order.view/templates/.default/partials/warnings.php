<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

if (empty($arResult['WARNINGS'])) { return; }

echo '<p>';
echo implode('<br />', $arResult['WARNINGS']);
echo '</p>';