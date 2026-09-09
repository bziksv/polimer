<?php

$curPage = $APPLICATION->GetCurPage().'?'.$arParams["ACTION_VARIABLE"].'=';
$arUrls = array(
    "delete" => $curPage."delete&id=#ID#",
    "delay" => $curPage."delay&id=#ID#",
    "add" => $curPage."add&id=#ID#",
);
unset($curPage);

$arEmptyPreview = false;
$strEmptyPreview = $this->GetFolder().'/images/no_photo.png';
if (file_exists($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview))
{
    $arSizes = getimagesize($_SERVER['DOCUMENT_ROOT'].$strEmptyPreview);
    if (!empty($arSizes))
    {
        $arEmptyPreview = array(
            'SRC' => $strEmptyPreview,
            'WIDTH' => (int)$arSizes[0],
            'HEIGHT' => (int)$arSizes[1]
        );
    }
    unset($arSizes);
}
unset($strEmptyPreview);

/**
 * Число из цены Bitrix (в т.ч. с пробелами/nbsp).
 */
$polimerBasketPrice = static function ($value): float {
    if (is_int($value) || is_float($value)) {
        return (float)$value;
    }
    $raw = str_replace(["\xC2\xA0", ' ', ','], ['', '', '.'], (string)$value);
    $raw = preg_replace('/[^\d.\-]/', '', $raw) ?? '';

    return $raw !== '' && $raw !== '-' && $raw !== '.' ? (float)$raw : 0.0;
};

$precent = [];
$_SESSION['DISCOUNT_PRICE_ALL_FORMATED'] = $arResult["allSum"];
foreach ($arResult["GRID"]["ROWS"] as &$row) {
    if (!$row['PREVIEW_PICTURE_SRC']) {
        $row['PREVIEW_PICTURE_SRC'] = $arEmptyPreview['SRC'];
    }

    // Bitrix: (int)24.999 → 24%. В карточке round() → 25%. Выравниваем.
    $fullPrice = $polimerBasketPrice($row['FULL_PRICE'] ?? ($row['BASE_PRICE'] ?? 0));
    $discountPrice = $polimerBasketPrice($row['DISCOUNT_PRICE'] ?? 0);
    if ($discountPrice <= 0 && $fullPrice > 0) {
        $price = $polimerBasketPrice($row['PRICE'] ?? 0);
        if ($price > 0 && $price < $fullPrice) {
            $discountPrice = $fullPrice - $price;
        }
    }
    if ($fullPrice > 0 && $discountPrice > 0) {
        $pct = (int)round(($discountPrice / $fullPrice) * 100);
        if ($pct < 0) {
            $pct = 0;
        }
        if ($pct > 100) {
            $pct = 100;
        }
        $row['DISCOUNT_PRICE_PERCENT'] = $pct;
        $row['DISCOUNT_PRICE_PERCENT_FORMATED'] = $pct . '%';
    }

    $precent[] = $row['DISCOUNT_PRICE_PERCENT_FORMATED'];
}
unset($row);
$_SESSION['DISCOUNT_PRICE_PERCENT_FORMATED'] = implode(',', $precent);
