<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

/** @var $component Avito\Export\Components\AdminFormEdit */
/** @var $field array */

if (!empty($field['HELP_MESSAGE']))
{
	ShowJSHint($field['HELP_MESSAGE']);
}

echo $component->getFieldTitle($field);