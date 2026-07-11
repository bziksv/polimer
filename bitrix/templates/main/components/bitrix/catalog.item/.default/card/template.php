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

// собираем кадры hover-слайдера превью: основное фото + дополнительные
$sliderImages = [];
if (!empty($arItem['PREVIEW_PICTURE']['ID'])) {
	$sliderImages[] = resizeCatalogCardImage($arItem['PREVIEW_PICTURE']['ID'], 220, 293);
}

if (!empty($arItem['PROPERTIES']['MORE_PHOTO']['VALUE']) && is_array($arItem['PROPERTIES']['MORE_PHOTO']['VALUE'])) {
	foreach ($arItem['PROPERTIES']['MORE_PHOTO']['VALUE'] as $id) {
		$src = resizeCatalogCardImage($id, 220, 293);
		if ($src) {
			$sliderImages[] = $src;
		}
	}
}

$sliderImages = array_values(array_unique($sliderImages));
if (empty($sliderImages)) {
	$sliderImages[] = resizeImage(0, 220, 293); // no_photo fallback
}

$hasSlider = count($sliderImages) > 1;
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
			
			<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="pic<?= $hasSlider ? ' has-slider' : '' ?>">
			   <span class="pic-slides">
				  <?php foreach ($sliderImages as $i => $src): ?>
					  <img class="pic-slide<?= $i === 0 ? ' active' : '' ?>" src="<?=$src?>" alt="<?=$arItem['NAME']?>" width="220" height="293"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
				  <?php endforeach; ?>
			   </span>

			   <?php if ($hasSlider): ?>
				   <span class="pic-dots">
					   <?php foreach ($sliderImages as $i => $src): ?>
						   <i class="pic-dot<?= $i === 0 ? ' active' : '' ?>"></i>
					   <?php endforeach; ?>
				   </span>
			   <?php endif; ?>

			   <?php if ($price["PERCENT"] > 0): ?>
					<div class="discount">- <?=$price["PERCENT"]?> %</div>
				<?php endif; ?>
			</a>
			
			<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="title"><?=$arItem['NAME']?></a>

			<div class="card-meta">
			<?php if (!empty($arItem['PROPERTIES']['CML2_TRAITS']['VALUE'][2])): ?>
			<span class="incode">Код товара: <?=htmlspecialcharsbx($arItem['PROPERTIES']['CML2_TRAITS']['VALUE'][2])?></span>
			<?php else: ?>
			<span class="incode incode--empty" aria-hidden="true"></span>
			<?php endif; ?>
			
			<? if ($arItem['CATALOG_QUANTITY'] > 0): ?>
				<div class="instock">Товар в наличии </div>
			<? else: ?>
				<div class="instock instock--empty" aria-hidden="true"></div>
			<? endif; ?>
			</div>
			
			<div class="card-footer">
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
			
			<a href="javascript:void(0)" class="add2cart" title="В корзину">
				<span class="txt1">В корзину</span>
				<span class="txt2">Добавить в корзину</span>
			</a>
			<?else:?>
			<div class="cost_total"><span></span></div>
			
			<a href="javascript:void(0)" class="add2cartOrder show-popup" data-id="order-product">Купить под заказ</a>
			
			<div class="outstock"></div>
			<? endif; ?>
			</div>

		</div>
	</div>
</div>
