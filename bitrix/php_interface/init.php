<?php
define("IBLOCK_CATALOG", 21);

function inCompare($IBLOCK_ID, $ID)
{
    return isset($_SESSION["CATALOG_COMPARE_LIST"][$IBLOCK_ID]["ITEMS"][$ID]);
}

function price($id){
    $ar_res_price = CPrice::GetBasePrice($id, false, false);
    if($ar_res_price['PRICE']){
        return $ar_res_price['PRICE'];
    }else{
        return false;
    }

}
function priceDiscount($id){
    global $USER;
    $ar_res_price = CCatalogProduct::GetOptimalPrice($id, 1, $USER->GetUserGroupArray(), 'N');
    if($ar_res_price['DISCOUNT_PRICE']){
        return $ar_res_price['DISCOUNT_PRICE'];
    }else{
        return false;
    }
}

function checkProduct($id){
    $ar_res = CCatalogProduct::GetByID($id);
    if($ar_res['QUANTITY'] > 0 && (float)price($id))
        return true;

    return false;
}

function productMeasureUnit($productId, array $properties = [])
{
    $productId = (int)$productId;
    if ($productId <= 0)
        return 'шт';

    if (!empty($properties['CML2_BASE_UNIT']['VALUE']))
        return trim((string)$properties['CML2_BASE_UNIT']['VALUE']);

    if (!CModule::IncludeModule('catalog'))
        return 'шт';

    $product = CCatalogProduct::GetByID($productId);
    if (empty($product['MEASURE']))
        return 'шт';

    $measure = CCatalogMeasure::getList([], ['ID' => (int)$product['MEASURE']])->Fetch();
    if ($measure)
    {
        $unit = trim((string)($measure['SYMBOL_RUS'] ?: $measure['MEASURE_TITLE'] ?: ''));
        if ($unit !== '')
            return $unit;
    }

    return 'шт';
}

function polimerGetProductAvailability($id)
{
    $id = (int)$id;
    if ($id <= 0)
        return 'unavailable';

    if (checkProduct($id))
        return 'available';

    if ((float)price($id) > 0)
        return 'order';

    return 'unavailable';
}

function polimerGetSearchProductSortPrice(array $productItem)
{
    if (array_key_exists('PRICE_SORT', $productItem) && $productItem['PRICE_SORT'] !== null)
        return (float)$productItem['PRICE_SORT'];

    $productId = (int)($productItem['ELEMENT_ID'] ?? $productItem['ITEM_ID'] ?? 0);
    if ($productId <= 0)
        return PHP_FLOAT_MAX;

    $productPrice = price($productId);

    return $productPrice ? (float)$productPrice : PHP_FLOAT_MAX;
}

function polimerSortSearchProductsByAvailabilityAndPrice(array $products)
{
    $availableProducts = [];
    $orderProducts = [];
    $unavailableProducts = [];

    foreach ($products as $productItem)
    {
        $stockStatus = $productItem['STOCK_STATUS'] ?? 'unavailable';

        if ($stockStatus === 'available')
            $availableProducts[] = $productItem;
        elseif ($stockStatus === 'order')
            $orderProducts[] = $productItem;
        else
            $unavailableProducts[] = $productItem;
    }

    $sortByPrice = static function (array $left, array $right): int {
        $priceCompare = polimerGetSearchProductSortPrice($left) <=> polimerGetSearchProductSortPrice($right);

        if ($priceCompare !== 0)
            return $priceCompare;

        return strcmp((string)($left['NAME'] ?? ''), (string)($right['NAME'] ?? ''));
    };

    usort($availableProducts, $sortByPrice);
    usort($orderProducts, $sortByPrice);
    usort($unavailableProducts, $sortByPrice);

    return array_merge($availableProducts, $orderProducts, $unavailableProducts);
}

function polimerNormalizePropValue($value)
{
    if (is_array($value))
    {
        $value = array_filter($value, static function ($item) {
            return $item !== '' && $item !== null;
        });
        sort($value);

        return implode('|', $value);
    }

    return trim((string)$value);
}

function polimerGetProductPropMatchValue(array $prop)
{
    if (!empty($prop['VALUE_ENUM_ID']))
        return (string)$prop['VALUE_ENUM_ID'];

    return polimerNormalizePropValue($prop['VALUE'] ?? '');
}

function polimerGetSimilarSearchSectionId($sectionId)
{
    $sectionId = (int)$sectionId;
    if ($sectionId <= 0)
        return 0;

    $section = CIBlockSection::GetByID($sectionId)->Fetch();
    if (!$section)
        return $sectionId;

    if ((int)$section['IBLOCK_SECTION_ID'] > 0)
        return (int)$section['IBLOCK_SECTION_ID'];

    return $sectionId;
}

function polimerGetSectionSmartFilterCodes($iblockId, $sectionId, $limit = 3)
{
    if (!CModule::IncludeModule('iblock'))
        return [];

    $iblockId = (int)$iblockId;
    $sectionId = (int)$sectionId;
    $limit = max(1, (int)$limit);
    $checkedSections = [];

    while ($sectionId > 0 && count($checkedSections) < 5)
    {
        if (in_array($sectionId, $checkedSections, true))
            break;

        $checkedSections[] = $sectionId;

        $res = \Bitrix\Iblock\SectionPropertyTable::getList([
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $sectionId,
                'SMART_FILTER' => 'Y',
            ],
            'select' => [
                'PROPERTY_ID',
                'PROPERTY_CODE' => 'PROPERTY.CODE',
                'PROPERTY_SORT' => 'PROPERTY.SORT',
            ],
            'order' => [
                'PROPERTY_SORT' => 'ASC',
                'PROPERTY_ID' => 'ASC',
            ],
            'limit' => $limit,
        ]);

        $codes = [];
        while ($row = $res->fetch())
        {
            if (!empty($row['PROPERTY_CODE']))
                $codes[] = $row['PROPERTY_CODE'];
        }

        if (!empty($codes))
            return array_values(array_unique($codes));

        $section = CIBlockSection::GetByID($sectionId)->Fetch();
        $sectionId = $section ? (int)$section['IBLOCK_SECTION_ID'] : 0;
    }

    return [];
}

function polimerFetchSectionProductsForSimilar($iblockId, $sectionId, $excludeId, array $propCodes, $limit = 150)
{
    $items = [];
    $propCodes = array_values(array_filter(array_unique($propCodes)));

    $arSelect = ['ID', 'IBLOCK_ID', 'NAME', 'SORT'];
    $arFilter = [
        'IBLOCK_ID' => (int)$iblockId,
        'SECTION_ID' => (int)$sectionId,
        'INCLUDE_SUBSECTIONS' => 'Y',
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        '!ID' => (int)$excludeId,
    ];

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        $arFilter,
        false,
        ['nTopCount' => max(20, (int)$limit)],
        $arSelect
    );

    while ($ob = $res->GetNextElement())
    {
        $fields = $ob->GetFields();
        $props = $ob->GetProperties();

        $propValues = [];
        foreach ($propCodes as $code)
        {
            if (!empty($props[$code]))
                $propValues[$code] = polimerGetProductPropMatchValue($props[$code]);
        }

        $items[] = [
            'ID' => (int)$fields['ID'],
            'PROPS' => $propValues,
        ];
    }

    return $items;
}

