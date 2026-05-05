<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("iblock"))
    return;

$arIBlockType = CIBlockParameters::GetIBlockTypes();
$rsIBlock = CIBlock::GetList(
    [
        "sort" => "asc",
    ],
    [
        "TYPE" => $arCurrentValues["IBLOCK_TYPE"],
        "ACTIVE" => "Y",
    ]
);
while ($arr = $rsIBlock->Fetch())
    $arIBlock[$arr["ID"]] = "[" . $arr["ID"] . "] " . $arr["NAME"];

$result = \Bitrix\Iblock\SectionTable::getList(
    [
        'select' => ['ID', 'NAME'],
        'filter' => ['IBLOCK_ID' => $arCurrentValues['IBLOCK_ID']]
    ]
);
while ($Section = $result->fetch()) {
    $arSections[$Section["ID"]] = "[" . $Section["ID"] . "] " . $Section["NAME"];
}

$arComponentParameters = [
    "GROUPS" => [],
    "PARAMETERS" => [
        "IBLOCK_TYPE" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_IBLOCK_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlockType,
            "REFRESH" => "Y",
        ],
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_IBLOCK_IBLOCK"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arIBlock,
            "REFRESH" => "Y",
        ],
        "ELEMENT_ID" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_ELEMENT_ID"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $elementId,
        ],
        "SECTION_ID" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_SECTION_ID"),
            "TYPE" => "LIST",
            "ADDITIONAL_VALUES" => "Y",
            "VALUES" => $arSections,
        ],
        "INCLUDE_SUBSECTIONS" => [
            "PARENT" => "BASE",
            'NAME' => Loc::getMessage('SM_INCLUDE_SUBSECTIONS'),
            "TYPE" => "LIST",
            "VALUES" => [
                "Y" => Loc::getMessage('SM_INCLUDE_SUBSECTIONS_ALL'),
                "N" => Loc::getMessage('SM_INCLUDE_SUBSECTIONS_NO'),
            ],
            "DEFAULT" => "Y",
        ],
        'SORT' => [
            "PARENT" => "BASE",
            'NAME' => Loc::getMessage('SM_SORT'),
            'TYPE' => "LIST",
            "VALUES" => [
                'NAME' => Loc::getMessage("SM_SORT_NAME"),
                'CONDITIONS' => Loc::getMessage("SM_SORT_CONDITION"),
                'URL_SORT' => Loc::getMessage("SM_SORT_URL_SORT"),
                'PRODUCT_COUNT' => Loc::getMessage('SM_SORT_PRODUCT_COUNT'),
                'RANDOM' => Loc::getMessage("SM_SORT_RANDOM")
            ],
        ],
        'SORT_ORDER' => [
            "PARENT" => "BASE",
            'NAME' => Loc::getMessage('SM_SORT_ORDER'),
            'TYPE' => "LIST",
            "VALUES" => ['asc' => Loc::getMessage("SM_SORT_ORDER_ASC"), 'desc' => Loc::getMessage("SM_SORT_ORDER_DESC")],
        ],
        'CNT_TAGS' => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_CNT_TAGS"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ],
        "PRODUCT_COUNT" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_PRODUCT_COUNT"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "GENERATING_TAGS" => [
            "PARENT" => "BASE",
            "NAME" => Loc::getMessage("SM_GENERATING_TAGS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 36000000,
        ],
        "CACHE_GROUPS" => [
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => Loc::getMessage("SM_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
    ],
];
?>