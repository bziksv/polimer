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

$templateData = array(
	'TEMPLATE_THEME' => $this->GetFolder().'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css',
	'TEMPLATE_CLASS' => 'bx_'.$arParams['TEMPLATE_THEME']
);

$items = is_array($arResult['ITEMS']) ? $arResult['ITEMS'] : array();
$itemCount = count($items);
$showProps = array();

if (!empty($arResult['SHOW_PROPERTIES']) && $itemCount > 0)
{
	foreach ($arResult['SHOW_PROPERTIES'] as $code => $arProperty)
	{
		$showRow = true;
		if (!empty($arResult['DIFFERENT']))
		{
			$arCompare = array();
			foreach ($items as $arElement)
			{
				$val = $arElement['DISPLAY_PROPERTIES'][$code]['VALUE'] ?? '';
				if (is_array($val))
				{
					sort($val);
					$val = implode(' / ', $val);
				}
				$arCompare[] = (string)$val;
			}
			$showRow = (count(array_unique($arCompare)) > 1);
		}
		if ($showRow)
		{
			$showProps[$code] = $arProperty;
		}
	}
}

$csidSuffix = '';
if (!empty($arResult['COMPARE_SECTIONS']) && count($arResult['COMPARE_SECTIONS']) > 1)
{
	$csidSuffix = '&csid='.(int)$arResult['COMPARE_SECTION_ID'];
}

$formatPrice = static function ($elementId) use ($USER) {
	$price = CCatalogProduct::GetOptimalPrice((int)$elementId, 1, $USER->GetUserGroupArray(), 'N');
	if (empty($price['DISCOUNT_PRICE']))
	{
		return '';
	}
	return number_format((float)$price['DISCOUNT_PRICE'], 2, '.', ' ').' ₽';
};

$formatPropValue = static function (array $arElement, $code) {
	$display = $arElement['DISPLAY_PROPERTIES'][$code]['DISPLAY_VALUE'] ?? null;
	if ($display === null || $display === '' || $display === array())
	{
		$raw = $arElement['DISPLAY_PROPERTIES'][$code]['VALUE'] ?? '';
		if (is_array($raw))
		{
			$raw = implode('/ ', $raw);
		}
		return (string)$raw;
	}
	if (is_array($display))
	{
		return implode('/ ', $display);
	}
	return (string)$display;
};
?>

