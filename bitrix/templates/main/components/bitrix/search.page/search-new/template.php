<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$productIds = !empty($arResult['POLIMER_PRODUCT_IDS'])
    ? $arResult['POLIMER_PRODUCT_IDS']
    : array_values(array_filter(array_map('intval', array_column($arResult['SEARCH'] ?? [], 'ID'))));

$hasResults = !empty($productIds);
$rowsCount = (int)($arResult['ROWS_COUNT'] ?? count($productIds));
$rowsCountAll = (int)($arResult['ROWS_COUNT_ALL'] ?? $rowsCount);
$pageSize = max(1, (int)($arParams['PAGE_RESULT_COUNT'] ?? 200));
$currentPage = max(1, (int)($_REQUEST['PAGEN_1'] ?? 1));
$pageCount = max(1, (int)ceil($rowsCount / $pageSize));

if ($currentPage > $pageCount)
    $currentPage = $pageCount;

$pageIds = array_slice($productIds, ($currentPage - 1) * $pageSize, $pageSize);

$searchQuery = (string)($arResult['REQUEST']['QUERY'] ?? '');
$selectedSectionIds = array_values(array_map('intval', $arResult['SELECTED_SECTION_IDS'] ?? []));
$selectedFlip = array_fill_keys($selectedSectionIds, true);

$buildSearchUrl = static function (array $sectionIds, $page = 1) use ($searchQuery) {
    $params = ['q' => $searchQuery, 's' => 'Поиск'];
    $sectionIds = array_values(array_filter(array_map('intval', $sectionIds)));
    if (!empty($sectionIds))
        $params['sid'] = implode(',', $sectionIds);
    if ((int)$page > 1)
        $params['PAGEN_1'] = (int)$page;

    return '/search/?' . http_build_query($params);
};

