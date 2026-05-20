<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $item
 * @var array $actualItem
 * @var array $minOffer
 * @var array $itemIds
 * @var array|null $price
 * @var float|int|null $measureRatio
 * @var bool $haveOffers
 * @var bool $showSubscribe
 * @var array $morePhoto
 * @var bool $showSlider
 * @var bool $itemHasDetailUrl
 * @var string $imgTitle
 * @var string $productTitle
 * @var string $buttonSizeClass
 * @var string $discountPositionClass
 * @var string $labelPositionClass
 * @var CatalogSectionComponent $component
 */
 
$arItem = $item;

$price = $arItem['ITEM_PRICES'][$arItem['ITEM_PRICE_SELECTED']] ?? null;

// if discount 5 percent then dont show discount.
if ($price["PERCENT"] == 5) {
	$price["PERCENT"] = 0;
	$price['PRINT_PRICE'] = $price['PRINT_BASE_PRICE'];
}
 					
$PRINT_BASE_PRICE = $price['PRINT_BASE_PRICE'];

$PRINT_PRICE = Loc::getMessage(
	'CT_BCI_TPL_MESS_PRICE_SIMPLE_MODE',
	array(
		'#PRICE#' => $price['PRINT_PRICE'],
		'#UNIT#' => $arItem['ITEM_MEASURE']['TITLE']
	)
);

$inCompare = inCompare($arItem['IBLOCK_ID'], $arItem['ID']);
?>

<div class="item" id="product_<?=$arItem['ID']?>">
	<div class="hover">
		<div class="inner">
			
			<div class="compare">
                <?php if ($inCompare): ?>
                    <label>
                        <input type="checkbox" id-cat="<?=$arItem['IBLOCK_SECTION_ID']?>" value="<?=$arItem['ID']?>" checked>
                        <span><a href="/catalog/compare/">Перейти</a></span>
                    </label>
                <?php else: ?>
                    <label>
                        <input type="checkbox" id-cat="<?=$arItem['IBLOCK_SECTION_ID']?>" value="<?=$arItem['ID']?>">
                        <span>Сравнить</span>
                    </label>
                <?php endif; ?>
			</div>
			
			<div class="close"></div>
			
			<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="pic">
			   <span>
				  <img src="<?=resizeImage($arItem['PREVIEW_PICTURE']['ID'], 150, 150)?>" alt="<?=$arItem['NAME']?>">
			   </span>
			   
			   <?php if ($price["PERCENT"] > 0): ?>
					<div class="discount">- <?=$price["PERCENT"]?> %</div>
				<?php endif; ?>
			</a>
			
			<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="title"><?=$arItem['NAME']?></a>
			
			<? if ($arItem['CATALOG_QUANTITY'] > 0): ?>
				<div class="instock">Товар в наличии </div>
			<? endif; ?>
			
			<? if ($price && $price['BASE_PRICE'] > 0): ?>
				<div class="cost">
				<?php echo $PRINT_PRICE; ?> 
				
				<?php if ($price["PERCENT"] > 0): ?>
					<span class="cost-old"><?php echo $PRINT_BASE_PRICE; ?></span>
				<?php endif; ?>
				</div>
			<? else: ?>
				<span class="noprice">Цену уточняйте у менеджера</span>
			<?php endif; ?>
			
			<? if($arItem['CATALOG_QUANTITY'] > 0 and $price['PRICE']): ?>
			
			<div class="quantity" id="count_<?=$arItem['ID']?>">
				<a class="minus na" href="#"></a>
				<input type="text" name="quantity" value="1" max="<?=$arItem['CATALOG_QUANTITY']?>" data-cost="<?=$price["PRICE"]?>"/>
				<a class="plus" href="#"></a>
			</div>
			
			<div class="cost_total"><?php echo $PRINT_PRICE; ?></div>
			
			<a href="javascript:void(0)" class="add2cart">
				<span class="txt1" onclick="if(document.body.clientWidth < 659){addToBasket2(<?=$arItem['ID']?>, $('#count_<?=$arItem['ID']?> input').val(),this)};">В корзину</span>
				<span class="txt2" onclick="addToBasket2(<?=$arItem['ID']?>, $('#count_<?=$arItem['ID']?> input').val(),this);">Добавить в корзину</span>
			</a>
			
			<span class="incode">Код товара: <?=$arItem['PROPERTIES']['CML2_TRAITS']['VALUE'][2];?></span>
			<?else:?>
			<div class="cost_total"><span></span></div>
			
			<a href="javascript:void(0)" class="add2cartOrder show-popup" data-id="order-product">Купить под заказ</a>
			
			<div class="outstock"></div>
			<? endif; ?>

		</div>
	</div>
</div>
