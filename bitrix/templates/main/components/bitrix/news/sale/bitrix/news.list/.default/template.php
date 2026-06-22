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
?>

<div class="products_roll sale">
			
	<div class="pr_box cl">
		<?
		foreach($arResult["ITEMS"] as $arItem): 
		if ($arItem["PROPERTIES"]["INACTIVE"]["VALUE"] == "Да") {
			continue;
		}
		?>
		<a href="<?echo $arItem["DETAIL_PAGE_URL"]?>" class="item">
			<div class="hover" style="background-image: url('<?=resizeImage($arItem['PREVIEW_PICTURE']['ID'], 220, 540)?>')">
				<div class="inner">
					<? if ($arItem["PROPERTIES"]["DISCOUTN_PERCENT"]["VALUE"]): ?>
						<div class="discount">- <?echo $arItem["PROPERTIES"]["DISCOUTN_PERCENT"]["VALUE"]?> %</div>
					<? endif; ?>
					<div class="information">
						<div class="duration">Срок акции с <?echo $arItem["DISPLAY_ACTIVE_FROM"]?></div>
						<div class="name"><?echo $arItem["NAME"]?></div>
					</div>				
				</div>
			</div>
		</a>
		<?endforeach;?>			
	</div>
	
	<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
		<br /><?=$arResult["NAV_STRING"]?>
	<?endif;?>
</div>


