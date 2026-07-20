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

$isAjax = ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["ajax_action"]) && $_POST["ajax_action"] == "Y");

$templateData = array(
	'TEMPLATE_THEME' => $this->GetFolder().'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css',
	'TEMPLATE_CLASS' => 'bx_'.$arParams['TEMPLATE_THEME']
);
$arrPropertyCode = array();
?>

<div class="compare-page cl">
	<?php if (!empty($arResult['COMPARE_SECTIONS']) && count($arResult['COMPARE_SECTIONS']) > 1): ?>
	<nav class="compare-cats" aria-label="Категории сравнения">
		<div class="compare-cats__track">
			<?php foreach ($arResult['COMPARE_SECTIONS'] as $sect):
				$isActive = ((int)$sect['ID'] === (int)$arResult['COMPARE_SECTION_ID']);
			?>
			<a class="compare-cats__tab<?= $isActive ? ' is-active' : '' ?>"
			   href="<?= $sect['URL'] ?>"
			   <?= $isActive ? 'aria-current="page"' : '' ?>>
				<span class="compare-cats__name"><?= htmlspecialcharsbx($sect['NAME']) ?></span>
				<span class="compare-cats__count"><?= (int)$sect['COUNT'] ?></span>
			</a>
			<?php endforeach; ?>
		</div>
	</nav>
	<?php endif; ?>

    <div class="action-btn">
        <a class="sortbutton<? echo (!$arResult["DIFFERENT"] ? ' current' : ''); ?>" href="<? echo $arResult['COMPARE_URL_DIFFERENT_N']; ?>" rel="nofollow"><?=GetMessage("CATALOG_ALL_CHARACTERISTICS")?></a>
        <a class="sortbutton<? echo ($arResult["DIFFERENT"] ? ' current' : ''); ?>" href="<? echo $arResult['COMPARE_URL_DIFFERENT_Y']; ?>" rel="nofollow"><?=GetMessage("CATALOG_ONLY_DIFFERENT")?></a>
    </div>

    <div class="compare-scroll-wrap">
	<p class="compare-scroll-hint">Листайте вправо, чтобы увидеть характеристики</p>
	<div class="compare-scroll-viewport">
	<button type="button" class="compare-scroll-next" aria-label="Показать характеристики">
		<svg class="compare-scroll-next__ico" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
			<path fill="currentColor" d="M6.3 3.2a1 1 0 0 1 1.4 0l5.1 5.1a1 1 0 0 1 0 1.4l-5.1 5.1a1 1 0 1 1-1.4-1.4L10.6 9 6.3 4.6a1 1 0 0 1 0-1.4z"/>
		</svg>
	</button>
	<div class="inn">
		<div class="params-name">
			<div class="values">
				<div class="val">Цена</div>
				<? if (!empty($arResult["SHOW_PROPERTIES"])):
					foreach ($arResult["SHOW_PROPERTIES"] as $code => $arProperty):

                        $showRow = true;
                        if ($arResult['DIFFERENT'])
                        {
                            $arCompare = array();
                            foreach($arResult["ITEMS"] as $arElement)
                            {
                                $arPropertyValue = $arElement["DISPLAY_PROPERTIES"][$code]["VALUE"];
                                if (is_array($arPropertyValue))
                                {
                                    sort($arPropertyValue);
                                    $arPropertyValue = implode(" / ", $arPropertyValue);
                                }
                                $arCompare[] = $arPropertyValue;
                            }
                            unset($arElement);
                            $showRow = (count(array_unique($arCompare)) > 1);
                        }

                        if($showRow):
						$arrPropertyCode[] = $code;
						?>
				            <div class="val"><?=$arProperty["NAME"]?></div>
                        <?endif;?>
				<? 	endforeach;
				endif;
				?>
				<div class="val basket">&nbsp;</div>
			</div>
		</div><!--end::params-name-->

		<div class="compare-items cl">
			<div class="items">

				<?foreach($arResult["ITEMS"] as $arElement):
					$delUrl = '?action=DELETE_FROM_COMPARE_LIST&id='.(int)$arElement['ID'];
					if (!empty($arResult['COMPARE_SECTIONS']) && count($arResult['COMPARE_SECTIONS']) > 1)
					{
						$delUrl .= '&csid='.(int)$arResult['COMPARE_SECTION_ID'];
					}
				?>
				<div class="item">
					<div class="info">
						<div class="img">
							<a href="<?=$arElement['DETAIL_PAGE_URL']?>"><img src="<?=$arElement['PREVIEW_PICTURE']['SRC']?>" alt="<?=htmlspecialcharsbx($arElement["NAME"])?>" /></a>
						</div>
						<div class="name">
							<a href="<?=$arElement['DETAIL_PAGE_URL']?>"><?=$arElement["NAME"]?></a>
						</div>
					</div>
					<div class="values">
						<div class="val"><b>
								<?
								$ar_res = CCatalogProduct::GetOptimalPrice($arElement['ID'], 1, $USER->GetUserGroupArray(), 'N');
								echo $ar_res['DISCOUNT_PRICE'];
								?>
							</b> руб</div>
						<? for($i = 0;$i < count($arrPropertyCode);$i++): ?>
						<div class="val"><?=(is_array($arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"])? implode("/ ", $arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"]): $arElement["DISPLAY_PROPERTIES"][$arrPropertyCode[$i]]["DISPLAY_VALUE"])?></div>
						<? endfor; ?>

						<div class="val val--actions">
							<a class="add2basket" href="javascript:void(0)" onclick="addToBasket2(<?=$arElement['ID']?>, 1);" title="В корзину" aria-label="В корзину">&nbsp;</a>
							<a href="<?=$delUrl?>" class="delete" title="Удалить из сравнения" aria-label="Удалить из сравнения">
								<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2.1 2.1a1 1 0 0 1 1.4 0L7 5.6l3.5-3.5a1 1 0 1 1 1.4 1.4L8.4 7l3.5 3.5a1 1 0 1 1-1.4 1.4L7 8.4l-3.5 3.5a1 1 0 1 1-1.4-1.4L5.6 7 2.1 3.5a1 1 0 0 1 0-1.4z"/></svg>
							</a>
						</div>
					</div>
				</div>
				<? endforeach; ?>

			</div>
		</div><!--end::compare-items-->
	</div>
    <!--end::inn-->
	</div>
	<!--end::compare-scroll-viewport-->
	</div>
	<!--end::compare-scroll-wrap-->

    <div class="action-btn">
        <a href="?action=DELETE_FROM_COMPARE_LIST&id=0"
           class="compare-clear-all"
           style="border-color: red;color: red;">Удалить все товары из сравнения</a>
    </div>
