<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);
$this->addExternalCss($templateFolder.'/style.css');
?>
<div class="sale-list">
	<div class="h1">Другие <?=$arParams["PAGER_TITLE"]?></div>
	<div class="sale-list__items">
		<?foreach($arResult["ITEMS"] as $arItem):
			$pictureId = (int)($arItem['PREVIEW_PICTURE']['ID'] ?? 0);
			if (!$pictureId) {
				$pictureId = (int)($arItem['DETAIL_PICTURE']['ID'] ?? 0);
			}
		?>
		<article class="sale-list__item">
			<?if($pictureId):?>
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="sale-list__media">
				<img src="<?=resizeImage($pictureId, 560, 1000)?>"
				     alt="<?=$arItem["NAME"]?>"
				     loading="lazy"
				     width="280"
				     height="500">
			</a>
			<?endif;?>

			<div class="sale-list__content">
				<?if($arParams["DISPLAY_DATE"] != "N" && $arItem["DISPLAY_ACTIVE_FROM"]):?>
				<div class="sale-list__date"><?=$arItem["DISPLAY_ACTIVE_FROM"]?></div>
				<?endif;?>

				<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="sale-list__title"><?=$arItem["NAME"]?></a>

				<?if($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]):?>
				<div class="sale-list__text"><?=$arItem["PREVIEW_TEXT"]?></div>
				<?endif;?>
			</div>
		</article>
		<?endforeach;?>
	</div>
	<a href="/<?=$arParams["LINK_TITLE"]?>/" class="sale-list__archive">Все <?=$arParams["PAGER_TITLE"]?></a>
</div>
