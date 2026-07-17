<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

polimerEnhanceSearchPageResult($arResult, $arParams);

$query = trim((string)($arResult['REQUEST']['~QUERY'] ?? $arResult['REQUEST']['QUERY'] ?? ''));
$productIds = !empty($arResult['POLIMER_PRODUCT_IDS'])
	? array_values(array_map('intval', $arResult['POLIMER_PRODUCT_IDS']))
	: [];

$arResult['SECTIONS'] = [];
$arResult['SELECTED_SECTION_IDS'] = [];
$arResult['POLIMER_PRODUCT_SECTION_MAP'] = [];
$arResult['POLIMER_ALL_PRODUCT_IDS'] = $productIds;
$arResult['ROWS_COUNT_ALL'] = count($productIds);

$noPhoto = '/bitrix/templates/main/img/no_photo.png';

if ($query !== '' && !empty($productIds))
{
	$sectionMap = polimerMapProductIdsToSectionIds($productIds, IBLOCK_CATALOG);
	$arResult['POLIMER_PRODUCT_SECTION_MAP'] = $sectionMap;

	$productsForSections = [];
	foreach ($sectionMap as $sectionId)
	{
		$sectionId = (int)$sectionId;
		if ($sectionId > 0)
			$productsForSections[] = ['SECTION_ID' => $sectionId];
	}

	$arResult['SECTIONS'] = polimerBuildSearchSectionsFromProducts($productsForSections, $noPhoto, 96);

	// Если по товарам секций нет — fallback: разделы по совпадению имени
	if (empty($arResult['SECTIONS']))
	{
		$fallback = polimerSearchCatalogSections($query, IBLOCK_CATALOG, 500);
		foreach ($fallback as &$section)
		{
			$section['URL'] = $section['SECTION_PAGE_URL'] ?? ($section['URL'] ?? '#');
			$section['COUNT'] = (int)($section['ELEMENT_CNT'] ?? $section['COUNT'] ?? 0);
			$picture = $noPhoto;
			if (!empty($section['PICTURE']))
			{
				$resized = CFile::ResizeImageGet(
					$section['PICTURE'],
					['width' => 96, 'height' => 96],
					BX_RESIZE_IMAGE_EXACT,
					true
				);
				if (!empty($resized['src']))
					$picture = $resized['src'];
			}
			$section['PICTURE'] = $picture;
		}
		unset($section);
		$arResult['SECTIONS'] = $fallback;
	}
	else
	{
		usort($arResult['SECTIONS'], static function ($a, $b) use ($query) {
			$scoreA = polimerScoreSearchNameRelevance($a['NAME'] ?? '', $query);
			$scoreB = polimerScoreSearchNameRelevance($b['NAME'] ?? '', $query);
			if ($scoreA !== $scoreB)
				return $scoreA <=> $scoreB;

			if (($a['COUNT'] ?? 0) !== ($b['COUNT'] ?? 0))
				return ($b['COUNT'] ?? 0) <=> ($a['COUNT'] ?? 0);

			return strcmp((string)($a['NAME'] ?? ''), (string)($b['NAME'] ?? ''));
		});
	}

	$selectedSectionIds = polimerParseSearchSectionFilterIds();
	$knownSectionIds = array_column($arResult['SECTIONS'], 'ID');
	$knownFlip = array_fill_keys(array_map('intval', $knownSectionIds), true);
	$selectedSectionIds = array_values(array_filter($selectedSectionIds, static function ($id) use ($knownFlip) {
		return isset($knownFlip[(int)$id]);
	}));
	$arResult['SELECTED_SECTION_IDS'] = $selectedSectionIds;

	if (!empty($selectedSectionIds))
	{
		$selectedFlip = array_fill_keys($selectedSectionIds, true);
		$filteredIds = [];
		foreach ($productIds as $productId)
		{
			$sectionId = (int)($sectionMap[$productId] ?? 0);
			if (isset($selectedFlip[$sectionId]))
				$filteredIds[] = $productId;
		}

		$arResult['POLIMER_PRODUCT_IDS'] = $filteredIds;
		$arResult['ROWS_COUNT'] = count($filteredIds);
		$arResult['SEARCH'] = array_map(static function ($id) {
			return [
				'ITEM_ID' => $id,
				'ID' => $id,
				'PARAM2' => IBLOCK_CATALOG,
			];
		}, $filteredIds);
	}
}
elseif ($query !== '')
{
	$arResult['SECTIONS'] = polimerSearchCatalogSections($query, IBLOCK_CATALOG, 500);
}

$arResult['SECTIONS_COUNT'] = count($arResult['SECTIONS']);