<div class="cmp" style="--cmp-n: <?= max(1, $itemCount) ?>;" id="bx_catalog_compare_block">
	<h1 class="cmp__title">Сравнение товаров</h1>

	<?php if (!empty($arResult['COMPARE_SECTIONS']) && count($arResult['COMPARE_SECTIONS']) > 1): ?>
	<nav class="cmp-cats" aria-label="Категории сравнения">
		<div class="cmp-cats__track">
			<?php foreach ($arResult['COMPARE_SECTIONS'] as $sect):
				$isActive = ((int)$sect['ID'] === (int)$arResult['COMPARE_SECTION_ID']);
			?>
			<a class="cmp-cats__tab<?= $isActive ? ' is-active' : '' ?>"
			   href="<?= htmlspecialcharsbx($sect['URL']) ?>"
			   <?= $isActive ? 'aria-current="page"' : '' ?>>
				<span class="cmp-cats__name"><?= htmlspecialcharsbx($sect['NAME']) ?></span>
				<span class="cmp-cats__count"><?= (int)$sect['COUNT'] ?></span>
			</a>
			<?php endforeach; ?>
		</div>
	</nav>
	<?php endif; ?>

	<div class="cmp-toolbar">
		<div class="cmp-toolbar__modes">
			<a class="cmp-mode<?= empty($arResult['DIFFERENT']) ? ' is-active' : '' ?>"
			   href="<?= htmlspecialcharsbx($arResult['COMPARE_URL_DIFFERENT_N']) ?>"
			   rel="nofollow">Все характеристики</a>
			<a class="cmp-mode<?= !empty($arResult['DIFFERENT']) ? ' is-active' : '' ?>"
			   href="<?= htmlspecialcharsbx($arResult['COMPARE_URL_DIFFERENT_Y']) ?>"
			   rel="nofollow">Только различия</a>
		</div>
		<a href="?action=DELETE_FROM_COMPARE_LIST&id=0<?= $csidSuffix ?>"
		   class="cmp-clear compare-clear-all"
		   rel="nofollow">
			<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
				<path fill="currentColor" d="M5.5 2h5l.5 1H14v1.5H2V3h3l.5-1zM3.5 5.5h9l-.7 8.2a1.5 1.5 0 0 1-1.5 1.3H5.7a1.5 1.5 0 0 1-1.5-1.3L3.5 5.5z"/>
			</svg>
			Очистить список
		</a>
	</div>

	<?php if ($itemCount === 0): ?>
		<p class="cmp-empty">Список сравниваемых товаров пуст.</p>
	<?php else: ?>

	<div class="cmp-board" data-cmp-board>
		<p class="cmp-scroll-hint">Листайте вправо, чтобы увидеть характеристики</p>
		<div class="cmp-pin-head">
			<div class="cmp-freeze">
				<div class="cmp-freeze__rail cmp-freeze__rail--head" aria-hidden="true"></div>
				<div class="cmp-hscroll" data-cmp-hscroll="head">
					<div class="cmp-products">
						<?php foreach ($items as $arElement):
							$delUrl = '?action=DELETE_FROM_COMPARE_LIST&id='.(int)$arElement['ID'].$csidSuffix;
							$imgSrc = $arElement['PREVIEW_PICTURE']['SRC'] ?? ($arElement['DETAIL_PICTURE']['SRC'] ?? '');
							$priceText = $formatPrice($arElement['ID']);
						?>
						<div class="cmp-card">
							<a class="cmp-card__remove" href="<?= htmlspecialcharsbx($delUrl) ?>" title="Удалить из сравнения" aria-label="Удалить из сравнения">
								<svg width="12" height="12" viewBox="0 0 14 14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2.1 2.1a1 1 0 0 1 1.4 0L7 5.6l3.5-3.5a1 1 0 1 1 1.4 1.4L8.4 7l3.5 3.5a1 1 0 1 1-1.4 1.4L7 8.4l-3.5 3.5a1 1 0 1 1-1.4-1.4L5.6 7 2.1 3.5a1 1 0 0 1 0-1.4z"/></svg>
							</a>
							<a class="cmp-card__img" href="<?= htmlspecialcharsbx($arElement['DETAIL_PAGE_URL']) ?>">
								<?php if ($imgSrc): ?>
									<img src="<?= htmlspecialcharsbx($imgSrc) ?>" alt="<?= htmlspecialcharsbx($arElement['NAME']) ?>">
								<?php endif; ?>
							</a>
							<a class="cmp-card__name" href="<?= htmlspecialcharsbx($arElement['DETAIL_PAGE_URL']) ?>"><?= htmlspecialcharsbx($arElement['NAME']) ?></a>
							<?php if ($priceText !== ''): ?>
								<div class="cmp-card__price"><?= htmlspecialcharsbx($priceText) ?></div>
							<?php endif; ?>
							<?php if (empty($showProps)): ?>
								<button type="button" class="cmp-card__cart" onclick="addToBasket2(<?= (int)$arElement['ID'] ?>, 1);" title="В корзину" aria-label="В корзину"></button>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<?php if (!empty($showProps)): ?>
		<div class="cmp-pin-body">
			<section class="cmp-section is-open">
				<button type="button" class="cmp-section__toggle" aria-expanded="true">
					<span>Характеристики</span>
					<svg class="cmp-section__chev" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4.2 6.2a1 1 0 0 1 1.4 0L8 8.6l2.4-2.4a1 1 0 1 1 1.4 1.4l-3.1 3.1a1 1 0 0 1-1.4 0L4.2 7.6a1 1 0 0 1 0-1.4z"/></svg>
				</button>
				<div class="cmp-section__body">
					<div class="cmp-freeze">
						<div class="cmp-freeze__rail">
							<?php $rowIndex = 0; foreach ($showProps as $code => $arProperty): ?>
							<div class="cmp-rail-row<?= ($rowIndex % 2 === 1) ? ' is-alt' : '' ?>" data-cmp-row="<?= $rowIndex ?>">
								<?= htmlspecialcharsbx($arProperty['NAME']) ?>
							</div>
							<?php $rowIndex++; endforeach; ?>
							<div class="cmp-rail-row cmp-rail-row--cart" data-cmp-row="<?= $rowIndex ?>">В корзину</div>
						</div>
						<div class="cmp-hscroll" data-cmp-hscroll="body">
							<?php $rowIndex = 0; foreach ($showProps as $code => $arProperty): ?>
							<div class="cmp-vals-row<?= ($rowIndex % 2 === 1) ? ' is-alt' : '' ?>" data-cmp-row="<?= $rowIndex ?>">
								<?php foreach ($items as $arElement):
									$val = $formatPropValue($arElement, $code);
								?>
								<div class="cmp-row__val"><?= $val !== '' ? htmlspecialcharsbx($val) : '—' ?></div>
								<?php endforeach; ?>
							</div>
							<?php $rowIndex++; endforeach; ?>
							<div class="cmp-vals-row cmp-vals-row--cart" data-cmp-row="<?= $rowIndex ?>">
								<?php foreach ($items as $arElement): ?>
								<div class="cmp-row__val cmp-row__val--cart">
									<button type="button" class="cmp-card__cart" onclick="addToBasket2(<?= (int)$arElement['ID'] ?>, 1);" title="В корзину" aria-label="В корзину"></button>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
		<?php else: ?>
		<p class="cmp-empty">Нет характеристик для сравнения.</p>
		<?php endif; ?>
	</div>

	<?php endif; ?>