$searchQueryString = ltrim(parse_url($buildSearchUrl($selectedSectionIds, 1), PHP_URL_QUERY) ?: '', '?');
$clearFilterUrl = $buildSearchUrl([]);
$searchSections = $arResult['SECTIONS'] ?? [];
$searchSectionsTotal = (int)($arResult['SECTIONS_COUNT'] ?? count($searchSections));
$searchSectionsStep = 12;
$selectedSectionNames = [];
foreach ($searchSections as $section)
{
    if (isset($selectedFlip[(int)$section['ID']]))
        $selectedSectionNames[] = (string)$section['NAME'];
}
?>
<style id="polimer-search-layout">
.search-page-layout .ct__content {
    float: none !important;
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
</style>

<div class="row cl search-page-layout">

    <div class="ct__content">

        <?if($hasResults || $rowsCountAll > 0):?>
            <div class="h1">
                <? $APPLICATION->ShowTitle(false, false); ?>
                «<?=htmlspecialcharsbx($searchQuery)?>»
                — найдено <?=$rowsCount;?> шт.<?if(!empty($selectedSectionIds) && $rowsCountAll > $rowsCount):?>
                <span class="search-page-total-hint">из <?=$rowsCountAll?></span>
                <?endif;?>
            </div>
        <? else:?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?></div>
        <?endif;?>

        <?if(!empty($searchSections)):?>
            <section class="search-cat-filter" aria-label="Фильтр по категориям">
                <div class="search-cat-filter__head">
                    <div class="search-cat-filter__title-row">
                        <h2 class="search-cat-filter__title">Категории</h2>
                        <span class="search-cat-filter__count"><?=$searchSectionsTotal?></span>
                    </div>
                    <p class="search-cat-filter__hint">
                        Нажмите на карточку — отфильтровать товары · стрелка — перейти в раздел каталога
                    </p>
                </div>

                <?if(!empty($selectedSectionIds)):?>
                <div class="search-cat-filter__chips">
                    <?foreach ($selectedSectionNames as $selectedName):?>
                        <span class="search-cat-filter__chip"><?=htmlspecialcharsbx($selectedName)?></span>
                    <?endforeach;?>
                    <a class="search-cat-filter__reset" href="<?=htmlspecialcharsbx($clearFilterUrl)?>">Сбросить фильтр</a>
                </div>
                <?endif;?>

                <div class="search-cat-filter__grid" id="search-sections-grid">
                    <?php foreach ($searchSections as $sectionIndex => $arSection):
                        $sectionId = (int)$arSection['ID'];
                        $isActive = isset($selectedFlip[$sectionId]);
                        $nextIds = $selectedSectionIds;
                        if ($isActive)
                            $nextIds = array_values(array_filter($nextIds, static function ($id) use ($sectionId) {
                                return (int)$id !== $sectionId;
                            }));
                        else
                            $nextIds[] = $sectionId;

                        $filterUrl = $buildSearchUrl($nextIds);
                        $sectionName = (string)$arSection['NAME'];
                        $sectionCount = (int)($arSection['COUNT'] ?? 0);
                        $sectionUrl = (string)($arSection['URL'] ?? $arSection['SECTION_PAGE_URL'] ?? '#');
                        $sectionPicture = (string)($arSection['PICTURE'] ?? '/bitrix/templates/main/img/no_photo.png');
                        $hiddenClass = $sectionIndex >= $searchSectionsStep ? ' search-cat-card--hidden' : '';
                    ?>
                    <div class="search-cat-card<?= $isActive ? ' is-active' : '' ?><?=$hiddenClass?>">
                        <a class="search-cat-card__filter"
                            href="<?=htmlspecialcharsbx($filterUrl)?>"
                            title="<?= $isActive ? 'Убрать фильтр' : 'Показать товары из «' . htmlspecialcharsbx($sectionName) . '»' ?>">
                            <span class="search-cat-card__check" aria-hidden="true"><i class="fa fa-check"></i></span>
                            <span class="search-cat-card__thumb">
                                <img src="<?=htmlspecialcharsbx($sectionPicture)?>" alt="" width="72" height="72" loading="lazy">
                            </span>
                            <span class="search-cat-card__info">
                                <span class="search-cat-card__name"><?=htmlspecialcharsbx($sectionName)?></span>
                                <span class="search-cat-card__meta"><?=$sectionCount?> шт. по запросу</span>
                            </span>
                        </a>
                        <a class="search-cat-card__go"
                            href="<?=htmlspecialcharsbx($sectionUrl)?>"
                            title="Перейти в раздел «<?=htmlspecialcharsbx($sectionName)?>»">
                            <i class="fa fa-external-link" aria-hidden="true"></i>
                            <span class="search-cat-card__go-text">В раздел</span>
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
                            Показать ещё категории
                        </button>
                    </div>
                <?php endif; ?>
            </section>
        <? endif; ?>

        <div class="h1">Товары<?if(!empty($selectedSectionNames)):?>
            <span class="search-page-filter-label">· <?=htmlspecialcharsbx(implode(', ', $selectedSectionNames))?></span>
        <?endif;?></div>

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
        elseif (!empty($selectedSectionIds) && $rowsCountAll > 0)
        {
            ShowNote('В выбранных категориях нет товаров по этому запросу. <a href="' . htmlspecialcharsbx($clearFilterUrl) . '">Сбросить фильтр</a>');
        }
        elseif (!$hasResults)
        {
            ShowNote(GetMessage('SEARCH_NOTHING_TO_FOUND'));
        }
        ?>
    </div>
    <div class="ct__mask"></div>
</div>
<?php if ($searchSectionsTotal > $searchSectionsStep): ?>
<script>
(function () {
    var btn = document.getElementById('search-sections-more-btn');
    var grid = document.getElementById('search-sections-grid');
    if (!btn || !grid) return;

    btn.addEventListener('click', function () {
        var step = parseInt(btn.getAttribute('data-step') || '12', 10);
        var hidden = grid.querySelectorAll('.search-cat-card--hidden');
        var shown = 0;

        for (var i = 0; i < hidden.length && shown < step; i++) {
            hidden[i].classList.remove('search-cat-card--hidden');
            shown++;
        }

        if (!grid.querySelector('.search-cat-card--hidden'))
            btn.parentNode.style.display = 'none';
    });
})();
</script>
<?php endif; ?>
