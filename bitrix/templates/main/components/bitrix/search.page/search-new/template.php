<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Iblock;
use Bitrix\Catalog\PriceTable;
?>

<div class="row cl">

    <div class="ct__content">

        <?if(count($arResult["SEARCH"])>0):?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?> "<?=$arResult['REQUEST']['QUERY']?>" найдено <?=$arResult['ROWS_COUNT'];?> шт.</div>
        <? else:?>
            <div class="h1"><? $APPLICATION->ShowTitle(false, false); ?></div>
        <?endif;?>

        <?if($arResult['SECTIONS']):?>
            <div class="h1">Категория</div>

            <div class="product_top cl">

                <div class="catalog_top cl">
                    <? foreach($arResult['SECTIONS'] as &$arSection):?>
                        <div class="item_c">
                            <a href="<?=$arSection['SECTION_PAGE_URL']?>">
                                <div class="img_c">
                                    <img src="<?=resizeImage($arSection['PICTURE'], 140, 120);?>" alt="<?=$arSection['NAME']?>">
                                </div>
                                <div class="name_c"><?=$arSection['NAME']?></div>
                            </a>
                        </div>
                    <? endforeach; ?>
                </div>

            </div>
            <!--end::catalog-sections-->
        <? endif; ?>

        <div class="h1">Товары</div>
		
		<?
		$IBLOCK_ID = 0;
		$IBLOCK_TYPE = "";
		$PRODUCT_IDS = [0];
		
		if (count($arResult["SEARCH"]) > 0) {
			foreach ($arResult['SEARCH'] as $key => $arItem) {
				$PRODUCT_IDS[] = $arItem["ID"];
				
				if (! $IBLOCK_ID || ! $IBLOCK_TYPE) {
					$IBLOCK_ID = $arItem["IBLOCK_ID"];
					$IBLOCK_TYPE = $arItem["IBLOCK_TYPE_ID"];
				}
			}
		}
		?>
		
		<?php 
		$GLOBALS["arrFilter"] = ["ID" => $PRODUCT_IDS];
		
		$APPLICATION->IncludeComponent(
            "bitrix:catalog.section",
            "search",
            array(
				"USE_FILTER" => "Y",
				"FILTER_NAME" => "arrFilter",
                "IBLOCK_TYPE" => $IBLOCK_TYPE,
                "IBLOCK_ID" => $IBLOCK_ID,
                "PRICE_CODE" => ["РОЗНИЦА", "КРУПНЫЙ_ОПТ", "СПЕЦЦЕНА", "ЦЕНА_МОНТАЖНИКА"],
                "USE_MAIN_ELEMENT_SECTION" => "Y",
				"SECTION_USER_FIELDS" => [],
				"PAGE_ELEMENT_COUNT" => $arParams["PAGE_RESULT_COUNT"],
            ),
            false
        );
		?>

        <div class="products_roll">
            <?if(count($arResult["SEARCH"])>0):?>

                <div class="pr_footer cl">
                    <?
                    if ($arParams["DISPLAY_BOTTOM_PAGER"])
                    {
                        ?><? echo $arResult["NAV_STRING"]; ?><?
                    }
                    ?>
                </div>

            <?else:?>
                <?ShowNote(GetMessage("SEARCH_NOTHING_TO_FOUND"));?>
            <?endif;?>
        </div>
        <!--end::products_roll-->
    </div>
    <div class="ct__mask"></div>
</div>

