<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

/** @var $this \CBitrixComponentTemplate */
/** @var array $arResult */
/** @var array $arParams */

Extension::load([
	'avitoexport.vendor.select2',
]);

$iblockId = (int)$arParams['IBLOCK_ID'];
$htmlId = 'avito-export-filter-' . $iblockId;
$filters = is_array($arParams['VALUE']) ? $arParams['VALUE'] : [];

if (empty($filters)) { $filters[] = []; }

// lang used by select2
?>
<div id="<?= $htmlId ?>" lang="<?= LANGUAGE_ID ?>">
    <?php
    $filterIndex = 0;

    foreach ($filters as $filter)
	{
		if (empty($filter))
		{
			$filter[] = [
				'FIELD' => null,
				'COMPARE' => null,
				'VALUE' => null,
			];
		}

	    ?>
	    <div class="avito-filter-container" data-entity="filter">
	        <button type="button" class="avito-filter-container-delete <?= count($filters) <= 1 ? 'avito--hidden' : ''?>" data-entity="deleteFilter"></button>
	        <?php
	        $conditionIndex = 0;
	        $iblockValueKeys = array_keys($filter);

	        foreach ($iblockValueKeys as $valueIndex => $valueKey)
	        {
	            $value = $filter[$valueKey];
	            $nextValueKey = $iblockValueKeys[$valueIndex + 1] ?? null;
	            $nextValue = $nextValueKey !== null ? $filter[$nextValueKey] : null;
	            $nextGlue = ($nextValue['GLUE'] ?? 'AND');

	            if (isset($value['SOURCE'], $value['ENTITY']))
	            {
	                $value['FIELD'] = $value['SOURCE'] . '.' . $value['ENTITY'];
	            }

	            $name = sprintf('%s[%s][%s]', $arParams['NAME'], $filterIndex, $conditionIndex);
	            $selectedField = null;
	            $selectedCompare = null;
	            $glue = ($value['GLUE'] ?? 'AND');

	            if ($conditionIndex > 0)
	            {
	                ?>
	                <button class="avito-filter-junction <?= $glue === 'OR' ? 'is--active' : '' ?>" type="button" data-entity="junction">
	                    <?= $glue === 'OR' ? Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_JUNCTION_OR') : Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_JUNCTION_AND') ?>
	                </button>
	                <?php
	            }

	            ?>
	            <div class="avito-filter-row level--<?= $glue === 'OR' || $nextGlue === 'OR' ? 1 : 0 ?>" data-entity="row">
	                <input type="hidden" name="<?= $name ?>[GLUE]" value="<?= htmlspecialcharsbx($glue) ?>" data-entity="glue" />
	                <div class="avito-filter-column for--entity">
	                    <label class="avito-filter-label"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_FIELD') ?></label>
	                    <?php
	                    include __DIR__ . '/partials/control-field.php';
	                    ?>
	                </div>
	                <div class="avito-filter-column for--compare">
	                    <label class="avito-filter-label"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_COMPARE') ?></label>
	                    <?php
	                    include __DIR__ . '/partials/control-compare.php';
	                    ?>
	                </div>
	                <div class="avito-filter-column for--value">
	                    <label class="avito-filter-label"><?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_VALUE') ?></label>
	                    <?php
	                    include __DIR__ . '/partials/control-value.php';
	                    ?>
	                </div>
	                <button type="button" class="avito-row-delete <?= count($filter) <= 1 ? 'avito--hidden' : ''?>" data-entity="delete"></button>
	            </div>
	            <?php

	            ++$conditionIndex;
	        }
	        ?>
	        <button type="button" class="avito-filter-add adm-btn" data-entity="addButton">
	            <?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_ADD') ?>
	        </button>
	    </div>
	    <?php
        ++$filterIndex;
    }
    ?>
    <a class="avito-filter-container-add" data-entity="addFilter">
        <?= Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_ADD_FILTER') ?>
    </a>
</div>
<script>
	BX.ready(function() {
		const container = document.getElementById('<?= $htmlId ?>');

        new BX.AvitoExport.Feed.FilterCollection(container, <?= Json::encode([
            'component' => $this->getComponent()->getName(),
            'signedParameters' => $this->getComponent()->getSignedParameters(),
            'fields' => $arResult['FIELDS'],
            'baseName' => $arParams['NAME'],
            'lang' => [
                'JUNCTION_AND' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_JUNCTION_AND'),
                'JUNCTION_OR' => Loc::getMessage('AVITO_EXPORT_T_ADMIN_FEED_FILTER_JUNCTION_OR'),
            ],
        ], JSON_INVALID_UTF8_IGNORE) ?>);
	});
</script>