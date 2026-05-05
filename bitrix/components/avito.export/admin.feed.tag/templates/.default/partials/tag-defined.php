<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Feed\Tag;
use Avito\Export\Feed\Source;

/** @var Tag\Tag $tag */
/** @var Source\FetcherPool $fetcherPool */
/** @var Source\Context $context */
/** @var bool $isDisabled */
/** @var bool $hasFew */
/** @var array $rowAttributes */
/** @var string $rowBaseName */

if (!empty($tagValue['DISABLED'])) { return; }

$found = false;

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

	$found = true;

	?>
	<input type="hidden" name="<?= $rowBaseName ?>[CODE]" value="<?= $tag->name() ?>" />
	<input type="hidden" name="<?= $rowBaseName ?>[VALUE]" value="<?= htmlspecialcharsbx($optionValue) ?>" />
	<?php
	break;
}

if (!$found)
{
	ShowError('cant find recommendation for defined tag');
}