</div>

<script type="text/javascript">
(function(){
	var root = document.getElementById('bx_catalog_compare_block');
	if (!root) return;

	var pinHead = root.querySelector('.cmp-pin-head');
	var board = root.querySelector('[data-cmp-board]');
	var headScroll = root.querySelector('[data-cmp-hscroll="head"]');
	var bodyScroll = root.querySelector('[data-cmp-hscroll="body"]');
	var syncing = false;

	function syncScroll(from, to){
		if (!from || !to || syncing) return;
		if (to.scrollLeft === from.scrollLeft) return;
		syncing = true;
		to.scrollLeft = from.scrollLeft;
		window.requestAnimationFrame(function(){ syncing = false; });
	}

	function updateScrollHint(){
		if (!board || !headScroll) return;
		if (!window.matchMedia('(max-width: 1019px)').matches) {
			board.classList.add('is-hint-hidden');
			return;
		}
		var maxScroll = headScroll.scrollWidth - headScroll.clientWidth;
		if (maxScroll <= 8) {
			board.classList.add('is-hint-hidden');
			return;
		}
		board.classList.remove('is-hint-hidden');
		board.classList.toggle('is-scrolled', headScroll.scrollLeft > 12);
	}

	if (headScroll && bodyScroll) {
		headScroll.addEventListener('scroll', function(){
			syncScroll(headScroll, bodyScroll);
			updateScrollHint();
		}, {passive: true});
		bodyScroll.addEventListener('scroll', function(){
			syncScroll(bodyScroll, headScroll);
			updateScrollHint();
		}, {passive: true});
		updateScrollHint();
		window.addEventListener('resize', updateScrollHint);
	}

	function syncRowHeights(){
		var rails = root.querySelectorAll('.cmp-rail-row[data-cmp-row]');
		var vals = root.querySelectorAll('.cmp-vals-row[data-cmp-row]');
		if (!rails.length || rails.length !== vals.length) return;
		for (var i = 0; i < rails.length; i++) {
			rails[i].style.minHeight = '';
			vals[i].style.minHeight = '';
		}
		for (var j = 0; j < rails.length; j++) {
			var h = Math.max(rails[j].offsetHeight, vals[j].offsetHeight);
			rails[j].style.minHeight = h + 'px';
			vals[j].style.minHeight = h + 'px';
		}
	}

	syncRowHeights();
	window.addEventListener('resize', syncRowHeights);

	if (pinHead) {
		var stickyTop = parseFloat(window.getComputedStyle(pinHead).top) || 64;
		var onScroll = function(){
			var y = pinHead.getBoundingClientRect().top;
			root.classList.toggle('is-stuck', y <= stickyTop + 1);
		};
		window.addEventListener('scroll', onScroll, {passive: true});
		window.addEventListener('resize', function(){
			stickyTop = parseFloat(window.getComputedStyle(pinHead).top) || 64;
			onScroll();
		});
		onScroll();
	}

	root.querySelectorAll('.cmp-section__toggle').forEach(function(btn){
		btn.addEventListener('click', function(){
			var section = btn.closest('.cmp-section');
			if (!section) return;
			var open = section.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				window.requestAnimationFrame(syncRowHeights);
			}
		});
	});

	if (typeof BX !== 'undefined' && BX.Iblock && BX.Iblock.Catalog && BX.Iblock.Catalog.CompareClass) {
		try { new BX.Iblock.Catalog.CompareClass('bx_catalog_compare_block'); } catch (e) {}
	}

	$(document).on('click', '.compare-clear-all', function(e){
		e.preventDefault();
		var url = this.getAttribute('href');
		if (!url) return;
		var go = function(){ window.location.href = url; };
		if (typeof alertify !== 'undefined' && typeof alertify.confirm === 'function') {
			try {
				var dlg = alertify.confirm('Подтверждение', 'Удалить все товары из сравнения?', go, function(){});
				if (dlg && typeof dlg.set === 'function') {
					dlg.set('labels', {ok: 'Удалить', cancel: 'Отмена'});
				}
			} catch (err) {
				if (window.confirm('Удалить все товары из сравнения?')) go();
			}
			return;
		}
		if (window.confirm('Удалить все товары из сравнения?')) go();
	});
})();
</script>
