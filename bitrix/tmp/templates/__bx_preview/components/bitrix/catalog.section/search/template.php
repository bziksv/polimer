<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;

if (!empty($arResult['ITEMS']))
{
?>
<div class="products_roll" id="products-list">
	
	<div class="pr_box cl">

		<? foreach ($arResult['ITEMS'] as $key => $arItem): 
		
			$uniqueId = $arItem['ID'].'_'.md5($this->randString().$component->getAction());
			$areaIds = $this->GetEditAreaId($uniqueId);
		
			$APPLICATION->IncludeComponent(
				"bitrix:catalog.item", 
				".default", 
				[
					"RESULT" => [
						"ITEM" => $arItem,
						"TYPE" => "card",
						"AREA_ID" => $areaIds,
					],
					"COMPONENT_TEMPLATE" => ".default",
					"COMPOSITE_FRAME_MODE" => "A",
					"COMPOSITE_FRAME_TYPE" => "AUTO"
				],
				$component,
				["HIDE_ICONS" => "Y"]
			);								
		?>
		
		<? endforeach; ?>

	</div>
	
	<div class="pr_footer cl">
		<?
		if ($arParams["DISPLAY_BOTTOM_PAGER"])
		{
			?><? echo $arResult["NAV_STRING"]; ?><?
		}
		?>				
	</div>
	
</div>
<?}?>