function polimerGetSimilarProductIds(array $product, $limit = 10)
{
    $limit = max(1, (int)$limit);
    $productId = (int)($product['ID'] ?? 0);
    $iblockId = (int)($product['IBLOCK_ID'] ?? 0);
    $sectionId = (int)($product['IBLOCK_SECTION_ID'] ?? 0);

    if ($productId <= 0 || $iblockId <= 0 || $sectionId <= 0)
        return [];

    if (!CModule::IncludeModule('iblock'))
        return [];

    $cache = \Bitrix\Main\Data\Cache::createInstance();
    $cacheKey = 'similar_v1_' . $productId . '_' . $limit;
    $cacheDir = '/polimer/similar_products';

    if ($cache->initCache(3600, $cacheKey, $cacheDir))
        return $cache->getVars();

    $cacheStarted = $cache->startDataCache();

    $searchSectionId = polimerGetSimilarSearchSectionId($sectionId);
    $propCodes = polimerGetSectionSmartFilterCodes($iblockId, $sectionId, 3);

    $matchProps = [];
    foreach ($propCodes as $code)
    {
        if (empty($product['PROPERTIES'][$code]))
            continue;

        $value = polimerGetProductPropMatchValue($product['PROPERTIES'][$code]);
        if ($value !== '')
            $matchProps[$code] = $value;
    }

    $candidates = polimerFetchSectionProductsForSimilar(
        $iblockId,
        $searchSectionId,
        $productId,
        $propCodes,
        150
    );

    $exact = [];
    $partial = [];
    $rest = [];
    $matchCount = count($matchProps);

    foreach ($candidates as $candidate)
    {
        if ((int)$candidate['ID'] === $productId)
            continue;

        if ($matchCount === 0)
        {
            $rest[] = (int)$candidate['ID'];
            continue;
        }

        $score = 0;
        foreach ($matchProps as $code => $value)
        {
            if (($candidate['PROPS'][$code] ?? '') === $value)
                $score++;
        }

        if ($score === $matchCount)
            $exact[] = (int)$candidate['ID'];
        elseif ($score > 0)
            $partial[] = ['ID' => (int)$candidate['ID'], 'SCORE' => $score];
        else
            $rest[] = (int)$candidate['ID'];
    }

    usort($partial, static function ($a, $b) {
        return $b['SCORE'] <=> $a['SCORE'];
    });

    $partialIds = array_column($partial, 'ID');
    $result = array_values(array_unique(array_merge($exact, $partialIds, $rest)));
    $result = array_slice($result, 0, $limit);

    if ($cacheStarted)
        $cache->endDataCache($result);

    return $result;
}

function polimerFormatPropertyDisplayValue(array $prop)
{
    if (empty($prop))
        return '';

    $value = $prop['DISPLAY_VALUE'] ?? $prop['VALUE'] ?? '';
    if (is_array($value))
        $value = implode(', ', array_filter($value, static function ($item) {
            return $item !== '' && $item !== null;
        }));

    return trim(strip_tags((string)$value));
}

function polimerShortPropertyName($name)
{
    $name = trim((string)$name);
    if ($name === '')
        return '';

    $lower = mb_strtolower($name);
    $map = [
        'мощност' => 'Мощность',
        'контур' => 'Контурность',
        'объ' => 'Объём',
        'вес' => 'Вес',
        'напряж' => 'Напряжение',
        'диаметр' => 'Диаметр',
        'напор' => 'Напор',
        'подач' => 'Подача',
        'тип насос' => 'Тип',
        'вид насос' => 'Вид',
    ];

    foreach ($map as $needle => $label)
    {
        if (mb_strpos($lower, $needle) !== false)
            return $label;
    }

    return $name;
}

function polimerIsPowerInWatts($code, $nameLower)
{
    $code = mb_strtoupper((string)$code);

    if (preg_match('/_KVT|_KW($|_)/i', $code))
        return false;

    if (preg_match('/_VT($|_)|MOSHCHNOST_VT/i', $code))
        return true;

    if (mb_strpos($nameLower, 'мощност') !== false)
    {
        if (mb_strpos($nameLower, 'квт') !== false || mb_strpos($nameLower, 'kw') !== false)
            return false;

        if (mb_strpos($nameLower, 'вт') !== false || mb_strpos($nameLower, 'w') !== false)
            return true;
    }

    return false;
}

function polimerFormatPowerNumeric($value, $inWatts = false)
{
    $normalized = str_replace(',', '.', preg_replace('/[^\d,\.]/u', '', (string)$value));
    if ($normalized === '' || !is_numeric($normalized))
        return trim((string)$value) . ($inWatts ? ' Вт' : ' кВт');

    $num = (float)$normalized;

    if ($inWatts)
    {
        if ($num >= 1000)
        {
            $kwt = $num / 1000;
            $formatted = rtrim(rtrim(number_format($kwt, 1, ',', ''), '0'), ',');

            return $formatted . ' кВт';
        }

        $formatted = rtrim(rtrim(number_format($num, 0, ',', ''), '0'), ',');

        return $formatted . ' Вт';
    }

    $formatted = rtrim(rtrim(number_format($num, 2, ',', ''), '0'), ',');

    return $formatted . ' кВт';
}

function polimerFormatSearchSpecPart(array $prop)
{
    $value = polimerFormatPropertyDisplayValue($prop);
    if ($value === '')
        return '';

    $code = mb_strtoupper((string)($prop['CODE'] ?? ''));
    $name = trim((string)($prop['NAME'] ?? ''));
    $nameLower = mb_strtolower($name);

    if (preg_match('/\s(квт|kw|кг|kg|л|l|мм|mm|м³|м3|bar|бар|в|w)\b/ui', $value))
        return $value;

    if (preg_match('/MOSHCH|MOHNOST|_KVT|_VT|_KW$/i', $code) || mb_strpos($nameLower, 'мощност') !== false)
    {
        if (preg_match('/^[\d\s,\.]+$/u', $value))
            return polimerFormatPowerNumeric($value, polimerIsPowerInWatts($code, $nameLower));
    }

    if (preg_match('/NAPOR|PODACH|PROIZVODITEL/i', $code) || mb_strpos($nameLower, 'напор') !== false || mb_strpos($nameLower, 'подач') !== false)
    {
        if (preg_match('/^[\d\s,\.]+$/u', $value))
        {
            if (mb_strpos($nameLower, 'напор') !== false || preg_match('/NAPOR/i', $code))
                return rtrim(str_replace('.', ',', $value)) . ' м';

            if (mb_strpos($nameLower, 'подач') !== false || preg_match('/PODACH|PROIZVODITEL/i', $code))
                return rtrim(str_replace('.', ',', $value)) . ' л/мин';
        }
    }

    if (preg_match('/VES|MASS|WEIGHT/i', $code) || mb_strpos($nameLower, 'вес') !== false)
    {
        if (preg_match('/^[\d\s,\.]+$/u', $value))
            return 'Вес: ' . $value . ' кг';
    }

    if (preg_match('/OBEM|EMKOST|VOLUME/i', $code) || mb_strpos($nameLower, 'объ') !== false)
    {
        if (preg_match('/^[\d\s,\.]+$/u', $value))
            return $value . ' л';
    }

    if (!preg_match('/^[\d\s,\.]+$/u', $value))
        return $value;

    $shortName = polimerShortPropertyName($name);
    if ($shortName !== '')
        return $shortName . ': ' . $value;

    return $value;
}

function polimerGetSectionSearchableCodes($iblockId, $limit = 3)
{
    if (!CModule::IncludeModule('iblock'))
        return [];

    $codes = [];
    $res = \Bitrix\Iblock\PropertyTable::getList([
        'filter' => [
            'IBLOCK_ID' => (int)$iblockId,
            'ACTIVE' => 'Y',
            'SEARCHABLE' => 'Y',
        ],
        'select' => ['CODE'],
        'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        'limit' => max(1, (int)$limit),
    ]);

    while ($row = $res->fetch())
    {
        if (!empty($row['CODE']))
            $codes[] = $row['CODE'];
    }

    return $codes;
}

function polimerGetSearchSpecFallbackCodes()
{
    return [
        'TIP_KOTLA',
        'KONTOURNOST',
        'KOLICHESTVO_KONTOUROV',
        'VOZMOZHNOE_PODKLYUCHENIE',
        'TIP_NASOSOV',
        'VID_NASOSOV',
        'NAPOR_M_VOD_ST_',
        'MOSHCHNOST_KVT',
        'MOSHCHNOST_VT',
    ];
}

function polimerIsExcludedSearchSpecCode($code)
{
    $code = mb_strtoupper((string)$code);

    if (preg_match('/^(VES|MASS|WEIGHT|SHIRINA|VYSOTA|GLUBINA|DLINA|RAZMER|DIAMETR|UDELNYY_VES|UDERZHIVAEMYY_VES)/', $code))
        return true;

    return false;
}

