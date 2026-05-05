<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Feed\Source;

/** @var array $arParams */

$context = new Source\Context((int)$arParams['IBLOCK_ID'], null, (int)$arParams['REGION_ID']);
$fetcherPool = new Source\FetcherPool();
$fields = [];
$autocompleteMap = [];

foreach ($fetcherPool->all() as $type => $fetcher)
{
	$group = $fetcher->title();

	foreach ($fetcher->fields($context) as $field)
	{
		if (!$field->filterable()) { continue; }

		$sign = $type . '.' . $field->id();

		$row = [
			'ID' => $sign,
			'NAME' => $field->name(),
			'GROUP' => $group,
			'TEMPLATE' => 'text',
			'CONDITIONS' => $field->conditions(),
		];

		if ($field instanceof Source\Field\Autocompletable && $field->autocomplete())
		{
			$row['TEMPLATE'] = 'autocomplete';
			$autocompleteMap[$sign] = $field;
		}
		else if ($field instanceof Source\Field\EnumField)
		{
			$row['TEMPLATE'] = 'select';
			$row['VARIANTS'] = $field->variants();
		}
		else if ($field instanceof Source\Field\DateField)
		{
			$row['TEMPLATE'] = 'datetime';
		}

		$fields[] = $row;
	}
}

$arResult['FIELDS'] = $fields;
$arResult['AUTOCOMPLETE'] = $autocompleteMap;
