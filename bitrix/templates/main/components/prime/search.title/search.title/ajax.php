<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Разрешаем только относительные и http(s) URL — режем javascript:/data: и пр.
 */
$polimerSearchSafeHref = static function ($url) {
	$url = trim((string)$url);
	if ($url === '')
		return '#';

	if (preg_match('#^(?:javascript|data|vbscript|file):#i', $url))
		return '#';

	if (preg_match('#^(?:/|https?://)#i', $url))
		return $url;

	return '#';
};

$polimerSearchSafeSrc = static function ($src) use ($polimerSearchSafeHref) {
	$src = trim((string)$src);
	if ($src === '')
		return '/bitrix/templates/main/img/no_photo.png';

	$safe = $polimerSearchSafeHref($src);
	return $safe === '#' ? '/bitrix/templates/main/img/no_photo.png' : $safe;
};

/** Имя с подсветкой Bitrix (<b>): экранируем текст, оставляем только <b></b> */
$polimerSearchSafeNameHtml = static function ($name) {
	$parts = preg_split('#(</?b>)#iu', (string)$name, -1, PREG_SPLIT_DELIM_CAPTURE);
	if ($parts === false)
		return htmlspecialcharsbx((string)$name);

	$html = '';
	foreach ($parts as $part)
	{
		$lower = mb_strtolower($part);
		if ($lower === '<b>')
			$html .= '<b>';
		elseif ($lower === '</b>')
			$html .= '</b>';
		else
			$html .= htmlspecialcharsbx(strip_tags($part));
	}

	return $html;
};

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
$searchAllUrl = $polimerSearchSafeHref($arResult['SEARCH_ALL']['URL'] ?? '');
$searchAllName = htmlspecialcharsbx(
	!empty($arResult['SEARCH_ALL']['NAME']) ? $arResult['SEARCH_ALL']['NAME'] : 'Все результаты'
);
$rawQuery = trim((string)($arResult['SEARCH_QUERY_CORRECTED'] ?? $arResult['query'] ?? $_POST['q'] ?? ''));
if (($searchAllUrl === '' || $searchAllUrl === '#') && $rawQuery !== '')
{
	$searchAllUrl = $polimerSearchSafeHref(CHTTP::urlAddParams(
		'/search/',
		['q' => $rawQuery, 's' => 'Поиск'],
		['encode' => true]
	));
}
$shownProducts = count($products);
?>
<div class="polimer-search-dropdown" data-query="<?=$query?>"<?if($correctedQuery):?> data-query-corrected="<?=$correctedQuery?>"<?endif?>>
	<?if($correctedQuery && mb_strtolower($correctedQuery) !== mb_strtolower($query)):?>
	<div class="polimer-search-dropdown__correction">
		<?if(!empty($arResult['SEARCH_QUERY_RELAXED'])):?>
		Точных совпадений по «<?=$query?>» нет. Показаны ближайшие результаты по «<?=$correctedQuery?>»
		<?else:?>
		Исправлено: показаны результаты по запросу «<?=$correctedQuery?>»
		<?endif?>
	</div>
	<?endif?>
	<div class="polimer-search-dropdown__cols<?=(!$hasSections || !$hasProducts) ? ' polimer-search-dropdown__cols--single' : ''?>">
		<?if($hasSections):?>
		<div class="polimer-search-dropdown__sections">
			<div class="polimer-search-dropdown__heading">
				<span class="polimer-search-dropdown__heading-text">Категории</span>
				<span class="polimer-search-dropdown__hint">можно выбрать несколько категорий</span>
			</div>
			<ul class="polimer-search-dropdown__list">
				<?foreach($sections as $arSection):
					$sectionId = (int)$arSection['ID'];
					$sectionName = htmlspecialcharsbx($arSection['NAME'] ?? '');
					$sectionCount = (int)$arSection['COUNT'];
					$sectionMeta = $sectionCount . ' шт.';
					$sectionUrl = $polimerSearchSafeHref($arSection['URL'] ?? '');
					$sectionPicture = htmlspecialcharsbx($polimerSearchSafeSrc($arSection['PICTURE'] ?? ''));
					$sectionFilterUrl = $searchAllUrl;
					if ($sectionFilterUrl && $sectionFilterUrl !== '#' && ($arResult['query'] ?? '') !== '')
					{
						$sectionFilterUrl = $polimerSearchSafeHref(CHTTP::urlAddParams(
							$arSection['URL'] ?? '',
							['q' => $arResult['query'] ?? ''],
							['encode' => true]
						));
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
							<span class="polimer-search-dropdown__section-check" aria-hidden="true"><i class="fa fa-check"></i></span>
							<span class="polimer-search-dropdown__thumb">
								<img src="<?=$sectionPicture?>" alt="" width="48" height="48" loading="lazy">
							</span>
							<span class="polimer-search-dropdown__info">
								<span class="polimer-search-dropdown__name"><?=$sectionName?></span>
								<span class="polimer-search-dropdown__meta"><?=$sectionMeta?></span>
							</span>
						</button>
						<a class="polimer-search-dropdown__section-go"
							href="<?=htmlspecialcharsbx($sectionUrl)?>"
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
					<span class="polimer-search-dropdown__products-count" data-total="<?=$shownProducts?>">
						<?=$shownProducts?>
					</span>
				</div>
				<div class="polimer-search-dropdown__filter-bar" hidden></div>
			</div>
			<ul class="polimer-search-dropdown__list polimer-search-dropdown__list--products">
				<?foreach($products as $arItem):
					$stockStatusRaw = (string)($arItem['STOCK_STATUS'] ?? (!empty($arItem['CAN_BUY']) ? 'available' : 'unavailable'));
					$stockStatus = in_array($stockStatusRaw, ['available', 'order', 'unavailable'], true)
						? $stockStatusRaw
						: 'unavailable';
					$productUrl = $polimerSearchSafeHref($arItem['URL'] ?? '');
					$productPath = $productUrl !== '#' ? (string)(parse_url($productUrl, PHP_URL_PATH) ?: '') : '';
					$productNameHtml = $polimerSearchSafeNameHtml($arItem['NAME'] ?? '');
					$productNameAttr = htmlspecialcharsbx(strip_tags((string)($arItem['NAME'] ?? '')));
					$productPicture = htmlspecialcharsbx($polimerSearchSafeSrc($arItem['PICTURE'] ?? ''));
					// CurrencyFormat уже отдаёт безопасный HTML (₽ как &#8381;) — не экранируем повторно
					$productPrice = (string)($arItem['FORMAT_INT'] ?? '');
				?>
				<li class="polimer-search-item polimer-search-item--product polimer-search-item--<?=htmlspecialcharsbx($stockStatus)?>"
					data-section-id="<?=(int)$arItem['SECTION_ID']?>"
					data-stock-status="<?=htmlspecialcharsbx($stockStatus)?>">
					<div class="polimer-search-dropdown__product-row">
						<a class="polimer-search-dropdown__product-link" href="<?=htmlspecialcharsbx($productUrl)?>">
							<span class="polimer-search-dropdown__thumb">
								<img src="<?=$productPicture?>" alt="" width="56" height="56" loading="lazy">
							</span>
							<span class="polimer-search-dropdown__info">
								<span class="polimer-search-dropdown__name"><?=$productNameHtml?></span>
								<?if(!empty($arItem['SPECS'])):?>
								<span class="polimer-search-dropdown__specs"><?=htmlspecialcharsbx($arItem['SPECS'])?></span>
								<?endif?>
								<span class="polimer-search-dropdown__meta-row">
									<?if($productPrice !== ''):?>
									<span class="polimer-search-dropdown__price"><?=$productPrice?></span>
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
								data-product-name="<?=$productNameAttr?>"
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

	<?if($searchAllUrl && $searchAllUrl !== '#'):?>
	<div class="polimer-search-dropdown__footer">
		<a class="polimer-search-dropdown__all"
			href="<?=htmlspecialcharsbx($searchAllUrl)?>"
			data-url-all="<?=htmlspecialcharsbx($searchAllUrl)?>"
			data-label-all="<?=$searchAllName?>">
			<span class="polimer-search-dropdown__all-text"><?=$searchAllName?></span>
			<?if($correctedQuery):?> по запросу «<?=$correctedQuery?>»<?elseif($query):?> по запросу «<?=$query?>»<?endif?>
		</a>
	</div>
	<?endif?>
</div>
