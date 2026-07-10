<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

polimerEnhanceSearchPageResult($arResult, $arParams);

$query = trim((string)($arResult['REQUEST']['~QUERY'] ?? $arResult['REQUEST']['QUERY'] ?? ''));
$arResult['SECTIONS'] = [];

if ($query !== '')
{
    $arResult['SECTIONS'] = polimerSearchCatalogSections($query, IBLOCK_CATALOG, 500);
}

$arResult['SECTIONS_COUNT'] = count($arResult['SECTIONS']);
