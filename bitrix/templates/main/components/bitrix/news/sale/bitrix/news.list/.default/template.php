<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);
$this->addExternalCss($templateFolder.'/style.css');
?>
<section class="sale-page">
	<?if(!empty($arResult["ITEMS"])):?>
	<div class="sale-page__grid">
		<?foreach($arResult["ITEMS"] as $arItem):
			if ($arItem["PROPERTIES"]["INACTIVE"]["VALUE"] == "Да") {
				continue;
			}

			$pictureId = (int)($arItem["PREVIEW_PICTURE"]["ID"] ?? 0);
			if (!$pictureId) {
				$pictureId = (int)($arItem["DETAIL_PICTURE"]["ID"] ?? 0);
			}

			$discount = $arItem["PROPERTIES"]["DISCOUTN_PERCENT"]["VALUE"]
				?: $arItem["PROPERTIES"]["DISCOUNT_PERCENT"]["VALUE"];
		?>
		<article class="sale-page__card">
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="sale-page__media">
				<?if($pictureId):?>
				<img src="<?=resizeImage($pictureId, 280, 500)?>"
				     alt="<?=$arItem["NAME"]?>"
				     loading="lazy"
				     width="280"
				     height="500">
				<?else:?>
				<span class="sale-page__media-placeholder" aria-hidden="true"></span>
				<?endif;?>

				<?if($discount):?>
				<span class="sale-page__badge">− <?=$discount?> %</span>
				<?endif;?>
			</a>

			<div class="sale-page__body">
				<h2 class="sale-page__title">
					<a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?=$arItem["NAME"]?></a>
				</h2>

				<?if($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]):?>
				<p class="sale-page__excerpt"><?=TruncateText(strip_tags($arItem["PREVIEW_TEXT"]), 120)?></p>
				<?endif;?>
			</div>
		</article>
		<?endforeach;?>
	</div>
	<?else:?>
	<p class="sale-page__empty">Сейчас нет активных акций.</p>
	<?endif;?>

	<?if($arParams["DISPLAY_BOTTOM_PAGER"] && $arResult["NAV_STRING"]):?>
	<div class="sale-page__pager">
		<?=$arResult["NAV_STRING"]?>
	</div>
	<?endif;?>
</section>
