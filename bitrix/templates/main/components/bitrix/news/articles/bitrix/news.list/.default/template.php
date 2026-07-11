<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);
$this->addExternalCss($templateFolder.'/style.css');
?>
<section class="articles-page">
	<header class="articles-page__header">
		<h1 class="articles-page__title"><?=$APPLICATION->GetTitle(false)?></h1>
		<p class="articles-page__lead">Полезные советы и инструкции по выбору инженерной сантехники и строительных материалов</p>
	</header>

	<?if(!empty($arResult["ITEMS"])):?>
	<div class="articles-page__grid">
		<?foreach($arResult["ITEMS"] as $arItem):
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), ["CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')]);
		?>
		<article class="articles-page__card" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
			<?if($arParams["DISPLAY_PICTURE"] != "N"):
				$pictureId = (int)($arItem["PREVIEW_PICTURE"]["ID"] ?? 0);
				if (!$pictureId) {
					$pictureId = (int)($arItem["DETAIL_PICTURE"]["ID"] ?? 0);
				}
			?>
			<?if($pictureId):?>
			<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="articles-page__media" tabindex="-1" aria-hidden="true">
				<img src="<?=resizeImage($pictureId, 230, 230)?>"
				     alt="<?=$arItem["NAME"]?>"
				     loading="lazy"
				     width="230"
				     height="230">
			</a>
			<?endif;?>
			<?endif;?>

			<div class="articles-page__body">
				<h2 class="articles-page__card-title">
					<a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?=$arItem["NAME"]?></a>
				</h2>

				<?if($arParams["DISPLAY_PREVIEW_TEXT"] != "N" && $arItem["PREVIEW_TEXT"]):?>
				<p class="articles-page__excerpt">
					<?=TruncateText(strip_tags($arItem["PREVIEW_TEXT"]), 160)?>
				</p>
				<?endif;?>

				<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="articles-page__more">Читать статью</a>
			</div>
		</article>
		<?endforeach;?>
	</div>
	<?else:?>
	<p class="articles-page__empty">Статьи пока не опубликованы.</p>
	<?endif;?>

	<?if($arParams["DISPLAY_BOTTOM_PAGER"] && $arResult["NAV_STRING"]):?>
	<div class="articles-page__pager">
		<?=$arResult["NAV_STRING"]?>
	</div>
	<?endif;?>
</section>