</div>
<!--end::compare-page-->




<script type="text/javascript">
	(function(){
		// Выравниваем высоты шапки ↔ значения товаров (и после загрузки картинок)
		function equalizeCompareRows(){
			var $headerCells = $('.compare-page .params-name .values > .val');
			var $itemRows = $('.compare-page .compare-items .item .values');
			if (!$headerCells.length || !$itemRows.length) return;

			$headerCells.css('height', '');
			$itemRows.each(function(){
				$(this).children('.val').css('height', '');
			});

			var colCount = $headerCells.length;
			for (var c = 0; c < colCount; c++) {
				var maxH = $headerCells.eq(c).outerHeight() || 0;
				$itemRows.each(function(){
					var $cell = $(this).children('.val').eq(c);
					if ($cell.length) maxH = Math.max(maxH, $cell.outerHeight());
				});
				$headerCells.eq(c).height(maxH);
				$itemRows.each(function(){
					$(this).children('.val').eq(c).height(maxH);
				});
			}
		}

		equalizeCompareRows();
		$(window).on('load resize', equalizeCompareRows);
		$('.compare-page .compare-items .item .info img').on('load', equalizeCompareRows);
	})();

	var CatalogCompareObj = new BX.Iblock.Catalog.CompareClass("bx_catalog_compare_block");

	(function(){
		var wrap = document.querySelector('.compare-scroll-wrap');
		var inn = wrap && wrap.querySelector('.inn');
		var viewport = wrap && wrap.querySelector('.compare-scroll-viewport');
		var nextBtn = wrap && wrap.querySelector('.compare-scroll-next');
		var itemsBlock = wrap && wrap.querySelector('.compare-items');
		if (!wrap || !inn) return;

		function pinned(){
			return inn.querySelectorAll('.compare-items .item .info');
		}

		function isMobile(){
			return window.matchMedia('(max-width: 1019px)').matches;
		}

		/** Центр стрелки по блоку товаров, без шапки «Цена / Вес …» */
		function positionNextBtn(){
			if (!nextBtn || !viewport || !itemsBlock || !isMobile()) {
				if (nextBtn) nextBtn.style.top = '';
				return;
			}
			var vRect = viewport.getBoundingClientRect();
			var iRect = itemsBlock.getBoundingClientRect();
			var top = (iRect.top - vRect.top) + (iRect.height / 2);
			nextBtn.style.top = Math.round(top) + 'px';
		}

		function updatePin(){
			var x = isMobile() ? inn.scrollLeft : 0;
			var tx = x ? ('translate3d(' + x + 'px,0,0)') : '';
			pinned().forEach(function(el){
				el.style.transform = tx;
			});
		}

		function updateHint(){
			if (!isMobile()) {
				wrap.classList.add('is-hint-hidden');
				wrap.classList.remove('is-scrolled', 'is-scrolled-end');
				updatePin();
				positionNextBtn();
				return;
			}
			var maxScroll = inn.scrollWidth - inn.clientWidth;
			if (maxScroll <= 8) {
				wrap.classList.add('is-hint-hidden');
				wrap.classList.remove('is-scrolled', 'is-scrolled-end');
				updatePin();
				positionNextBtn();
				return;
			}
			wrap.classList.remove('is-hint-hidden');
			wrap.classList.toggle('is-scrolled', inn.scrollLeft > 12);
			wrap.classList.toggle('is-scrolled-end', inn.scrollLeft >= maxScroll - 8);
			updatePin();
			positionNextBtn();
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function(e){
				e.preventDefault();
				inn.scrollBy({ left: Math.min(220, inn.clientWidth * 0.7), behavior: 'smooth' });
			});
		}

		inn.addEventListener('scroll', updateHint, {passive: true});
		window.addEventListener('resize', updateHint);
		if (window.ResizeObserver && itemsBlock) {
			new ResizeObserver(positionNextBtn).observe(itemsBlock);
		}
		setTimeout(updateHint, 50);
		setTimeout(positionNextBtn, 300);
	})();

	$(document).on('click', '.compare-clear-all', function(e){
		e.preventDefault();
		var url = this.getAttribute('href');
		if (!url) return;

		var go = function(){ window.location.href = url; };

		if (typeof alertify !== 'undefined' && typeof alertify.confirm === 'function') {
			try {
				var dlg = alertify.confirm(
					'Подтверждение',
					'Удалить все товары из сравнения?',
					go,
					function(){}
				);
				if (dlg && typeof dlg.set === 'function') {
					dlg.set('labels', {ok: 'Удалить', cancel: 'Отмена'});
				}
			} catch (err) {
				if (window.confirm('Удалить все товары из сравнения?')) go();
			}
			return;
		}

		if (window.confirm('Удалить все товары из сравнения?')) {
			go();
		}
	});
</script>