function polimerGetSearchSpecCandidateCodes($iblockId, $sectionId)
{
    $codes = polimerGetSectionSmartFilterCodes($iblockId, $sectionId, 20);
    $codes = array_merge($codes, polimerGetSearchSpecFallbackCodes());
    $codes = polimerPrioritizeSearchSpecCodes($codes);

    return array_values(array_filter(array_unique($codes), static function ($code) {
        return !polimerIsExcludedSearchSpecCode($code);
    }));
}

function polimerExtractSpecsFromName($name)
{
    $parts = [];
    $name = trim((string)$name);

    if ($name === '')
        return $parts;

    if (preg_match('/(\d+(?:[,\.]\d+)?)\s*(?:квт|kw|kwt)\b/ui', $name, $match))
        $parts[] = str_replace('.', ',', $match[1]) . ' кВт';

    if (preg_match('/(одноконтурн\w*|двухконтурн\w*|комбинированн\w*)/ui', $name, $match))
        $parts[] = mb_strtolower($match[1]);

    return $parts;
}

function polimerGetSearchSectionPropertyCodes($iblockId, $sectionId, $limit = 2)
{
    return array_slice(polimerGetSearchSpecCandidateCodes($iblockId, $sectionId), 0, max($limit, 8));
}

function polimerPrioritizeSearchSpecCodes(array $codes)
{
    $priority = [
        'TIP_KOTLA', 'KONTOURNOST', 'KOLICHESTVO_KONTOUROV', 'VOZMOZHNOE_PODKLYUCHENIE',
        'TIP_NASOSOV', 'VID_NASOSOV',
        'NAPOR_M_VOD_ST_', 'NAPOR', 'PODACHA', 'PROIZVODITELNOST',
        'MOSHCHNOST_KVT', 'MOSHCHNOST', 'MOSHCH', 'MOCHNOST', 'NOMINALNAYA_MOSHCHNOST',
        'MOSHCHNOST_VT',
        'TIP',
        'OBEM', 'EMKOST', 'OBEM_BAKA',
    ];
    $deprioritize = ['VES_KG', 'VES', 'MASS', 'WEIGHT', 'SHIRINA', 'VYSOTA', 'GLUBINA', 'DLINA'];
    usort($codes, static function ($a, $b) use ($priority, $deprioritize) {
        $aUpper = mb_strtoupper($a);
        $bUpper = mb_strtoupper($b);

        $aDep = in_array($aUpper, $deprioritize, true) ? 1 : 0;
        $bDep = in_array($bUpper, $deprioritize, true) ? 1 : 0;
        if ($aDep !== $bDep)
            return $aDep <=> $bDep;

        $aIndex = array_search($aUpper, $priority, true);
        $bIndex = array_search($bUpper, $priority, true);
        $aIndex = $aIndex === false ? 100 : $aIndex;
        $bIndex = $bIndex === false ? 100 : $bIndex;

        if ($aIndex === $bIndex)
            return strcmp($a, $b);

        return $aIndex <=> $bIndex;
    });

    return array_values(array_unique($codes));
}

function polimerFillSearchProductSpecs(array &$products, $iblockId = IBLOCK_CATALOG, $propsLimit = 2)
{
    if (empty($products) || !CModule::IncludeModule('iblock'))
        return;

    $iblockId = (int)$iblockId;
    $propsLimit = max(1, (int)$propsLimit);
    $sectionCodesCache = [];
    $productIds = [];
    $codesByProduct = [];

    foreach ($products as $index => $product)
    {
        $productId = (int)($product['ELEMENT_ID'] ?? $product['ITEM_ID'] ?? 0);
        $sectionId = (int)($product['SECTION_ID'] ?? 0);

        if ($productId <= 0)
            continue;

        $productIds[] = $productId;

        if ($sectionId > 0)
        {
            if (!isset($sectionCodesCache[$sectionId]))
                $sectionCodesCache[$sectionId] = polimerGetSearchSpecCandidateCodes($iblockId, $sectionId);

            $codesByProduct[$productId] = $sectionCodesCache[$sectionId];
        }
        else
        {
            $codesByProduct[$productId] = polimerGetSearchSpecFallbackCodes();
        }
    }

    $productIds = array_values(array_unique($productIds));
    if (empty($productIds))
        return;

    $allCodes = polimerGetSearchSpecFallbackCodes();
    foreach ($codesByProduct as $codes)
        $allCodes = array_merge($allCodes, $codes);

    $allCodes = array_values(array_unique(array_filter($allCodes, static function ($code) {
        return !polimerIsExcludedSearchSpecCode($code);
    })));
    $propsByProduct = [];

    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'ID' => $productIds, 'ACTIVE' => 'Y'],
        false,
        false,
        ['ID', 'IBLOCK_ID']
    );

    while ($ob = $res->GetNextElement())
    {
        $fields = $ob->GetFields();
        $props = $ob->GetProperties();

        if (!empty($allCodes))
        {
            $filteredProps = [];
            foreach ($allCodes as $code)
            {
                if (isset($props[$code]))
                    $filteredProps[$code] = $props[$code];
            }
            $props = $filteredProps;
        }

        $propsByProduct[(int)$fields['ID']] = $props;
    }

    foreach ($products as &$product)
    {
        $productId = (int)($product['ELEMENT_ID'] ?? $product['ITEM_ID'] ?? 0);
        $specs = [];
        $codes = $codesByProduct[$productId] ?? [];

        $descriptive = [];
        $numeric = [];

        foreach ($codes as $code)
        {
            if (polimerIsExcludedSearchSpecCode($code))
                continue;

            $prop = $propsByProduct[$productId][$code] ?? [];
            $rawValue = polimerFormatPropertyDisplayValue($prop);
            if ($rawValue === '')
                continue;

            $part = polimerFormatSearchSpecPart($prop);
            if ($part === '')
                continue;

            if (preg_match('/^[\d\s,\.]+$/u', $rawValue))
                $numeric[] = $part;
            else
                $descriptive[] = $part;
        }

        foreach (array_merge($descriptive, $numeric) as $part)
        {
            if (!in_array($part, $specs, true))
                $specs[] = $part;

            if (count($specs) >= $propsLimit)
                break;
        }

        if (count($specs) < $propsLimit)
        {
            foreach (polimerExtractSpecsFromName($product['NAME'] ?? '') as $part)
            {
                if ($part !== '' && !in_array($part, $specs, true))
                    $specs[] = $part;

                if (count($specs) >= $propsLimit)
                    break;
            }
        }

        $product['SPECS'] = implode(' · ', $specs);
    }
    unset($product);
}

function polimerBuildSearchSectionsFromProducts(array $products, $noPhoto = '/bitrix/templates/main/img/no_photo.png')
{
    if (empty($products) || !CModule::IncludeModule('iblock'))
        return [];

    $counts = [];
    foreach ($products as $product)
    {
        $sectionId = (int)($product['SECTION_ID'] ?? 0);
        if ($sectionId > 0)
            $counts[$sectionId] = ($counts[$sectionId] ?? 0) + 1;
    }

    if (empty($counts))
        return [];

    $sections = [];
    $res = CIBlockSection::GetList(
        ['NAME' => 'ASC'],
        ['ID' => array_keys($counts)],
        false,
        ['ID', 'NAME', 'SECTION_PAGE_URL', 'PICTURE', 'ELEMENT_CNT']
    );

    while ($row = $res->GetNext())
    {
        $sectionId = (int)$row['ID'];
        $picture = $noPhoto;

        if (!empty($row['PICTURE']))
        {
            $resized = CFile::ResizeImageGet(
                $row['PICTURE'],
                ['width' => 48, 'height' => 48],
                BX_RESIZE_IMAGE_EXACT,
                true
            );
            if (!empty($resized['src']))
                $picture = $resized['src'];
        }

        $searchCount = (int)($counts[$sectionId] ?? 0);
        $sections[] = [
            'ID' => $sectionId,
            'NAME' => $row['NAME'],
            'URL' => $row['SECTION_PAGE_URL'],
            'PICTURE' => $picture,
            'COUNT' => $searchCount,
            'TOTAL' => (int)$row['ELEMENT_CNT'],
        ];
    }

    usort($sections, static function ($a, $b) {
        if ($a['COUNT'] !== $b['COUNT'])
            return $b['COUNT'] <=> $a['COUNT'];

        return strcmp($a['NAME'], $b['NAME']);
    });

    return $sections;
}

