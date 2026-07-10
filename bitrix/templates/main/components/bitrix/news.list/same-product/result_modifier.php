<?
use Bitrix\Main\Type\Collection;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Iblock;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */


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

$currentProductId = (int)($arParams['CURRENT_PRODUCT_ID'] ?? 0);
$similarProductIds = [];

if (!empty($arParams['SIMILAR_PRODUCT_IDS']) && is_array($arParams['SIMILAR_PRODUCT_IDS']))
{
	foreach ($arParams['SIMILAR_PRODUCT_IDS'] as $productId)
	{
		$productId = (int)$productId;
		if ($productId > 0 && $productId !== $currentProductId)
			$similarProductIds[] = $productId;
	}
}

if (!empty($similarProductIds))
{
	$itemsById = [];

	foreach ($arResult['ITEMS'] as $arItem)
	{
		$itemsById[(int)$arItem['ID']] = $arItem;
	}

	$orderedItems = [];
	foreach ($similarProductIds as $productId)
	{
		if (isset($itemsById[$productId]))
			$orderedItems[] = $itemsById[$productId];
	}

	$arResult['ITEMS'] = $orderedItems;
}
elseif ($currentProductId > 0)
{
	$arResult['ITEMS'] = array_values(array_filter(
		$arResult['ITEMS'],
		static function ($arItem) use ($currentProductId) {
			return (int)$arItem['ID'] !== $currentProductId;
		}
	));
}

foreach($arResult["ITEMS"] as &$arItem){
	if(!$arItem["PREVIEW_PICTURE"]["SRC"]){
		$arItem["PREVIEW_PICTURE"]["SRC"] = $arEmptyPreview['SRC'];
	}
}
unset($arItem);

?>



