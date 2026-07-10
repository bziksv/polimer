<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);

$allowRootCat = ['1153', '1492', '1496'];
$rootSections = [];
foreach ($arResult['SECTIONS'] as $section) {
	if ($section["DEPTH_LEVEL"] == "1" && in_array($section['ID'], $allowRootCat) == false) {
		continue;
	}
	$rootSections[] = $section;
}

usort($rootSections, function ($a, $b) use ($allowRootCat) {
	return array_search($a['ID'], $allowRootCat) <=> array_search($b['ID'], $allowRootCat);
});

$menuId = 'cm5-' . $this->randString(6);
$maxLevel3 = 5;

$rootMeta = [
	'1153' => [
		'short' => 'Инженерная сантехника',
		'accent' => '#0060bf',
		'icon' => 'plumbing',
	],
	'1492' => [
		'short' => 'Строительные материалы',
		'accent' => '#028090',
		'icon' => 'build',
	],
	'1496' => [
		'short' => 'Ворота и автоматика',
		'accent' => '#4a5d7a',
		'icon' => 'gate',
	],
];

if (!function_exists('catalogMenuV5FormatCount')) {
	function catalogMenuV5FormatCount($count) {
		$count = (int)$count;
		if ($count <= 0) {
			return '';
		}
		return number_format($count, 0, '', ' ');
	}
}
?>

<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/" class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu catalog-menu-v5" data-catalog-menu-v5="<?=htmlspecialcharsbx($menuId)?>">
	<div class="catalog-menu-v5__shell">
		<aside class="catalog-menu-v5__rail" aria-label="Разделы каталога">
			<ul class="catalog-menu-v5__rail-list">
				<? foreach ($rootSections as $i => $section):
					$meta = $rootMeta[$section['ID']] ?? [
						'short' => $section['NAME'],
						'accent' => '#0060bf',
						'icon' => 'plumbing',
					];
					$panelId = $menuId . '-' . $section['ID'];
				?>
				<li>
					<button type="button"
						class="catalog-menu-v5__rail-btn<?= $i === 0 ? ' is-active' : '' ?>"
						data-v5-panel="<?=htmlspecialcharsbx($panelId)?>"
						style="--cm5-accent: <?=htmlspecialcharsbx($meta['accent'])?>">
						<span class="catalog-menu-v5__icon catalog-menu-v5__icon--<?=htmlspecialcharsbx($meta['icon'])?>" aria-hidden="true"></span>
						<span class="catalog-menu-v5__rail-text">
							<span class="catalog-menu-v5__rail-name"><?=htmlspecialcharsbx($meta['short'])?></span>
							<? if (!empty($section['ELEMENT_CNT'])): ?>
							<span class="catalog-menu-v5__rail-count"><?=catalogMenuV5FormatCount($section['ELEMENT_CNT'])?> товаров</span>
							<? endif; ?>
						</span>
					</button>
				</li>
				<? endforeach; ?>
			</ul>
			<a href="/catalog/" class="catalog-menu-v5__all-link">Весь каталог</a>
		</aside>

		<div class="catalog-menu-v5__panels">
			<? foreach ($rootSections as $i => $section):
				$panelId = $menuId . '-' . $section['ID'];
			?>
			<div class="catalog-menu-v5__panel<?= $i === 0 ? ' is-active' : '' ?>"
				id="<?=htmlspecialcharsbx($panelId)?>"
				data-v5-content
				role="region"
				aria-label="<?=htmlspecialcharsbx($section['NAME'])?>">
				<div class="catalog-menu-v5__panel-head">
					<div>
						<h3 class="catalog-menu-v5__panel-title"><?=htmlspecialcharsbx($section['NAME'])?></h3>
						<? if (!empty($section['ELEMENT_CNT'])): ?>
						<p class="catalog-menu-v5__panel-meta"><?=catalogMenuV5FormatCount($section['ELEMENT_CNT'])?> позиций в разделе</p>
						<? endif; ?>
					</div>
					<a href="<?=$section['SECTION_PAGE_URL']?>" class="catalog-menu-v5__panel-cta">Смотреть раздел</a>
				</div>

				<? if (!empty($section['SECTION_1'])): ?>
				<div class="catalog-menu-v5__grid">
					<? foreach ($section['SECTION_1'] as $sec_2):
						$level3 = !empty($sec_2['SECTION_2']) ? $sec_2['SECTION_2'] : [];
						$visibleLevel3 = array_slice($level3, 0, $maxLevel3);
						$hiddenLevel3 = max(0, count($level3) - $maxLevel3);
					?>
					<article class="catalog-menu-v5__card">
						<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v5__card-title">
							<span><?=htmlspecialcharsbx($sec_2['NAME'])?></span>
							<? if (!empty($sec_2['ELEMENT_CNT'])): ?>
							<em><?=catalogMenuV5FormatCount($sec_2['ELEMENT_CNT'])?></em>
							<? endif; ?>
						</a>
						<? if (!empty($visibleLevel3)): ?>
						<ul class="catalog-menu-v5__card-links">
							<? foreach ($visibleLevel3 as $sec_3): ?>
							<li>
								<a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=htmlspecialcharsbx($sec_3['NAME'])?></a>
							</li>
							<? endforeach; ?>
						</ul>
						<? endif; ?>
						<? if ($hiddenLevel3 > 0): ?>
						<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v5__card-more">
							ещё <?=catalogMenuV5FormatCount($hiddenLevel3)?> →
						</a>
						<? endif; ?>
					</article>
					<? endforeach; ?>
				</div>
				<? else: ?>
				<p class="catalog-menu-v5__empty">Подразделы скоро появятся. Перейдите в <a href="<?=$section['SECTION_PAGE_URL']?>">каталог раздела</a>.</p>
				<? endif; ?>
				<span class="catalog-menu-v5__scroll-hint" aria-hidden="true">Прокрутите вниз</span>
			</div>
			<? endforeach; ?>
		</div>
	</div>
