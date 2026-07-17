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
if($arResult["ITEMS"]):
?>
<div class="h2"><?=$arParams['PAGER_TITLE']?></div>
<div class="slider_product_show_all slider_product" id="mp__product__action">

	<?foreach($arResult["ITEMS"] as $arItem): ?>
	<div>
		<div class="product">
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="product__thumb">
				<img src="<?=resizeImage($arItem["PREVIEW_PICTURE"]["ID"], 150, 150)?>" alt="<?=$arItem["NAME"]?>" class="img">
			</a>
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="name"><?=$arItem["NAME"]?></a>
            <div class="product-meta">
                <?php if (!empty($arItem['SIMILAR_PRODUCT_CODE'])): ?>
                    <span class="incode">Код товара: <?=htmlspecialcharsbx($arItem['SIMILAR_PRODUCT_CODE'])?></span>
                <?php endif; ?>
                <?php if (!empty($arItem['SIMILAR_IN_STOCK'])): ?>
                    <span class="instock">Товар в наличии</span>
                <?php else: ?>
                    <span class="instock instock--order">Под заказ</span>
                <?php endif; ?>
            </div>
			<div class="price">
                <?if(price($arItem['ID'])):?>
				<span><?=price($arItem['ID']);?></span> &#8381;/<?=$arItem['PROPERTIES']['CML2_BASE_UNIT']['VALUE'];?>
                <?else:?>
                    <span>&nbsp;</span>
                <? endif; ?>
			</div>

            <? if(!empty($arItem['SIMILAR_IN_STOCK'])): ?>
                <a href="javascript:void(0)" onclick="addToBasket2(<?=$arItem['ID']?>,1,this);" class="cart">В корзину</a>
            <? else: ?>
                <a href="javascript:void(0)" class="cart show-popup" data-id="order-product">под заказ</a>
            <? endif; ?>
		</div>
	</div>
	<? endforeach; ?>


</div><!-- end::slider_product -->
<? endif; ?>
