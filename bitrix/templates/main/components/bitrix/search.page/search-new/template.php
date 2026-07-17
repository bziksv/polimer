<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$productIds = !empty($arResult['POLIMER_PRODUCT_IDS'])
    ? $arResult['POLIMER_PRODUCT_IDS']
    : array_values(array_filter(array_map('intval', array_column($arResult['SEARCH'] ?? [], 'ID'))));

$hasResults = !empty($productIds);
$rowsCount = (int)($arResult['ROWS_COUNT'] ?? count($productIds));
$pageSize = max(1, (int)($arParams['PAGE_RESULT_COUNT'] ?? 200));
$currentPage = max(1, (int)($_REQUEST['PAGEN_1'] ?? 1));
$pageCount = max(1, (int)ceil($rowsCount / $pageSize));

if ($currentPage > $pageCount)
    $currentPage = $pageCount;

$pageIds = array_slice($productIds, ($currentPage - 1) * $pageSize, $pageSize);
$searchQueryString = http_build_query(['q' => $arResult['REQUEST']['QUERY'] ?? '']);
?>
<style id="polimer-search-layout">
.search-page-layout .ct__content {
    float: none !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
.search-page-layout .product_top {
    width: 100% !important;
    margin-bottom: 30px !important;
}
.search-page-layout .catalog_top {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    width: 100% !important;
    margin: 0 !important;
}
.search-page-layout .catalog_top .item_c {
    float: none !important;
    flex: 0 0 140px !important;
    width: 140px !important;
    margin: 0 !important;
}
@media screen and (max-width: 659px) {
    .search-page-layout .catalog_top {
        justify-content: center !important;
    }
}
</style>

<div class="row cl search-page-layout">

    <div class="ct__content">

        <?if($hasResults):?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?> "<?=htmlspecialcharsbx($arResult['REQUEST']['QUERY'])?>" найдено <?=$rowsCount;?> шт.</div>
        <? else:?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?></div>
        <?endif;?>

        <?if(!empty($arResult['SECTIONS'])):?>
            <?php
            $searchSections = $arResult['SECTIONS'];
            $searchSectionsTotal = (int)($arResult['SECTIONS_COUNT'] ?? count($searchSections));
            $searchSectionsStep = 24;
            ?>
            <div class="h1">Категории<?= $searchSectionsTotal > 0 ? ' — ' . $searchSectionsTotal : '' ?></div>

            <div class="product_top cl search-sections">

                <div class="catalog_top cl" id="search-sections-grid">
                    <?php foreach ($searchSections as $sectionIndex => $arSection): ?>
                        <div class="item_c<?= $sectionIndex >= $searchSectionsStep ? ' search-sections__item--hidden' : '' ?>">
                            <a href="<?=$arSection['SECTION_PAGE_URL']?>">
                                <div class="img_c">
                                    <img src="<?=resizeImage($arSection['PICTURE'], 140, 120);?>" alt="<?=htmlspecialcharsbx($arSection['NAME'])?>">
                                </div>
                                <div class="name_c"><?=htmlspecialcharsbx($arSection['NAME'])?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($searchSectionsTotal > $searchSectionsStep): ?>
                    <div class="search-sections-more">
                        <button type="button"
                            class="search-sections-more__btn"
                            id="search-sections-more-btn"
                            data-step="<?=$searchSectionsStep?>">
                            Показать ещё
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        <? endif; ?>

        <div class="h1">Товары</div>

        <?php
        if ($hasResults && !empty($pageIds))
        {
            $GLOBALS['arrFilter'] = ['ID' => $pageIds];
            $GLOBALS['POLIMER_SEARCH_ID_ORDER'] = $pageIds;

            $APPLICATION->IncludeComponent(
                'bitrix:catalog.section',
                'search',
                [
                    'USE_FILTER' => 'Y',
                    'FILTER_NAME' => 'arrFilter',
                    'IBLOCK_TYPE' => '1c_catalog',
                    'IBLOCK_ID' => IBLOCK_CATALOG,
                    'PRICE_CODE' => ['РОЗНИЦА', 'КРУПНЫЙ_ОПТ', 'СПЕЦЦЕНА', 'ЦЕНА_МОНТАЖНИКА'],
                    'USE_MAIN_ELEMENT_SECTION' => 'Y',
                    'SECTION_USER_FIELDS' => [],
                    'PAGE_ELEMENT_COUNT' => count($pageIds),
                    'DISPLAY_BOTTOM_PAGER' => 'N',
                    'DISPLAY_TOP_PAGER' => 'N',
                    'CACHE_TYPE' => 'N',
                    'CACHE_TIME' => '0',
                ],
                false
            );

            unset($GLOBALS['POLIMER_SEARCH_ID_ORDER']);

            if (($arParams['DISPLAY_BOTTOM_PAGER'] ?? 'Y') === 'Y')
            {
                echo '<div class="products_roll"><div class="pr_footer cl">';
                echo polimerBuildSearchPageNav($currentPage, $pageSize, $rowsCount, $searchQueryString);
                echo '</div></div>';
            }
        }
        elseif (!$hasResults)
        {
            ShowNote(GetMessage('SEARCH_NOTHING_TO_FOUND'));
        }
        ?>
    </div>
    <div class="ct__mask"></div>
</div>
<?php if (!empty($arResult['SECTIONS']) && ($arResult['SECTIONS_COUNT'] ?? 0) > 24): ?>
<script>
(function () {
    var btn = document.getElementById('search-sections-more-btn');
    var grid = document.getElementById('search-sections-grid');
    if (!btn || !grid) return;

    btn.addEventListener('click', function () {
        var step = parseInt(btn.getAttribute('data-step') || '24', 10);
        var hidden = grid.querySelectorAll('.search-sections__item--hidden');
        var shown = 0;

        for (var i = 0; i < hidden.length && shown < step; i++) {
            hidden[i].classList.remove('search-sections__item--hidden');
            shown++;
        }

        if (!grid.querySelector('.search-sections__item--hidden'))
            btn.parentNode.style.display = 'none';
    });
})();
</script>
<?php endif; ?>
