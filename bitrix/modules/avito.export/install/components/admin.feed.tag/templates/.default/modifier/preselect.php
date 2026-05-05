<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Feed\Tag;
use Avito\Export\Feed\Source;

/** @var Source\FetcherPool $fetcherPool */
/** @var Source\Context $context */
/** @var Tag\Format $format */

if (!empty($iblockValues)) { return; }

$iblockValues = [];
$found = [];

foreach ($format->tags() as $tag)
{
	if ($tag->deprecated()) { continue; }

	$required = $tag->required();
	$preselect = $tag->preselect();
	$selected = null;

	foreach ($tag->recommendation($context, $fetcherPool) as $recommendation)
	{
		/** @noinspection DuplicatedCode */
		if (isset($recommendation['VALUE']))
		{
			$optionValue = $recommendation['VALUE'];
		}
		else
		{
			/** @noinspection DuplicatedCode */
			$fetcher = $fetcherPool->some($recommendation['TYPE']);
			$field = array_reduce($fetcher->fields($context), static function($found, Source\Field\Field $field) use ($recommendation) {
				return $found ?? ($recommendation['FIELD'] === $field->id() ? $field : null);
			});

			if ($field === null) { continue; }

			$optionValue = $recommendation['TYPE'] . '.' . $recommendation['FIELD'];
		}

		$selected = $optionValue;
		break;
	}

	if (
		($preselect === false || $selected === null)
		&& (
			$required === false
			|| (is_array($required) && empty(array_diff($required, $found)))
		)
	)
	{
		continue;
	}

	$found[] = $tag->name();

	$iblockValues[] = [
		'CODE' => $tag->name(),
		'VALUE' => $selected,
	];
}
