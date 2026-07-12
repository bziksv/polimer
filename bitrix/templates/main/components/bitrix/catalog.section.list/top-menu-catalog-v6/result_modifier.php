<?php

foreach ($arResult['SECTIONS'] as $key => $section) {

		if ($section['DEPTH_LEVEL'] == 1) {
			$one = $key;
			$i = 0;
		}

		if ($section['DEPTH_LEVEL'] == 2) {
			$two = $i;
			$arResult['SECTIONS'][$one]['SECTION_1'][] = $section;
			$i++;
			unset($arResult['SECTIONS'][$key]);
		}

		if ($section['DEPTH_LEVEL'] == 3) {
			$arResult['SECTIONS'][$one]['SECTION_1'][$two]['SECTION_2'][] = $section;
			unset($arResult['SECTIONS'][$key]);
		}

}

if (!function_exists('sortSectionsByName')) {
function sortSectionsByName(&$sections) {
    usort($sections, function($a, $b) {
        return strcasecmp($a['NAME'], $b['NAME']);
    });
    foreach ($sections as &$section) {
        if (isset($section['SECTION_1']) && is_array($section['SECTION_1'])) {
            sortSectionsByName($section['SECTION_1']);
        }
        if (isset($section['SECTION_2']) && is_array($section['SECTION_2'])) {
            sortSectionsByName($section['SECTION_2']);
        }
    }
}
}

sortSectionsByName($arResult['SECTIONS']);

if (function_exists('polimerMenuAttachSectionImages')) {
    polimerMenuAttachSectionImages(
        $arResult['SECTIONS'],
        (int)($arParams['IBLOCK_ID'] ?? 21)
    );
}