function polimerConvertMixedLayoutChars($text)
{
    static $map = [
        'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з',
        '[' => 'х', ']' => 'ъ', 'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л',
        'l' => 'д', ';' => 'ж', '\'' => 'э', 'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
        ',' => 'б', '.' => 'ю', '/' => '.',
        'Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е', 'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З',
        'A' => 'Ф', 'S' => 'Ы', 'D' => 'В', 'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л',
        'L' => 'Д', 'Z' => 'Я', 'X' => 'Ч', 'C' => 'С', 'V' => 'М', 'B' => 'И', 'N' => 'Т', 'M' => 'Ь',
    ];

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $converted = [];

    foreach ($chars as $char)
        $converted[] = $map[$char] ?? $char;

    return implode('', $converted);
}

function polimerConvertKeyboardLayoutMixed($text)
{
    if (!CModule::IncludeModule('search'))
        return $text;

    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/search/tools/language.php';

    $variants = [
        CSearchLanguage::ConvertKeyboardLayout($text, 'en', 'ru'),
        CSearchLanguage::ConvertKeyboardLayout($text, 'ru', 'en'),
    ];

    foreach ($variants as $variant)
    {
        if ($variant && $variant !== $text)
            return $variant;
    }

    return $text;
}

function polimerGetCyrillicTypoSubstitutes($char)
{
    static $map = [
        'а' => 'аоея', 'б' => 'бп', 'в' => 'вм', 'г' => 'гн', 'д' => 'дл', 'е' => 'еиё', 'ё' => 'ёео',
        'ж' => 'жш', 'з' => 'зс', 'и' => 'иеы', 'й' => 'йи', 'к' => 'кул', 'л' => 'лдк', 'м' => 'мнв',
        'н' => 'нмг', 'о' => 'оаеу', 'п' => 'прб', 'р' => 'рл', 'с' => 'сз', 'т' => 'ть', 'у' => 'уко',
        'ф' => 'фа', 'х' => 'х', 'ц' => 'цс', 'ч' => 'чш', 'ш' => 'шщч', 'щ' => 'щш', 'ы' => 'ыи',
        'ь' => 'ьъ', 'ъ' => 'ъь', 'э' => 'эе', 'ю' => 'юу', 'я' => 'яа',
    ];

    $char = mb_strtolower((string)$char);

    return preg_split('//u', $map[$char] ?? $char, -1, PREG_SPLIT_NO_EMPTY);
}

function polimerGenerateTypoQueries($query, $maxVariants = 40)
{
    $query = mb_strtolower(trim((string)$query));
    $length = mb_strlen($query);

    if ($length < 3 || $length > 24)
        return [];

    if (!preg_match('/^[а-яё\-]+$/u', $query))
        return [];

    $variants = [];

    for ($i = 0; $i < $length; $i++)
    {
        $char = mb_substr($query, $i, 1);
        foreach (polimerGetCyrillicTypoSubstitutes($char) as $substitute)
        {
            if ($substitute === $char)
                continue;

            $candidate = mb_substr($query, 0, $i) . $substitute . mb_substr($query, $i + 1);
            if ($candidate !== $query)
                $variants[] = $candidate;
        }
    }

    $chars = preg_split('//u', $query, -1, PREG_SPLIT_NO_EMPTY);
    for ($i = 0; $i < $length - 1; $i++)
    {
        $swapped = $chars;
        $tmp = $swapped[$i];
        $swapped[$i] = $swapped[$i + 1];
        $swapped[$i + 1] = $tmp;
        $variants[] = implode('', $swapped);
    }

    return array_slice(array_values(array_unique($variants)), 0, max(1, (int)$maxVariants));
}

