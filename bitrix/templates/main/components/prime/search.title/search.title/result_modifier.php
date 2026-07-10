<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

polimerEnhanceTitleSearchResult($arResult, $arParams);

if (!empty($arResult['SEARCH_QUERY_CORRECTED']))
{
	$arResult['QUERY_ORIGINAL'] = $arResult['query'] ?? '';
	$correctedQuery = $arResult['SEARCH_QUERY_CORRECTED'];

	if (!empty($arResult['SEARCH_ALL']['URL']))
	{
		$arResult['SEARCH_ALL']['URL'] = CHTTP::urlAddParams(
			strtok($arResult['SEARCH_ALL']['URL'], '?'),
			['q' => $correctedQuery],
			['encode' => true]
		);
	}
}
else
{
	$suggestedQuery = polimerSuggestSearchCorrection($arResult['query'] ?? '');
	if ($suggestedQuery)
	{
		$arResult['SEARCH_QUERY_CORRECTED'] = $suggestedQuery;
		$arResult['QUERY_ORIGINAL'] = $arResult['query'] ?? '';

		if (!empty($arResult['SEARCH_ALL']['URL']))
		{
			$arResult['SEARCH_ALL']['URL'] = CHTTP::urlAddParams(
				strtok($arResult['SEARCH_ALL']['URL'], '?'),
				['q' => $suggestedQuery],
				['encode' => true]
			);
		}
	}
}

$arResult['USER_PROPERTY'] = array(
	"UF_DEPARTMENT",
);

$image_path = $this->GetFolder()."/images/";
$abs_path = $_SERVER["DOCUMENT_ROOT"].$image_path;
$noPhoto = '/bitrix/templates/main/img/no_photo.png';
$arIBlocks = array();

$arResult["SEARCH"] = array();
$arResult["SEARCH_SECTIONS"] = array();
$arResult["SEARCH_PRODUCTS"] = array();
$arResult["SEARCH_ALL"] = null;

foreach($arResult["CATEGORIES"] as $category_id => &$arCategory)
{
	$arFilterSection = array();
	$productItems = array();

	foreach($arCategory["ITEMS"] as $i => $arItem)
	{
		if($arItem['TYPE'] == "all")
		{
			$arResult["SEARCH_ALL"] = $arItem;
			unset($arResult["CATEGORIES"][$category_id]["ITEMS"][$i]);
			continue;
		}

		if(!isset($arItem["ITEM_ID"]))
			continue;

		$arResult["SEARCH"][] = &$arResult["CATEGORIES"][$category_id]["ITEMS"][$i];
		$productItems[] = &$arResult["CATEGORIES"][$category_id]["ITEMS"][$i];

		$productPrice = price($arItem["ITEM_ID"]);
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['PRICE_SORT'] = $productPrice ? (float)$productPrice : null;
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['FORMAT_INT'] = $productPrice
			? CurrencyFormat($productPrice, 'RUB')
			: null;

		$element = CIBlockElement::GetByID($arItem["ITEM_ID"])->GetNext();
		if($element && $element['PREVIEW_PICTURE'])
		{
			$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['PICTURE'] = CFile::ResizeImageGet(
				$element['PREVIEW_PICTURE'],
				["width" => 56, "height" => 56],
				BX_RESIZE_IMAGE_EXACT,
				true
			)['src'];
		}
		else
		{
			$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['PICTURE'] = $noPhoto;
		}

		$productId = (int)$arItem["ITEM_ID"];
		$iblockId = (int)$arItem["PARAM2"];
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['ELEMENT_ID'] = $productId;
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['SECTION_ID'] = $element ? (int)$element['IBLOCK_SECTION_ID'] : 0;
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['IBLOCK_ID'] = $iblockId;
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['STOCK_STATUS'] = polimerGetProductAvailability($productId);
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['CAN_BUY'] = $arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['STOCK_STATUS'] === 'available';
		$arResult["CATEGORIES"][$category_id]["ITEMS"][$i]['IN_COMPARE'] = inCompare($iblockId, $productId);

		$arFilterSection['IBLOCK_ID'] = $arItem["PARAM2"];
		$arFilterSection['ID'][] = $arItem["ITEM_ID"];
	}

		$arResult["SEARCH_PRODUCTS"] = array_merge($arResult["SEARCH_PRODUCTS"], $productItems);
}

polimerFillSearchProductSpecs($arResult["SEARCH_PRODUCTS"], IBLOCK_CATALOG, 2);
$arResult["SEARCH_SECTIONS"] = polimerBuildSearchSectionsFromProducts($arResult["SEARCH_PRODUCTS"], $noPhoto);
$arResult["SEARCH_PRODUCTS"] = polimerSortSearchProductsByAvailabilityAndPrice($arResult["SEARCH_PRODUCTS"]);

