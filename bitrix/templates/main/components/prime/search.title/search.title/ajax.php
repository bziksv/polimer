<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$sections = $arResult['SEARCH_SECTIONS'] ?? array();
$products = $arResult['SEARCH_PRODUCTS'] ?? array();
$hasSections = !empty($sections);
$hasProducts = !empty($products);

$query = htmlspecialcharsbx($arResult['query'] ?? $_POST['q'] ?? '');

if(!$hasSections && !$hasProducts)
{
?>
<div class="polimer-search-dropdown polimer-search-dropdown--empty" data-query="<?=$query?>">
	<div class="polimer-search-dropdown__empty-state">
		По запросу «<?=$query?>» ничего не найдено
	</div>
</div>
<?
	return;
}
$correctedQuery = !empty($arResult['SEARCH_QUERY_CORRECTED'])
	? htmlspecialcharsbx($arResult['SEARCH_QUERY_CORRECTED'])
	: '';
$searchAllUrl = $arResult['SEARCH_ALL']['URL'] ?? '';
$searchAllName = $arResult['SEARCH_ALL']['NAME'] ?: 'Все результаты';
?>
<div class="polimer-search-dropdown" data-query="<?=$query?>"<?if($correctedQuery):?> data-query-corrected="<?=$correctedQuery?>"<?endif?>>
	<?if($correctedQuery && mb_strtolower($correctedQuery) !== mb_strtolower($query)):?>
	<div class="polimer-search-dropdown__correction">
		Исправлено: показаны результаты по запросу «<?=$correctedQuery?>»
	</div>
	<?endif?>
	<div class="polimer-search-dropdown__cols<?=(!$hasSections || !$hasProducts) ? ' polimer-search-dropdown__cols--single' : ''?>">
		<?if($hasSections):?>
		<div class="polimer-search-dropdown__sections">
			<div class="polimer-search-dropdown__heading">
				<span class="polimer-search-dropdown__heading-text">Категории</span>
				<span class="polimer-search-dropdown__hint">нажмите, чтобы отфильтровать</span>
			</div>
			<ul class="polimer-search-dropdown__list">
				<?foreach($sections as $arSection):
					$sectionId = (int)$arSection['ID'];
					$sectionName = htmlspecialcharsbx($arSection['NAME']);
					$sectionCount = (int)$arSection['COUNT'];
					$sectionTotal = (int)($arSection['TOTAL'] ?? 0);
					$sectionMeta = $sectionCount . ' шт.';
					if ($sectionTotal > $sectionCount)
						$sectionMeta .= ' из ' . $sectionTotal;
					$sectionFilterUrl = $searchAllUrl;
					if ($sectionFilterUrl && $query)
					{
						$sectionFilterUrl = CHTTP::urlAddParams(
							$arSection['URL'],
							['q' => $arResult['query'] ?? ''],
							['encode' => true]
						);
					}
				?>
				<li class="polimer-search-item polimer-search-item--section" data-section-id="<?=$sectionId?>">
					<div class="polimer-search-dropdown__section-row">
						<button type="button"
							class="polimer-search-dropdown__section-filter"
							data-section-id="<?=$sectionId?>"
							data-section-name="<?=$sectionName?>"
							data-section-url="<?=htmlspecialcharsbx($sectionFilterUrl)?>"
							title="Показать товары из «<?=$sectionName?>»">
							<span class="polimer-search-dropdown__thumb">
								<img src="<?=$arSection['PICTURE']?>" alt="" width="48" height="48" loading="lazy">
							</span>
							<span class="polimer-search-dropdown__info">
								<span class="polimer-search-dropdown__name"><?=$sectionName?></span>
								<span class="polimer-search-dropdown__meta"><?=$sectionMeta?></span>
							</span>
						</button>
						<a class="polimer-search-dropdown__section-go"
							href="<?=$arSection['URL']?>"
							title="Перейти в раздел «<?=$sectionName?>»">
							<i class="fa fa-external-link" aria-hidden="true"></i>
						</a>
					</div>
				</li>
				<?endforeach;?>
			</ul>
		</div>
		<?endif?>

		<?if($hasProducts):?>
		<div class="polimer-search-dropdown__products">
			<div class="polimer-search-dropdown__products-head">
				<div class="polimer-search-dropdown__heading polimer-search-dropdown__heading--products">
					<span class="polimer-search-dropdown__heading-text">Товары</span>
					<?php
					$shownProducts = count($products);
					$totalProducts = (int)($arResult['SEARCH_PRODUCTS_TOTAL'] ?? $shownProducts);
					?>
					<span class="polimer-search-dropdown__products-count" data-total="<?=$totalProducts?>">
						<?=$shownProducts?><?if($totalProducts > $shownProducts):?> из <?=$totalProducts?><?endif?>
					</span>
				</div>
				<div class="polimer-search-dropdown__filter-bar" hidden>
					<button type="button" class="polimer-search-dropdown__filter-chip">
						<span class="polimer-search-dropdown__filter-chip-label"></span>
						<span class="polimer-search-dropdown__filter-clear" title="Сбросить фильтр">&times;</span>
					</button>
				</div>
			</div>
			<ul class="polimer-search-dropdown__list polimer-search-dropdown__list--products">
				<?foreach($products as $arItem):
					$stockStatus = $arItem['STOCK_STATUS'] ?? (!empty($arItem['CAN_BUY']) ? 'available' : 'unavailable');
					$productUrl = (string)($arItem['URL'] ?? '');
					$productPath = $productUrl !== '' ? parse_url($productUrl, PHP_URL_PATH) : '';
				?>
				<li class="polimer-search-item polimer-search-item--product polimer-search-item--<?=htmlspecialcharsbx($stockStatus)?>"
					data-section-id="<?=(int)$arItem['SECTION_ID']?>"
					data-stock-status="<?=htmlspecialcharsbx($stockStatus)?>">
					<div class="polimer-search-dropdown__product-row">
						<a class="polimer-search-dropdown__product-link" href="<?=$arItem['URL']?>">
							<span class="polimer-search-dropdown__thumb">
								<img src="<?=$arItem['PICTURE']?>" alt="" width="56" height="56" loading="lazy">
							</span>
							<span class="polimer-search-dropdown__info">
								<span class="polimer-search-dropdown__name"><?=$arItem['NAME']?></span>
								<?if(!empty($arItem['SPECS'])):?>
								<span class="polimer-search-dropdown__specs"><?=htmlspecialcharsbx($arItem['SPECS'])?></span>
								<?endif?>
								<span class="polimer-search-dropdown__meta-row">
									<?if($arItem['FORMAT_INT']):?>
									<span class="polimer-search-dropdown__price"><?=$arItem['FORMAT_INT']?></span>
									<?endif?>
									<?if($stockStatus === 'order'):?>
									<span class="polimer-search-dropdown__stock polimer-search-dropdown__stock--order">Под заказ</span>
									<?elseif($stockStatus === 'unavailable'):?>
									<span class="polimer-search-dropdown__stock polimer-search-dropdown__stock--out">Нет в наличии</span>
									<?endif?>
								</span>
							</span>
						</a>
						<div class="polimer-search-dropdown__actions">
							<?if($stockStatus === 'available'):?>
							<button type="button"
								class="polimer-search-dropdown__action polimer-search-dropdown__action--cart"
								title="В корзину"
								onclick="addToBasket2(<?=(int)$arItem['ELEMENT_ID']?>, 1, this);">
								<i class="fa fa-shopping-cart" aria-hidden="true"></i>
							</button>
							<?elseif($stockStatus === 'order'):?>
							<button type="button"
								class="polimer-search-dropdown__action polimer-search-dropdown__action--order show-popup"
								data-id="order-product"
								data-product-url="<?=htmlspecialcharsbx($productPath)?>"
								data-product-name="<?=htmlspecialcharsbx($arItem['NAME'])?>"
								title="Под заказ">
								<i class="fa fa-clock-o" aria-hidden="true"></i>
							</button>
							<?else:?>
							<span class="polimer-search-dropdown__action polimer-search-dropdown__action--stock"
								title="Нет в наличии">
								<i class="fa fa-ban" aria-hidden="true"></i>
							</span>
							<?endif?>
							<?if(!empty($arItem['IN_COMPARE'])):?>
							<a href="/catalog/compare/"
								class="polimer-search-dropdown__action polimer-search-dropdown__action--compare is-active"
								title="Перейти в сравнение">
								<i class="fa fa-bar-chart" aria-hidden="true"></i>
							</a>
							<?else:?>
							<button type="button"
								class="polimer-search-dropdown__action polimer-search-dropdown__action--compare"
								title="Сравнить"
								data-product-id="<?=(int)$arItem['ELEMENT_ID']?>"
								data-section-id="<?=(int)$arItem['SECTION_ID']?>">
								<i class="fa fa-bar-chart" aria-hidden="true"></i>
							</button>
							<?endif?>
						</div>
					</div>
				</li>
				<?endforeach;?>
			</ul>
			<div class="polimer-search-dropdown__empty" hidden>
				В этой категории нет товаров по текущему запросу
			</div>
		</div>
		<?endif?>
	</div>

	<?if($searchAllUrl):?>
	<div class="polimer-search-dropdown__footer">
		<a class="polimer-search-dropdown__all"
			href="<?=$searchAllUrl?>"
			data-url-all="<?=htmlspecialcharsbx($searchAllUrl)?>"
			data-label-all="<?=htmlspecialcharsbx($searchAllName)?>">
			<span class="polimer-search-dropdown__all-text"><?=$searchAllName?></span>
			<?if($correctedQuery):?> по запросу «<?=$correctedQuery?>»<?elseif($query):?> по запросу «<?=$query?>»<?endif?>
		</a>
	</div>
	<?endif?>
</div>
