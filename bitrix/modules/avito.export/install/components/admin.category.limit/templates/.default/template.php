<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web;
use Bitrix\Main\Localization\Loc;

/** @var $this \CBitrixComponentTemplate */
/** @var array $arResult */
/** @var array $arParams */

global $APPLICATION;

$values = isset($arParams['VALUES']) && is_array($arParams['VALUES']) ? $arParams['VALUES'] : [];

if (empty($values))
{
	$values[] = [
		'CATEGORY' => '',
		'LIMIT' => null,
		'PLACEHOLDER' => true,
	];
}

$typeName = 'CATEGORY_LIMIT';
$htmlId = 'avito-export-restrictions-' . $this->randString(4);
$index = 0;

?>
<div id="<?= $htmlId ?>">
	<?php

	foreach ($values as $item)
	{
		$isPlaceholder = !empty($item['PLACEHOLDER']);
		$nameControls = [
			'LIMIT' => sprintf('%s[%s][%s]',
				$typeName,
				$index,
				'LIMIT'
			),
			'CATEGORY' => sprintf('%s[%s][%s]',
				$typeName,
				$index,
				'CATEGORY'
			),
		];

		?>
		<div class="avito-limit-row <?= $isPlaceholder ? 'avito--hidden' : '' ?>" data-entity="row">
			<div class="avito-limit-column for--category">
				<label class="avito-limit-label"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT_NAME_CATEGORY') ?></label>
				<?php
				echo $APPLICATION->IncludeComponent('avito.export:admin.property.category', '.default', [
					'MULTIPLE' => 'N',
					'ALLOW_NO_VALUE' => 'N',
					'VALUE' => $item['CATEGORY'],
					'CONTROL_NAME' => $nameControls['CATEGORY'],
					'ADDITIONAL_ATTRIBUTES' => [
						'data-entity' => 'category',
						'disabled' => $isPlaceholder,
					],
					'SKIP_INIT' => 'Y',
				], false, [ 'HIDE_ICONS' => 'Y' ]);
				?>
			</div>
			<div class="avito-limit-column for--limit">
				<label class="avito-limit-label"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT_COUNT') ?></label>
				<input
					class="adm-input"
					type="number"
					step="1"
					min="0"
					max="100000"
					name="<?= $nameControls['LIMIT'] ?>"
					value="<?= htmlspecialcharsbx($item['LIMIT']) ?>"
					data-entity="limit"
					<?= $isPlaceholder ? 'disabled' : '' ?>
				/>
			</div>
			<button class="avito-limit-delete" type="button" data-entity="delete" title="<?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT_DELETE') ?>"></button>
		</div>
		<?php

		++$index;
	}
	?>
	<button type="button" class="avito-limit-add adm-btn" data-entity="addButton"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT_ADD') ?></button>
</div>

<script>
	BX.ready(function() {
		const container = document.getElementById("<?= $htmlId ?>");
		
		// noinspection BadExpressionStatementJS
		new BX.AvitoExport.Feed.CategoryLimit(container, <?= Web\Json::encode([
			'component' => $this->getComponent()->getName(),
			'baseName' => $typeName,
			'lang' => [
				'VALUE_PLACEHOLDER' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FORM_CATEGORY_LIMIT_CATEGORY_PLACEHOLDER')
			],
		], JSON_INVALID_UTF8_IGNORE) ?>);
	});
</script>