function polimerBuildTokenTypoQueries($query, $maxVariants = 40, $maxVariantsPerToken = 12)
{
    $query = trim((string)$query);
    if ($query === '')
        return [];

    if (!preg_match('/[\s,]+/u', $query))
        return polimerGenerateTypoQueries($query, $maxVariants);

    $tokens = preg_split('/[\s,]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    if (count($tokens) < 2)
        return polimerGenerateTypoQueries($query, $maxVariants);

    $variants = [];
    foreach ($tokens as $index => $token)
    {
        if (mb_strlen($token) < 3)
            continue;

        foreach (polimerGenerateTypoQueries($token, $maxVariantsPerToken) as $variant)
        {
            if ($variant === mb_strtolower($token))
                continue;

            $rebuilt = $tokens;
            $rebuilt[$index] = $variant;
            $variants[] = implode(' ', $rebuilt);
        }
    }

    if (count($tokens) >= 2 && count($tokens) <= 4)
    {
        $tokenOptions = [];
        foreach ($tokens as $token)
        {
            $options = [mb_strtolower($token)];
            if (mb_strlen($token) >= 3)
            {
                foreach (polimerGenerateTypoQueries($token, $maxVariantsPerToken) as $variant)
                    $options[] = $variant;
            }
            $tokenOptions[] = array_values(array_unique($options));
        }

        $combined = [[]];
        foreach ($tokenOptions as $options)
        {
            $next = [];
            foreach ($combined as $prefix)
            {
                foreach ($options as $option)
                    $next[] = array_merge($prefix, [$option]);
            }
            $combined = $next;
        }

        foreach ($combined as $parts)
        {
            $candidate = implode(' ', $parts);
            if (mb_strtolower($candidate) !== mb_strtolower($query))
                $variants[] = $candidate;
        }
    }

    return array_slice(array_values(array_unique($variants)), 0, max(1, (int)$maxVariants));
}

function polimerCorrectSearchQueryByTokens($query, $iblockId = IBLOCK_CATALOG)
{
    $query = trim((string)$query);
    if ($query === '')
        return null;

    if (!empty(polimerSearchCatalogByTokens($query, $iblockId, 1)))
        return $query;

    foreach (polimerBuildSearchQueries($query, true) as $variant)
    {
        if (mb_strtolower($variant) === mb_strtolower($query))
            continue;

        if (!empty(polimerSearchCatalogByTokens($variant, $iblockId, 1)))
            return $variant;
    }

    $tokens = preg_split('/[\s,]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    if (count($tokens) < 2)
        return null;

    $corrected = $tokens;
    $changed = true;
    $passes = 0;

    while ($changed && $passes < count($tokens) * 2)
    {
        $changed = false;
        $passes++;

        foreach ($corrected as $index => $token)
        {
            if (mb_strlen($token) < 3)
                continue;

            foreach (polimerGenerateTypoQueries($token, 15) as $variant)
            {
                if ($variant === mb_strtolower($token))
                    continue;

                $candidateTokens = $corrected;
                $candidateTokens[$index] = $variant;
                $candidate = implode(' ', $candidateTokens);

                if (!empty(polimerSearchCatalogByTokens($candidate, $iblockId, 1)))
                {
                    $corrected = $candidateTokens;
                    $changed = true;
                    break 2;
                }
            }
        }
    }

    $result = implode(' ', $corrected);

    return !empty(polimerSearchCatalogByTokens($result, $iblockId, 1)) ? $result : null;
}

function polimerSuggestSearchCorrection($query, $iblockId = IBLOCK_CATALOG)
{
    $corrected = polimerCorrectSearchQueryByTokens($query, $iblockId);
    if (!$corrected || mb_strtolower($corrected) === mb_strtolower(trim((string)$query)))
        return null;

    return $corrected;
}

function polimerBuildSearchQueries($query, $includeTypo = false)
{
    $query = trim((string)$query);
    if ($query === '')
        return [];

    $queries = [$query];

    if (CModule::IncludeModule('search'))
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/search/tools/language.php';

        $arLang = CSearchLanguage::GuessLanguage($query);
        if (is_array($arLang) && $arLang['from'] !== $arLang['to'])
        {
            $alt = CSearchLanguage::ConvertKeyboardLayout($query, $arLang['from'], $arLang['to']);
            if ($alt && $alt !== $query)
                $queries[] = $alt;
        }
    }

    $mixed = polimerConvertKeyboardLayoutMixed($query);
    if ($mixed !== $query)
        $queries[] = $mixed;

    $mixedChars = polimerConvertMixedLayoutChars($query);
    if ($mixedChars !== $query)
        $queries[] = $mixedChars;

    if ($includeTypo)
    {
        if (preg_match('/[\s,]+/u', $query))
            $queries = array_merge($queries, polimerBuildTokenTypoQueries($query));
        else
            $queries = array_merge($queries, polimerGenerateTypoQueries($query));
    }

    return array_values(array_unique(array_filter($queries)));
}

function polimerBuildCatalogTokenFilter(array $tokens, $iblockId = IBLOCK_CATALOG, $fullText = true)
{
    $filter = [
        'IBLOCK_ID' => (int)$iblockId,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
    ];

    if (empty($tokens))
        return $filter;

    $tokenFilters = [];
    foreach ($tokens as $token)
    {
        if ($fullText)
        {
            $tokenFilters[] = [
                'LOGIC' => 'OR',
                ['?NAME' => $token],
                ['?PREVIEW_TEXT' => $token],
                ['?DETAIL_TEXT' => $token],
            ];
        }
        else
        {
            $tokenFilters[] = ['?NAME' => $token];
        }
    }

    if (count($tokenFilters) === 1)
        $filter[] = $tokenFilters[0];
    else
        $filter[] = array_merge(['LOGIC' => 'AND'], $tokenFilters);

    return $filter;
}

function polimerFetchCatalogElementIds(array $filter, $limit = 0)
{
    if (!CModule::IncludeModule('iblock'))
        return [];

    $navParams = ((int)$limit > 0) ? ['nTopCount' => (int)$limit] : false;
    $ids = [];
    $res = CIBlockElement::GetList(
        ['SHOW_COUNTER' => 'DESC', 'NAME' => 'ASC'],
        $filter,
        false,
        $navParams,
        ['ID']
    );

    while ($row = $res->GetNext())
    {
        $id = (int)($row['ID'] ?? 0);
        if ($id > 0)
            $ids[] = $id;
    }

    return $ids;
}

function polimerSearchCatalogByTokens($query, $iblockId = IBLOCK_CATALOG, $limit = 15, array $excludeIds = [], $fullText = false)
{
    if (!CModule::IncludeModule('iblock'))
        return [];

    $tokens = preg_split('/[\s,]+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
    $tokens = array_values(array_filter($tokens, static function ($token) {
        return mb_strlen($token) >= 2;
    }));

    if (empty($tokens))
        return [];

    $filter = polimerBuildCatalogTokenFilter($tokens, $iblockId, $fullText);

    if (!empty($excludeIds))
        $filter['!ID'] = $excludeIds;

    $items = [];
    $navParams = ((int)$limit > 0) ? ['nTopCount' => (int)$limit] : false;
    $res = CIBlockElement::GetList(
        ['SHOW_COUNTER' => 'DESC', 'NAME' => 'ASC'],
        $filter,
        false,
        $navParams,
        ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL']
    );

    while ($row = $res->GetNext())
    {
        $items[] = [
            'NAME' => $row['NAME'],
            'URL' => $row['DETAIL_PAGE_URL'],
            'MODULE_ID' => 'iblock',
            'PARAM1' => '1c_catalog',
            'PARAM2' => (int)$row['IBLOCK_ID'],
            'ITEM_ID' => (int)$row['ID'],
        ];
    }

    return $items;
}

function polimerSearchCatalogAllIds($query, $iblockId = IBLOCK_CATALOG, $maxIds = 50000, $fullText = true)
{
    $query = trim((string)$query);
    if ($query === '')
        return [];

    $seen = [];
    $allIds = [];
    $queries = polimerBuildSearchQueries($query, true);

    foreach ($queries as $searchQuery)
    {
        if (count($allIds) >= $maxIds)
            break;

        $tokens = preg_split('/[\s,]+/u', trim($searchQuery), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter($tokens, static function ($token) {
            return mb_strlen($token) >= 2;
        }));

        if (empty($tokens))
            continue;

        $filter = polimerBuildCatalogTokenFilter($tokens, $iblockId, $fullText);
        $remaining = $maxIds - count($allIds);
        if ($remaining <= 0)
            break;

        $ids = polimerFetchCatalogElementIds($filter, $remaining);

        foreach ($ids as $id)
        {
            if (isset($seen[$id]))
                continue;

            $seen[$id] = true;
            $allIds[] = $id;

            if (count($allIds) >= $maxIds)
                break 2;
        }
    }

    return $allIds;
}

function polimerSearchBitrixCatalogIds($query, array $arParams, $iblockId = IBLOCK_CATALOG, $maxIds = 50000)
{
    if (!CModule::IncludeModule('search'))
        return [];

    $query = trim((string)$query);
    if ($query === '')
        return [];

    $exFILTER = CSearchParameters::ConvertParamsToFilter($arParams, 'arrFILTER');

    $arFilter = [
        'QUERY' => $query,
        'SITE_ID' => SITE_ID,
    ];

    if (($arParams['CHECK_DATES'] ?? '') === 'Y')
        $arFilter['CHECK_DATES'] = 'Y';

    $obSearch = new CSearch();
    $obSearch->limit = max(500, (int)$maxIds);
    $obSearch->SetOptions([
        'ERROR_ON_EMPTY_STEM' => ($arParams['RESTART'] ?? '') !== 'Y',
        'NO_WORD_LOGIC' => isset($arParams['NO_WORD_LOGIC']) && $arParams['NO_WORD_LOGIC'] === 'Y',
    ]);
    $obSearch->Search($arFilter, ['CUSTOM_RANK' => 'DESC', 'RANK' => 'DESC', 'TITLE_RANK' => 'DESC'], $exFILTER);

    if ($obSearch->errorno !== 0)
        return [];

    $ids = [];
    while ($ar = $obSearch->GetNext())
    {
        if ((int)($ar['PARAM2'] ?? 0) !== (int)$iblockId)
            continue;

        $id = (int)($ar['ITEM_ID'] ?? 0);
        if ($id > 0 && !in_array($id, $ids, true))
            $ids[] = $id;

        if (count($ids) >= $maxIds)
            break;
    }

    return $ids;
}

function polimerEnhanceSearchPageResult(array &$arResult, array $arParams)
{
    $query = trim((string)($arResult['REQUEST']['~QUERY'] ?? $arResult['REQUEST']['QUERY'] ?? ''));
    if ($query === '')
        return;

    $iblockId = IBLOCK_CATALOG;
    $catalogIds = polimerSearchCatalogAllIds($query, $iblockId, 5000, true);
    $bitrixIds = polimerSearchBitrixCatalogIds($query, $arParams, $iblockId, 5000);

    $seen = array_fill_keys($catalogIds, true);
    $allIds = $catalogIds;

    foreach ($bitrixIds as $id)
    {
        if (!isset($seen[$id]))
        {
            $seen[$id] = true;
            $allIds[] = $id;
        }
    }

    if (empty($allIds))
        return;

    $arResult['POLIMER_PRODUCT_IDS'] = $allIds;
    $arResult['ROWS_COUNT'] = count($allIds);
    $arResult['SEARCH'] = array_map(static function ($id) use ($iblockId) {
        return [
            'ITEM_ID' => $id,
            'ID' => $id,
            'PARAM2' => $iblockId,
        ];
    }, $allIds);
}

function polimerSearchCatalogSections($query, $iblockId = IBLOCK_CATALOG, $maxSections = 500)
{
    $query = trim((string)$query);
    if ($query === '' || !CModule::IncludeModule('iblock'))
        return [];

    $maxSections = max(1, (int)$maxSections);
    $origTokens = preg_split('/[\s,]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    $origTokens = array_values(array_filter($origTokens, static function ($token) {
        return mb_strlen($token) >= 2;
    }));

    if (empty($origTokens))
        return [];

    $queries = polimerBuildSearchQueries($query, false);
    $found = [];
    $seen = [];

    foreach ($queries as $searchQuery)
    {
        if (count($found) >= $maxSections)
            break;

        $tokens = preg_split('/[\s,]+/u', trim($searchQuery), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter($tokens, static function ($token) {
            return mb_strlen($token) >= 2;
        }));

        if (empty($tokens))
            continue;

        $filter = [
            'IBLOCK_ID' => (int)$iblockId,
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ];

        $tokenFilters = [];
        foreach ($tokens as $token)
            $tokenFilters[] = ['?NAME' => $token];

        if (count($tokenFilters) === 1)
            $filter[] = $tokenFilters[0];
        else
            $filter[] = array_merge(['LOGIC' => 'AND'], $tokenFilters);

        $res = CIBlockSection::GetList(
            ['NAME' => 'ASC'],
            $filter,
            false,
            ['ID', 'NAME', 'SECTION_PAGE_URL', 'PICTURE', 'ELEMENT_CNT']
        );

        while ($row = $res->GetNext())
        {
            $id = (int)$row['ID'];
            if (isset($seen[$id]))
                continue;

            $nameLower = mb_strtolower((string)$row['NAME']);
            $matchesOriginal = false;
            foreach ($origTokens as $token)
            {
                if (mb_strpos($nameLower, mb_strtolower($token)) !== false)
                {
                    $matchesOriginal = true;
                    break;
                }
            }

            if (!$matchesOriginal)
                continue;

            $seen[$id] = true;
            $found[] = $row;

            if (count($found) >= $maxSections)
                break;
        }
    }

    if (empty($found))
        return [];

    $queryLower = mb_strtolower($query);
    usort($found, static function ($a, $b) use ($queryLower) {
        $score = static function ($name) use ($queryLower) {
            $nameLower = mb_strtolower((string)$name);
            if ($nameLower === $queryLower)
                return 0;
            if (mb_strpos($nameLower, $queryLower) === 0)
                return 1;
            if (mb_strpos($nameLower, $queryLower) !== false)
                return 2;

            return 3;
        };

        $scoreA = $score($a['NAME']);
        $scoreB = $score($b['NAME']);
        if ($scoreA !== $scoreB)
            return $scoreA <=> $scoreB;

        return strcmp((string)$a['NAME'], (string)$b['NAME']);
    });

    return $found;
}