$searchQueryForTotal = trim((string)($arResult['SEARCH_QUERY_CORRECTED'] ?? $arResult['query'] ?? ''));
if ($searchQueryForTotal !== '')
{
    $arResult['SEARCH_PRODUCTS_TOTAL'] = count(polimerSearchCatalogAllIds($searchQueryForTotal, IBLOCK_CATALOG, 50000, true));
    if (!empty($arResult['SEARCH_ALL']) && $arResult['SEARCH_PRODUCTS_TOTAL'] > 0)
        $arResult['SEARCH_ALL']['NAME'] = 'Все ' . $arResult['SEARCH_PRODUCTS_TOTAL'] . ' результатов';
}
else
{
    $arResult['SEARCH_PRODUCTS_TOTAL'] = count($arResult['SEARCH_PRODUCTS']);
}

foreach($arResult["SEARCH"] as $i=>$arItem)
{
	$file = false;
	switch($arItem["MODULE_ID"])
	{
		case "socialnetwork":
		case "iblock":
			if(substr($arItem["ITEM_ID"], 0, 1) === "G")
			{
				if(file_exists($abs_path."socialnetwork_group.png"))
					$file = "socialnetwork_group.png";
			}
			elseif(CModule::IncludeModule('iblock'))
			{
				if(!array_key_exists($arItem["PARAM2"], $arIBlocks))
					$arIBlocks[$arItem["PARAM2"]] = CIBlock::GetArrayByID($arItem["PARAM2"]);

				if(substr($arItem["ITEM_ID"], 0, 1) !== "S")
				{
					$rsElement = CIBlockElement::GetList(array(), array(
							"=ID" => $arItem["ITEM_ID"],
							"IBLOCK_ID" => $arItem["PARAM2"],
						),
						false, false, array("ID", "IBLOCK_ID", "CODE", "XML_ID", "PROPERTY_DOC_TYPE")
					);
					$arElement = $rsElement->Fetch();
					if($arElement && strlen($arElement["PROPERTY_DOC_TYPE_ENUM_ID"]) > 0)
					{
						$arEnum = CIBlockPropertyEnum::GetByID($arElement["PROPERTY_DOC_TYPE_ENUM_ID"]);
						if($arEnum && $arEnum["XML_ID"] && file_exists($abs_path."iblock_doc_type_".strtolower($arEnum["XML_ID"]).".png"))
							$file = "iblock_doc_type_".strtolower($arEnum["XML_ID"]).".png";
					}

					if(!$file)
					{
						$rsSection = CIBlockElement::GetElementGroups($arItem["ITEM_ID"], true);
						$arSection = $rsSection->Fetch();
						$SECTION_ID = $arSection ? $arSection["ID"] : false;
					}
					else
					{
						$SECTION_ID = false;
					}
				}
				else
				{
					$SECTION_ID = $arItem["ITEM_ID"];
				}

				if(!$file && !empty($SECTION_ID))
				{
					$rsSection = CIBlockSection::GetList(array(), array("=ID" => $SECTION_ID, "IBLOCK_ID" => $arItem["PARAM2"]));
					if($arSection = $rsSection->Fetch())
					{
						if(strlen($arSection["CODE"]) && file_exists($abs_path."iblock_section_".strtolower($arSection["CODE"]).".png"))
							$file = "iblock_section_".strtolower($arSection["CODE"]).".png";
						elseif(file_exists($abs_path."iblock_section_".strtolower($arSection["ID"]).".png"))
							$file = "iblock_section_".strtolower($arSection["ID"]).".png";
					}
				}

				if(!$file && preg_match("/\\.([a-z]+?)$/i", $arItem["TITLE"], $match))
				{
					if(file_exists($abs_path."iblock_type_".strtolower($arIBlocks[$arItem["PARAM2"]]["IBLOCK_TYPE_ID"])."_".$match[1].".png"))
						$file = "iblock_type_".strtolower($arIBlocks[$arItem["PARAM2"]]["IBLOCK_TYPE_ID"])."_".$match[1].".png";
				}

				if(!$file)
				{
					if(strlen($arIBlocks[$arItem["PARAM2"]]["CODE"]) && file_exists($abs_path."iblock_iblock_".strtolower($arIBlocks[$arItem["PARAM2"]]["CODE"]).".png"))
						$file = "iblock_iblock_".strtolower($arIBlocks[$arItem["PARAM2"]]["CODE"]).".png";
					elseif(file_exists($abs_path."iblock_iblock_".strtolower($arIBlocks[$arItem["PARAM2"]]["ID"]).".png"))
						$file = "iblock_iblock_".strtolower($arIBlocks[$arItem["PARAM2"]]["ID"]).".png";
				}

				if(!$file)
					$file = substr($arItem["ITEM_ID"], 0, 1) !== "S" ? "iblock_element.png" : "iblock_section.png";
			}
			break;
		case "main":
			$ext = end(explode('.', $arItem["ITEM_ID"]));
			if(file_exists($abs_path."main_".strtolower($ext).".png"))
				$file = "main_".strtolower($ext).".png";
			break;
	}

	if(!$file)
		$file = file_exists($abs_path.$arItem["MODULE_ID"]."_default.png") ? $arItem["MODULE_ID"]."_default.png" : "default.png";

	$arResult["SEARCH"][$i]["ICON"] = $image_path.$file;
}

?>