</div>

<script>
(function () {
	document.querySelectorAll('[data-catalog-menu-v5]').forEach(function (root) {
		var railBtns = root.querySelectorAll('[data-v5-panel]');
		var panels = root.querySelectorAll('[data-v5-content]');
		if (!railBtns.length || !panels.length) return;

		function updatePanelScroll(panel) {
			var grid = panel.querySelector('.catalog-menu-v5__grid');
			if (!grid) {
				panel.classList.remove('is-scrollable', 'is-at-bottom');
				return;
			}

			var canScroll = grid.scrollHeight > grid.clientHeight + 2;
			var atBottom = grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 4;

			panel.classList.toggle('is-scrollable', canScroll);
			panel.classList.toggle('is-at-bottom', !canScroll || atBottom);
		}

		function updateAllPanels() {
			panels.forEach(updatePanelScroll);
		}

		function activate(panelId) {
			railBtns.forEach(function (btn) {
				btn.classList.toggle('is-active', btn.getAttribute('data-v5-panel') === panelId);
			});
			panels.forEach(function (panel) {
				panel.classList.toggle('is-active', panel.id === panelId);
			});
			var activePanel = root.querySelector('#' + panelId);
			if (activePanel) {
				requestAnimationFrame(function () {
					updatePanelScroll(activePanel);
				});
			}
		}

		panels.forEach(function (panel) {
			var grid = panel.querySelector('.catalog-menu-v5__grid');
			if (!grid) return;
			grid.addEventListener('scroll', function () {
				updatePanelScroll(panel);
			}, { passive: true });
		});

		window.addEventListener('resize', updateAllPanels);

		railBtns.forEach(function (btn) {
			btn.addEventListener('mouseenter', function () {
				activate(btn.getAttribute('data-v5-panel'));
			});
			btn.addEventListener('focus', function () {
				activate(btn.getAttribute('data-v5-panel'));
			});
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				activate(btn.getAttribute('data-v5-panel'));
			});
		});

		updateAllPanels();
	});
})();
</script>