function polimerDisableCompositeForDynamicPages()
{
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if (
        strpos($uri, '/search/') !== false
        || (($_REQUEST['ajax_call'] ?? '') === 'y')
    )
    {
        if (!defined('BX_COMPOSITE_DISABLE'))
            define('BX_COMPOSITE_DISABLE', true);

        if (class_exists('\Bitrix\Main\Composite\Helper'))
            \Bitrix\Main\Composite\Helper::setEnabled(false);

        polimerDisableSeometaBufferHandlerForSearch();
    }
}

function polimerDisableSeometaBufferHandlerForSearch()
{
    if (!function_exists('GetModuleEvents') || !function_exists('RemoveEventHandler'))
        return;

    foreach (GetModuleEvents('main', 'OnEndBufferContent', true) as $key => $event)
    {
        if (($event['TO_MODULE_ID'] ?? '') !== 'sotbit.seometa')
            continue;

        if (($event['TO_METHOD'] ?? '') === 'ChangeContent')
            RemoveEventHandler('main', 'OnEndBufferContent', $key);
    }
}

function polimerBuildSearchPageNav($currentPage, $pageSize, $totalCount, $queryString)
{
    $pageSize = max(1, (int)$pageSize);
    $totalCount = max(0, (int)$totalCount);
    $pageCount = max(1, (int)ceil($totalCount / $pageSize));
    $currentPage = max(1, min((int)$currentPage, $pageCount));

    if ($pageCount <= 1)
        return '';

    $queryString = trim((string)$queryString);
    $baseUrl = '/search/' . ($queryString !== '' ? '?' . $queryString : '');

    $buildUrl = static function ($page) use ($baseUrl, $queryString) {
        if ($page <= 1)
            return $baseUrl;

        $params = [];
        if ($queryString !== '')
            parse_str($queryString, $params);

        $params['PAGEN_1'] = $page;

        return '/search/?' . http_build_query($params);
    };

    $window = 5;
    $startPage = max(1, $currentPage - (int)floor($window / 2));
    $endPage = min($pageCount, $startPage + $window - 1);
    $startPage = max(1, $endPage - $window + 1);

    $somePage = [];
    for ($page = $startPage; $page <= $endPage; $page++)
        $somePage[$page] = $buildUrl($page);

    $html = '<div class="ns__paginator cl">';
    $html .= '<div class="name">Страницы:</div>';

    if ($currentPage === 1)
        $html .= '<a href="#" class="arrow left"><span></span><span></span></a>';
    else
        $html .= '<a href="' . htmlspecialcharsbx($buildUrl($currentPage - 1)) . '" class="arrow left aractive"><span></span><span></span></a>';

    $html .= '<div class="pages cl">';
    for ($page = $startPage; $page <= $endPage; $page++)
    {
        if ($page === $currentPage)
            $html .= '<a href="" class="page active">' . $page . '</a>';
        else
            $html .= '<a href="' . htmlspecialcharsbx($somePage[$page]) . '" class="page">' . $page . '</a>';
    }
    $html .= '</div>';

    if ($currentPage === $pageCount)
        $html .= '<a href="#" class="arrow right"><span></span><span></span></a>';
    else
        $html .= '<a href="' . htmlspecialcharsbx($buildUrl($currentPage + 1)) . '" class="arrow right aractive"><span></span><span></span></a>';

    $html .= '</div>';

    return $html;
}

