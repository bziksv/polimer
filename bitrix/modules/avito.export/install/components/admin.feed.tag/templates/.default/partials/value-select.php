<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Avito\Export\Feed\Tag;
use Avito\Export\Feed\Source;

/** @var Tag\Tag $tag */
/** @var Source\FetcherPool $fetcherPool */
/** @var Source\Context $context */
/** @var array $tagValue */
/** @var string $rowBaseName */
/** @var bool $isDisabled */

$foundSelected = false;

?>
<select name="<?= $rowBaseName ?>[VALUE]" data-entity="value" <?= $isDisabled ? 'disabled' : '' ?>>
	<option value=""><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VALUE_PLACEHOLDER') ?></option>
 	<?php
	// recommendation

	$groupContent = '';
	$recommendationTitleUsage = [];
	$recommendationEnum = [];

	foreach ($tag->recommendation($context, $fetcherPool) as $recommendation)
	{
		if (isset($recommendation['VALUE']))
		{
			$optionType = null;
			$optionValue = $recommendation['VALUE'];
			$optionTitle = $recommendation['DISPLAY'] ?? $recommendation['VALUE'];
		}
		else
		{
			/** @noinspection DuplicatedCode */
			$fetcher = $fetcherPool->some($recommendation['TYPE']);
			$field = array_reduce($fetcher->fields($context), static function($found, Source\Field\Field $field) use ($recommendation) {
				return $found ?? ($recommendation['FIELD'] === $field->id() ? $field : null);
			});

			if ($field === null) { continue; }

			$optionType = $recommendation['TYPE'];
			$optionValue = $recommendation['TYPE'] . '.' . $recommendation['FIELD'];
			$optionTitle = $field->name();
		}

		$recommendationEnum[] = [
			'VALUE' => $optionValue,
			'TITLE' => $optionTitle,
			'TYPE' => $optionType,
		];

		if (isset($recommendationTitleUsage[$optionTitle]))
		{
			++$recommendationTitleUsage[$optionTitle];
		}
		else
		{
			$recommendationTitleUsage[$optionTitle] = 1;
		}
	}

	foreach ($recommendationEnum as $recommendationOption)
	{
		$optionValue = $recommendationOption['VALUE'];
		$optionTitle = $recommendationOption['TITLE'];

		if ($recommendationTitleUsage[$optionTitle] > 1 && $recommendationOption['TYPE'] !== null)
		{
			$fetcher = $fetcherPool->some($recommendationOption['TYPE']);
			$optionTitle = $fetcher->title() . ': ' . $optionTitle;
		}

		$isSelected = (!$foundSelected && $tagValue['VALUE'] === $optionValue);

		if ($isSelected) { $foundSelected = true; }

		/** @noinspection HtmlUnknownAttribute */
		$groupContent .= sprintf(
			'<option value="%s" %s>%s</option>',
			htmlspecialcharsbx($optionValue),
			$isSelected ? 'selected' : '',
			htmlspecialcharsbx($optionTitle)
		);
	}

	if ($groupContent !== '')
	{
		echo sprintf('<optgroup label="%s">', htmlspecialcharsbx(Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VALUE_RECOMMENDATION')));
		echo $groupContent;
		echo '</optgroup>';
	}

	// all fields

	$supported = $tag->supported();
    $supportedMap = $supported !== null ? array_flip($supported) : null;

	foreach ($fetcherPool->all() as $type => $fetcher)
	{
		if (!$tag->fetcherSupported($fetcher)) { continue; }

		$groupContent = '';

		foreach ($fetcher->fields($context) as $field)
		{
			if (!$field->selectable()) { continue; }
			if ($supportedMap !== null && !isset($supportedMap[$field->type()])) { continue; }

			$optionValue = $type . '.' . $field->id();
			$isSelected = (!$foundSelected && $tagValue['VALUE'] === $optionValue);

			if ($isSelected) { $foundSelected = true; }

			/** @noinspection HtmlUnknownAttribute */
			$groupContent .= sprintf(
				'<option value="%s" %s>%s</option>',
				htmlspecialcharsbx($optionValue),
				$isSelected ? 'selected' : '',
				htmlspecialcharsbx($field->name())
			);
		}

		if ($groupContent === '') { continue; }

		echo sprintf('<optgroup label="%s">', htmlspecialcharsbx($fetcher->title()));
		echo $groupContent;
		echo '</optgroup>';
	}

	// text value

    if (!$foundSelected && (string)$tagValue['VALUE'] !== '')
    {
	    $selectedBehavior = $tagValue['BEHAVIOR'] ?? null;

	    /** @noinspection HtmlUnknownAttribute */
	    echo sprintf(
			'<option selected %s>%s</option>',
			$selectedBehavior !== null ? 'data-format="' . htmlspecialcharsbx($selectedBehavior) . '"' : '',
			htmlspecialcharsbx($tagValue['VALUE'])
		);
    }
	?>
</select>
