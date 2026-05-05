<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Web\Json;
use Avito\Export\Feed\Tag;
use Bitrix\Main\UI\Extension;
use Avito\Export\Feed\Source;
use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $iblockValuesMap */

Extension::load([
	'avitoexport.vendor.select2',
]);

echo BeginNote('style="max-width: 550px; margin: 0 auto;"');
echo Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_WARNING');
echo EndNote();

$iblockId = (int)$arParams['IBLOCK_ID'];
$iblockValues = is_array($arParams['VALUE']) ? $arParams['VALUE'] : [];
$context = new Source\Context(
	(int)$arParams['IBLOCK_ID'],
	isset($arParams['SITE_ID'][$arParams['IBLOCK_ID']]) ? (string)$arParams['SITE_ID'][$arParams['IBLOCK_ID']] : null,
	(int)$arParams['REGION_ID']
);
$fetcherPool = new Source\FetcherPool();
$format = new Tag\Format();
$htmlId = 'avito-export-tags-' . $iblockId;
$valueIndex = 0;

include __DIR__ . '/modifier/preselect.php';
include __DIR__ . '/modifier/value-map.php';

?>
<div id="<?= $htmlId ?>" lang="<?= LANGUAGE_ID ?>">
	<?php
	foreach ($format->tags() as $tag)
	{
		$tagValues = $iblockValuesMap[$tag->name()] ?? [];

		if (empty($tagValues))
		{
			$required = $tag->required();

			$tagValues[] = [
				'VALUE' => null,
				'DISABLED' => !(
					$tag->defined()
					|| $required === true
					|| (
						is_array($required)
						&& !empty(array_diff_key(array_flip($required), $iblockValuesMap))
					)
				),
			];

			$iblockValuesMap[$tag->name()] = [];
		}

		$hasFew = (count($tagValues) > 1);

		foreach ($tagValues as $tagValue)
		{
			$isDisabled = ($tagValue['DISABLED'] ?? false);
			$rowBaseName = $arParams['NAME'] . sprintf('[%s]', $isDisabled ? -1 : $valueIndex);
			$rowAttributes = [
				'data-entity' => 'row',
				'data-required' => $tag->required() === true,
				'data-multiple' => $tag->multiple(),
			];

			if ($tag instanceof Tag\Param)
			{
				include __DIR__ . '/partials/tag-param.php';
			}
			else if ($tag->defined())
			{
				include __DIR__ . '/partials/tag-defined.php';
			}
			else
			{
				include __DIR__ . '/partials/tag-common.php';
			}

			if (!$isDisabled) { ++$valueIndex; }
		}
	}
	?>
	<div class="avito-tag-container">
		<div class="avito-tag-title"></div>
		<div class="avito-tag-value">
			<button class="adm-btn" type="button" data-entity="addButton">
				<?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_ADD_TAG') ?>
			</button>
		</div>
	</div>
</div>
<script>
	BX.ready(function() {
		const container = document.getElementById('<?= $htmlId ?>');

		new BX.AvitoExport.Feed.Tags(container, <?= Json::encode([
			'baseName' => $arParams['NAME'],
			'nextIndex' => $valueIndex,
			'lang' => [
				'VALUE_PLACEHOLDER' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_SELECT_PLACEHOLDER'),
				'VISUAL_EDITOR_MODAL_TITLE' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VISUAL_EDITOR_MODAL_TITLE'),
				'VISUAL_EDITOR_INJECTION_TITLE' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VISUAL_EDITOR_INJECTION_TITLE'),
				'VISUAL_EDITOR_INJECTION_SEARCH' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FIELD_TAG_VISUAL_EDITOR_INJECTION_SEARCH'),
			],
		], JSON_INVALID_UTF8_IGNORE) ?>);
	});
</script>
<script id="avito-editor-template" type="text/html">
	<?php
	ob_start();
	$editor = new CHTMLEditor;
	$editor->Show([
		'id' => 'avitoeditor',
		'width' => '100%',
		'height' => '100%',
		'display' => true,
		'bAllowPhp' => false,
		'showSnippets' => false,
	]);
	$content = ob_get_clean();
	$content = str_replace('</script>', '<\/script>', $content);

	echo $content;
	?>
</script>