function polimerEnhanceTitleSearchResult(array &$arResult, array $arParams)
{
    if (empty($arResult['query']) || !CModule::IncludeModule('search'))
        return;

    $existingIds = [];
    $productCount = 0;

    foreach ($arResult['CATEGORIES'] as &$category)
    {
        if (empty($category['ITEMS']) || !is_array($category['ITEMS']))
            continue;

        foreach ($category['ITEMS'] as $item)
        {
            if (!empty($item['TYPE']) && $item['TYPE'] === 'all')
                continue;

            if (!empty($item['ITEM_ID']) && substr((string)$item['ITEM_ID'], 0, 1) !== 'S')
            {
                $existingIds[] = (int)$item['ITEM_ID'];
                $productCount++;
            }
        }
    }
    unset($category);

    $topCount = (int)($arParams['TOP_COUNT'] ?? 15);
    if ($topCount <= 0)
        $topCount = 15;

    if ($productCount >= $topCount)
        return;

    $categoryIndex = 0;
    if (empty($arResult['CATEGORIES']))
    {
        $categoryTitle = trim($arParams['CATEGORY_0_TITLE'] ?? '');
        if ($categoryTitle === '' && !empty($arParams['CATEGORY_0']))
            $categoryTitle = is_array($arParams['CATEGORY_0']) ? implode(', ', $arParams['CATEGORY_0']) : $arParams['CATEGORY_0'];

        $arResult['CATEGORIES'][$categoryIndex] = [
            'TITLE' => htmlspecialcharsbx($categoryTitle),
            'ITEMS' => [],
        ];
    }
    else
    {
        $categoryKeys = array_keys($arResult['CATEGORIES']);
        $categoryIndex = (int)$categoryKeys[0];
    }

    $originalQuery = trim((string)$arResult['query']);
    $queries = polimerBuildSearchQueries($originalQuery, $productCount === 0);

    foreach ($queries as $searchQuery)
    {
        if ($productCount >= $topCount)
            break;

        if (!isset($arResult['CATEGORIES'][$categoryIndex]))
            break;

        $beforeCount = $productCount;

        $exFILTER = [
            0 => CSearchParameters::ConvertParamsToFilter($arParams, 'CATEGORY_' . $categoryIndex),
        ];
        $exFILTER[0]['LOGIC'] = 'OR';

        if (($arParams['CHECK_DATES'] ?? '') === 'Y')
            $exFILTER['CHECK_DATES'] = 'Y';

        $obTitle = new CSearchTitle;
        $obTitle->setMinWordLength($_REQUEST['l'] ?? 2);

        if (!$obTitle->Search($searchQuery, $topCount, $exFILTER, false, $arParams['ORDER'] ?? 'rank'))
            continue;

        while ($ar = $obTitle->Fetch())
        {
            $itemId = (int)$ar['ITEM_ID'];
            if ($itemId <= 0 || in_array($itemId, $existingIds, true))
                continue;

            $arResult['CATEGORIES'][$categoryIndex]['ITEMS'][] = [
                'NAME' => $ar['NAME'],
                'URL' => htmlspecialcharsbx($ar['URL']),
                'MODULE_ID' => $ar['MODULE_ID'],
                'PARAM1' => $ar['PARAM1'],
                'PARAM2' => $ar['PARAM2'],
                'ITEM_ID' => $ar['ITEM_ID'],
            ];

            $existingIds[] = $itemId;
            $productCount++;

            if ($productCount >= $topCount)
                break 2;
        }

        if ($beforeCount === 0 && $productCount > 0 && mb_strtolower($searchQuery) !== mb_strtolower($originalQuery))
            $arResult['SEARCH_QUERY_CORRECTED'] = $searchQuery;
    }

    if ($productCount >= $topCount)
        return;

    foreach ($queries as $searchQuery)
    {
        if ($productCount >= $topCount)
            break;

        $beforeCount = $productCount;

        $fallbackItems = polimerSearchCatalogByTokens(
            $searchQuery,
            IBLOCK_CATALOG,
            $topCount - $productCount,
            $existingIds
        );

        foreach ($fallbackItems as $item)
        {
            $itemId = (int)$item['ITEM_ID'];
            if ($itemId <= 0 || in_array($itemId, $existingIds, true))
                continue;

            $arResult['CATEGORIES'][$categoryIndex]['ITEMS'][] = $item;
            $existingIds[] = $itemId;
            $productCount++;

            if ($productCount >= $topCount)
                break 2;
        }

        if ($beforeCount === 0 && $productCount > 0 && mb_strtolower($searchQuery) !== mb_strtolower($originalQuery))
            $arResult['SEARCH_QUERY_CORRECTED'] = $searchQuery;
    }
}

function getUrlProd($url){
    if($url){
        $code = explode('/',$url);
        if($code[3]){
            $code = $code[3];
        }else{
           $code = $code[2];
        }

        if(CModule::IncludeModule("iblock")) {
            $arSelect = Array("ID", "IBLOCK_ID","DETAIL_PAGE_URL","PREVIEW_PICTURE","DETAIL_PICTURE", "NAME", "PROPERTY_*");//IBLOCK_ID � ID ����������� ������ ���� �������, ��. �������� arSelectFields ����
            $arFilter = Array("IBLOCK_ID" => 21, "CODE" => $code);
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            if($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $arResults = array_merge($arFields,$arProps);
                return $arResults;
            }
        }
    }

}

AddEventHandler('main', 'OnProlog', 'polimerDisableCompositeForDynamicPages');

AddEventHandler("main", "OnEpilog", "Redirect404");
function Redirect404() {
    if(
        !defined('ADMIN_SECTION') &&
        defined("ERROR_404") &&
        defined("PATH_TO_404") &&
        file_exists($_SERVER["DOCUMENT_ROOT"].PATH_TO_404)
    ) {

        //LocalRedirect("/404.php", "404 Not Found");
        global $APPLICATION;
        global $USER;
        $APPLICATION->RestartBuffer();

        CHTTP::SetStatus("404 Not Found");
        include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/header.php");
        include($_SERVER["DOCUMENT_ROOT"].PATH_TO_404);
        include($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/footer.php");
    }
}


\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleStatusOrderChange',
    'StatusOrderChange'
);

function StatusOrderChange(\Bitrix\Main\Event $event)
{
    $status = $event->getParameter("ENTITY");

    if($status->getField('STATUS_ID') == "F"){
        $user_id = $status->getField('USER_ID');
        $rsUser = CUser::GetByID($user_id);
        if($arUser = $rsUser->Fetch()){
        $user_name = $arUser['NAME'].' '.$arUser['LAST_NAME'];
        $user_email = $arUser['EMAIL'];
        }
        $order_id = $status->getField('ID');

        if ($arOrder = CSaleOrder::GetByID($order_id))
        {
            $date_status = $arOrder['DATE_STATUS'];
        }

        $arFields = array(
            "USER_NAME" => $user_name,
            "USER_EMAIL" => $user_email,
            "ORDER_ID" => $order_id,
            "ORDER_DATE" => $date_status
        );
        CEvent::Send("ORDER_COMPLETED", "s1", $arFields);
    }
}

function AddOrderProperty($code, $value, $order)    {
    if (!strlen($code)) {
        return false;
    }
    if (CModule::IncludeModule('sale')) {
        if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' => $code))->Fetch()) {

            $db_vals = CSaleOrderPropsValue::GetList(
                array(),
                array(
                    "ORDER_ID" => $order,
                    "ORDER_PROPS_ID" => $arProp["ID"]
                )
            );
            if ($arVals = $db_vals->Fetch()) {
                CSaleOrderPropsValue::Update($arVals["ID"], array("VALUE"=>$value));
            } else {
                CSaleOrderPropsValue::Add(array(
                    'NAME' => $arProp['NAME'],
                    'CODE' => $arProp['CODE'],
                    'ORDER_PROPS_ID' => $arProp['ID'],
                    'ORDER_ID' => $order,
                    'VALUE' => $value,
                ));
            }
            //  тут можно увидеть ошибку, если что
//                global $APPLICATION;
//                var_dump($APPLICATION->GetException());
        }
    }
}

function resizeImage($id, $w, $h){
    $no_photo_path = '/bitrix/templates/main/img/no_photo.png';

    if(!isImageExists($id)) {
        return $no_photo_path;
    }

    $resized = CFile::ResizeImageGet(
        $id,
        ["width" => $w, "height" => $h],
        BX_RESIZE_IMAGE_PROPORTIONAL,
        true,
        false,
        false,
        85
    );

    if (!empty($resized['src'])) {
        return $resized['src'];
    }

    return CFile::GetPath($id) ?: $no_photo_path;
}

function resizeCatalogCardImage($id, $w, $h)
{
    return resizeImage($id, $w, $h);
}

function isImageExists($fileId) {
    if (!is_numeric($fileId) || empty($fileId)) {
        return false;
    }

    $path = CFile::GetPath($fileId);
    if ($path && file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return true;
    }

    return false;
}

function tel($phone){
    return str_replace(['-', ' '], '', filter_var($phone, FILTER_SANITIZE_NUMBER_INT));
}

//Обнавление ИБ Каталог брендов
/*
CAgent::AddAgent(
    "CBrands::updateBrandsAgent();",
    "",
    "N",
    604800,
    "",
    "Y",
    ""
);
*/
Class CBrands
{
    public static function updateBrandsAgent()
    {
        if(CModule::IncludeModule("iblock")) {

            $arResult = [];
            $arSelect = Array(
                "ID",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "NAME",
                "PROPERTY_PROIZVODITEL"
            );

            $arFilter = Array("IBLOCK_ID" => 21, "ACTIVE" => "Y");
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            $i = 0;
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                if (isset($arFields['PROPERTY_PROIZVODITEL_VALUE']) && strlen($arFields['PROPERTY_PROIZVODITEL_VALUE']) > 0 && $arFields['IBLOCK_SECTION_ID']) {
                    $brand_name = trim($arFields['PROPERTY_PROIZVODITEL_VALUE']);
                    $arResult[$brand_name]["n" . $i] = ["VALUE" => $arFields['ID']];
                }
                $i++;
            }
            if (count($arResult) > 0) {

                foreach ($arResult as $brand => $arItem) {

                    $find = CIBlockElement::GetList(Array(), ["IBLOCK_ID" => 28, "=NAME" => trim($brand)], false, false, ["ID"])->Fetch();

                    $el = new CIBlockElement;
                    $params = Array(
                        "max_len" => "100", // обрезает символьный код до 100 символов
                        "change_case" => "L", // буквы преобразуются к нижнему регистру
                        "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                        "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                        "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                        "use_google" => "false", // отключаем использование google
                    );

                    $PROP["PRODUCT"] = $arItem;
                    $arLoadProductArray = Array(
                        "IBLOCK_SECTION_ID" => false,
                        "IBLOCK_ID" => 28,
                        "PROPERTY_VALUES" => $PROP,
                        "NAME" => trim($brand),
                        "CODE" => CUtil::translit($brand, "ru" , $params),
                        "ACTIVE" => "Y"
                    );
                    if(!$el->Add($arLoadProductArray)){
                        $el->Update($find['ID'], $arLoadProductArray);
                    }
                }
            }
        }
        return "CBrands::updateBrandsAgent();";
    }
}

