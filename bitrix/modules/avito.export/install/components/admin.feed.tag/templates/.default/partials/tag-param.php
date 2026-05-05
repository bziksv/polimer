<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Avito\Export\Config;
use Avito\Export\Feed\Tag;
use Avito\Export\Admin\View\Attributes;

/** @var Tag\Format $format */
/** @var Tag\Tag $tag */
/** @var bool $isDisabled */
/** @var bool $hasFew */
/** @var array $rowAttributes */
/** @var string $rowBaseName */
/** @var string $templateFolder */

/** @noinspection DuplicatedCode */
$hint = $tag->hint();

$useTemplates = Config::getOption('templates_for_all', 'N') === 'Y';

?>
<div class="avito-tag-complex <?= $isDisabled ? 'avito--hidden' : '' ?>" <?= Attributes::stringify($rowAttributes) ?>>
	<div class="avito-tag-container">
		<div class="avito-tag-title <?= $tag->required() === true ? 'avito--required' : ''?>">
			<input type="hidden" name="<?= $rowBaseName ?>[CODE]" value="<?= $tag->name() ?>" data-entity="code" <?= $isDisabled ? 'disabled' : '' ?> />
			<?php
			if ($hint !== '')
			{
				?>
				<span data-entity="hint" data-hint="<?= htmlspecialcharsbx($hint) ?>"></span>
				<?php
			}
			?>
			<span class="avito-tag-name" data-entity="name"><?= htmlspecialcharsbx($tag->title() ?: ('<' . $tag->name() . '>')) ?></span>
		</div>
		<div class="avito-tag-value">
			<input name="<?= $rowBaseName ?>[TAG]" type="text" value="<?= htmlspecialcharsbx($tagValue['TAG'] ?? '') ?>" <?= $isDisabled ? 'disabled' : '' ?> data-entity="tag" />
			<button type="button" class="avito-row-delete <?= !$hasFew && $tag->required() === true ? 'avito--hidden' : '' ?>" data-entity="delete"></button>
		</div>
	</div>
	<div class="avito-tag-container">
		<div class="avito-tag-title">
			<span class="avito-tag-name"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_PARAM_VALUE') ?></span>
		</div>
		<div class="avito-tag-value <?=$useTemplates ? 'has--template' : ''?>">
			<?php
			include __DIR__ . '/value-select.php';
			include __DIR__ . '/value-behavior.php';

			if ($useTemplates)
			{
				?>
				<button type="button" class="avito-tag-template" title="<?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VALUE_TEMPLATE') ?>" data-entity="template">
					<svg class="avito-tag-template__icon">
						<use xlink:href="<?= $templateFolder ?>/i/template.svg#avito-icon-tag-template"></use>
					</svg>
				</button>
				<?php
			}
			?>
		</div>
	</div>
</div>