AddEventHandler('catalog', 'OnSuccessCatalogImport1C', 'functionOnSuccessCatalogImport1C');
function functionOnSuccessCatalogImport1C()
{
    $page = \Bitrix\Main\Composite\Page::getInstance();
    $page->deleteAll();
}

if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/checkSize.php')){
   require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/checkSize.php');
}


AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");
function bxModifySaleMails($orderID, &$eventName, &$arFields)
{
  CModule::IncludeModule('sale');
  CModule::IncludeModule('iblock');
  
  orderListTable($orderID, $arFields);
	
  $arOrder = CSaleOrder::GetByID($orderID);
  
  $order_props = CSaleOrderPropsValue::GetOrderProps($orderID);
  $phone="";
  $index = ""; 
  $country_name = "";
  $city_name = "";  
  $address = "";
  while ($arProps = $order_props->Fetch())
  {
    if ($arProps["CODE"] == "PHONE")
    {
       $phone = htmlspecialchars($arProps["VALUE"]);
    }
    if ($arProps["CODE"] == "LOCATION")
    {
        $arLocs = CSaleLocation::GetByID($arProps["VALUE"]);
        $country_name =  $arLocs["COUNTRY_NAME_ORIG"];
        $city_name = $arLocs["CITY_NAME_ORIG"];
    }

    if ($arProps["CODE"] == "INDEX")
    {
      $index = $arProps["VALUE"];   
    }

    if ($arProps["CODE"] == "ADDRESS")
    {
      $address = $arProps["VALUE"];
    }
  }

  $full_address = $country_name."-".$city_name.", ".$address;

  $arDeliv = CSaleDelivery::GetByID($arOrder["DELIVERY_ID"]);
  $delivery_name = "";
  if ($arDeliv)
  {
    $delivery_name = $arDeliv["NAME"];
  }

  $arPaySystem = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"]);
  $pay_system_name = "";
  if ($arPaySystem)
  {
    $pay_system_name = $arPaySystem["NAME"];
  }

  $arFields["ORDER_DESCRIPTION"] = $arOrder["USER_DESCRIPTION"]; 
  $arFields["PHONE"] =  $phone;
  $arFields["DELIVERY_NAME"] =  $delivery_name;
  $arFields["PAY_SYSTEM_NAME"] =  $pay_system_name;
  $arFields["FULL_ADDRESS"] = $full_address;   
}

function orderListTable($orderID, &$arFields)
{
		include_once  __DIR__. '/mail_item_template.php';
	
		$strOrderList = [];
        $productsList = [];
        $productIds = [];
        $dbBasketItems = CSaleBasket::GetList(
            ['ID'=>'ASC'],
            ['ORDER_ID' => $orderID],
            false,
            false,
            ['PRODUCT_ID', 'ID', 'NAME', 'QUANTITY', 'PRICE', 'CURRENCY']
        );

        while ($arProps = $dbBasketItems->Fetch()) {

            $productIds[] = $arProps['PRODUCT_ID'];
            $productsList[$arProps['PRODUCT_ID']] = [
                'NAME' => $arProps['NAME'],
                'QUANTITY' => $arProps['QUANTITY'],
                'PRICE' => round($arProps['PRICE'], 2),
                'SUM'   => round($arProps['PRICE'] * $arProps['QUANTITY'], 2),
            ];
        }
		
		$arSelectProducts = ['ID', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE', 'PROPERTY_CML2_ARTICLE'];
        $arFilterProducts = ['IBLOCK_ID' => IBLOCK_CATALOG, 'ID' => $productIds];
        $resProductList = CIBlockElement::GetList(['ID'=>'ASC'], $arFilterProducts, false, false, $arSelectProducts);
        while($obProductList = $resProductList->GetNextElement()) {
            $arFieldsProductList = $obProductList->GetFields();

            $productsList[$arFieldsProductList['ID']]['DETAIL_PAGE_URL'] = 'https://polimer-vrn.ru' . $arFieldsProductList['DETAIL_PAGE_URL'];
            $productsList[$arFieldsProductList['ID']]['PREVIEW_PICTURE'] = 'https://polimer-vrn.ru' . CFile::GetPath($arFieldsProductList['PREVIEW_PICTURE']);
            $productsList[$arFieldsProductList['ID']]['SKU'] = $arFieldsProductList['PROPERTY_CML2_ARTICLE_VALUE'];
        }

        foreach ($productsList as $productItem) {
            $layout = $productItemTemplate;
            $arSearch = [
                '#PRODUCT_DETAIL_PAGE#',
                '#PRODUCT_IMG#',
                '#PRODUCT_NAME#',
                '#PRODUCT_QUANTITY#',
                '#PRODUCT_PRICE#',
                '#PRODUCT_COST#',
                '#PRODUCT_SKU#'
            ];

            $arReplace = [
                $productItem['DETAIL_PAGE_URL'],
                $productItem['PREVIEW_PICTURE'],
                $productItem['NAME'],
                $productItem['QUANTITY'],
                $productItem['PRICE'],
                $productItem['SUM'],
                $productItem['SKU'],
            ];

            $strOrderList[] = str_replace($arSearch, $arReplace, $layout);
        }

        $strOrderList = implode('', $strOrderList);

        $arFields['ORDER_LIST_TABLE'] = $strOrderList;
}

// Отправка пароля пользователю при регистрации
AddEventHandler("main", "OnBeforeUserAdd", ["SendPassword", "onBeforeUserAddUpdate"]);
AddEventHandler("main", "OnBeforeEventAdd", ["SendPassword", "onBeforeEventAdd"]);

class SendPassword 
{
    private static $alreadySent = false;
    private static $needSend = false;
    private static $password = "";

    static public function onBeforeUserAddUpdate(&$arFields)
    {
        if($arFields["PASSWORD"])
        {
            self::$needSend = true;
            self::$password = $arFields["PASSWORD"];
        }
    }
	
    static public function onBeforeEventAdd(&$event, &$lid, &$arFields, &$message_id, &$files)
    {
        if($event!="USER_PASS_CHANGED" && $event!="USER_INFO")
            return;

        if(self::$alreadySent)
            return false;

        $arFields["PASSWORD"] = self::$password;

        self::$alreadySent = true;
    }
}

AddEventHandler("main", "OnAfterUserAdd", "OnAfterUserAddHandler");

function OnAfterUserAddHandler(&$arFields)
{
   if (intval($arFields["ID"]) > 0) { 
		Bitrix\Main\Mail\Event::sendImmediate(array(
			"EVENT_NAME" => "NEW_USER_REGISTER",
			"LID" => $arFields["LID"],
			"C_FIELDS" => $arFields,
		)); 
   }
   
   
   return $arFields;
} 

AddEventHandler("main", "OnAfterUserRegister", "OnAfterUserRegisterHandler");

function OnAfterUserRegisterHandler(&$arFields)
{
	if ($arFields["USER_ID"] > 0) {
		$user = new CUser;
		$user->Update($arFields["USER_ID"], ["PERSONAL_PHONE" => $_POST["USER_PERSONAL_PHONE"]]);
	}
	
	return $arFields;
}
