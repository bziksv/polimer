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

$menuId = 'cm6-' . $this->randString(6);

$rootMeta = [
	'1153' => [
		'short' => 'Инженерная сантехника',
		'accent' => '#0a7bdc',
		'glow' => 'rgba(10, 123, 220, 0.12)',
	],
	'1492' => [
		'short' => 'Строительные материалы',
		'accent' => '#0f9aa8',
		'glow' => 'rgba(15, 154, 168, 0.12)',
	],
	'1496' => [
		'short' => 'Ворота и автоматика',
		'accent' => '#5a6f8f',
		'glow' => 'rgba(90, 111, 143, 0.12)',
	],
];

if (!function_exists('catalogMenuV6FormatCount')) {
	function catalogMenuV6FormatCount($count) {
		$count = (int)$count;
		if ($count <= 0) {
			return '';
		}
		return number_format($count, 0, '', ' ');
	}
}

if (!function_exists('catalogMenuV6RenderImage')) {
	function catalogMenuV6RenderImage(array $image, $alt = '') {
		if (empty($image['SRC'])) {
			return '';
		}

		$src = htmlspecialcharsbx($image['SRC']);
		$src1x = htmlspecialcharsbx($image['SRC_1X'] ?? $image['SRC']);
		$src2x = htmlspecialcharsbx($image['SRC_2X'] ?? $image['SRC']);
		$width = (int)($image['WIDTH'] ?? 96);
		$height = (int)($image['HEIGHT'] ?? 72);

		return '<img src="' . $src1x . '" srcset="' . $src1x . ' 1x, ' . $src2x . ' 2x" width="' . $width . '" height="' . $height . '" alt="' . htmlspecialcharsbx($alt) . '" loading="lazy" decoding="async">';
	}
}

$searchItems = [];
foreach ($rootSections as $section) {
	$rootName = $section['NAME'];
	$panelId = $menuId . '-' . $section['ID'];

	if (empty($section['SECTION_1']) || !is_array($section['SECTION_1'])) {
		continue;
	}

	foreach ($section['SECTION_1'] as $sec_2) {
		$searchItems[] = [
			'name' => $sec_2['NAME'],
			'url' => $sec_2['SECTION_PAGE_URL'],
			'path' => $rootName,
			'level' => 2,
			'panelId' => $panelId,
			'image' => $sec_2['MENU_IMAGE']['SRC_1X'] ?? ($sec_2['MENU_IMAGE']['SRC'] ?? ''),
		];

		if (empty($sec_2['SECTION_2']) || !is_array($sec_2['SECTION_2'])) {
			continue;
		}

		foreach ($sec_2['SECTION_2'] as $sec_3) {
			$searchItems[] = [
				'name' => $sec_3['NAME'],
				'url' => $sec_3['SECTION_PAGE_URL'],
				'path' => $rootName . ' → ' . $sec_2['NAME'],
				'level' => 3,
				'panelId' => $panelId,
				'image' => '',
			];
		}
	}
}
?>

<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/" class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu catalog-menu-v6" data-catalog-menu-v6="<?=htmlspecialcharsbx($menuId)?>">
	<div class="catalog-menu-v6__shell">
		<aside class="catalog-menu-v6__rail" aria-label="Разделы каталога">
			<div class="catalog-menu-v6__rail-head">Каталог</div>
			<ul class="catalog-menu-v6__rail-list">
				<? foreach ($rootSections as $i => $section):
					$meta = $rootMeta[$section['ID']] ?? [
						'short' => $section['NAME'],
						'accent' => '#0a7bdc',
						'glow' => 'rgba(10, 123, 220, 0.12)',
					];
					$panelId = $menuId . '-' . $section['ID'];
				?>
				<li>
					<button type="button"
						class="catalog-menu-v6__rail-btn<?= $i === 0 ? ' is-active' : '' ?>"
						data-v6-panel="<?=htmlspecialcharsbx($panelId)?>"
						style="--cm6-accent: <?=htmlspecialcharsbx($meta['accent'])?>; --cm6-glow: <?=htmlspecialcharsbx($meta['glow'])?>">
						<span class="catalog-menu-v6__rail-thumb" aria-hidden="true">
							<?=catalogMenuV6RenderImage($section['MENU_IMAGE'] ?? [], $section['NAME'])?>
						</span>
						<span class="catalog-menu-v6__rail-text">
							<span class="catalog-menu-v6__rail-name"><?=htmlspecialcharsbx($meta['short'])?></span>
							<? if (!empty($section['ELEMENT_CNT'])): ?>
							<span class="catalog-menu-v6__rail-count"><?=catalogMenuV6FormatCount($section['ELEMENT_CNT'])?> товаров</span>
							<? endif; ?>
						</span>
					</button>
				</li>
				<? endforeach; ?>
			</ul>
			<a href="/catalog/" class="catalog-menu-v6__all-link">Весь каталог</a>
		</aside>

		<div class="catalog-menu-v6__panels">
			<div class="catalog-menu-v6__toolbar">
				<label class="catalog-menu-v6__search">
					<i class="fa fa-search catalog-menu-v6__search-icon" aria-hidden="true"></i>
					<input type="search"
						class="catalog-menu-v6__search-input"
						placeholder="Поиск по категориям"
						autocomplete="off"
						spellcheck="false"
						data-v6-search>
					<button type="button" class="catalog-menu-v6__search-clear" data-v6-search-clear hidden aria-label="Очистить поиск"><i class="fa fa-times" aria-hidden="true"></i></button>
				</label>
			</div>

			<div class="catalog-menu-v6__search-results" data-v6-search-results hidden>
				<div class="catalog-menu-v6__search-meta" data-v6-search-meta></div>
				<p class="catalog-menu-v6__search-empty" data-v6-search-empty hidden>Категории не найдены. Попробуйте другое название.</p>
				<ul class="catalog-menu-v6__search-list" data-v6-search-list></ul>
			</div>

			<? foreach ($rootSections as $i => $section):
				$meta = $rootMeta[$section['ID']] ?? [
					'accent' => '#0a7bdc',
					'glow' => 'rgba(10, 123, 220, 0.12)',
				];
				$panelId = $menuId . '-' . $section['ID'];
			?>
			<div class="catalog-menu-v6__panel<?= $i === 0 ? ' is-active' : '' ?>"
				id="<?=htmlspecialcharsbx($panelId)?>"
				data-v6-content
				style="--cm6-accent: <?=htmlspecialcharsbx($meta['accent'])?>; --cm6-glow: <?=htmlspecialcharsbx($meta['glow'])?>"
				role="region"
				aria-label="<?=htmlspecialcharsbx($section['NAME'])?>">
				<div class="catalog-menu-v6__panel-head">
					<div>
						<h3 class="catalog-menu-v6__panel-title"><?=htmlspecialcharsbx($section['NAME'])?></h3>
						<? if (!empty($section['ELEMENT_CNT'])): ?>
						<p class="catalog-menu-v6__panel-meta"><?=catalogMenuV6FormatCount($section['ELEMENT_CNT'])?> позиций в разделе</p>
						<? endif; ?>
					</div>
					<a href="<?=$section['SECTION_PAGE_URL']?>" class="catalog-menu-v6__panel-cta">Смотреть раздел</a>
				</div>

				<? if (!empty($section['SECTION_1'])): ?>
				<div class="catalog-menu-v6__grid">
					<? foreach ($section['SECTION_1'] as $sec_2): ?>
					<div class="catalog-menu-v6__col" data-v6-col data-v6-search-text="<?=htmlspecialcharsbx(mb_strtolower($sec_2['NAME']))?>">
						<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v6__col-head">
							<span class="catalog-menu-v6__col-thumb" aria-hidden="true">
								<?=catalogMenuV6RenderImage($sec_2['MENU_IMAGE'] ?? [], $sec_2['NAME'])?>
							</span>
							<span class="catalog-menu-v6__col-title">
								<span><?=htmlspecialcharsbx($sec_2['NAME'])?></span>
								<? if (!empty($sec_2['ELEMENT_CNT'])): ?>
								<em><?=catalogMenuV6FormatCount($sec_2['ELEMENT_CNT'])?></em>
								<? endif; ?>
							</span>
						</a>
						<? if (!empty($sec_2['SECTION_2'])): ?>
						<ul class="catalog-menu-v6__col-links">
							<? foreach ($sec_2['SECTION_2'] as $sec_3): ?>
							<li data-v6-search-text="<?=htmlspecialcharsbx(mb_strtolower($sec_3['NAME']))?>">
								<a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=htmlspecialcharsbx($sec_3['NAME'])?></a>
							</li>
							<? endforeach; ?>
						</ul>
						<? endif; ?>
					</div>
					<? endforeach; ?>
				</div>
				<? else: ?>
				<p class="catalog-menu-v6__empty">Подразделы скоро появятся. Перейдите в <a href="<?=$section['SECTION_PAGE_URL']?>">каталог раздела</a>.</p>
				<? endif; ?>
				<span class="catalog-menu-v6__scroll-hint" aria-hidden="true">Прокрутите вниз</span>
			</div>
			<? endforeach; ?>
		</div>
	</div>
</div>

<script type="application/json" data-v6-search-index><?=json_encode($searchItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?></script>

<script>
(function () {
	document.querySelectorAll('[data-catalog-menu-v6]').forEach(function (root) {
		var railBtns = root.querySelectorAll('[data-v6-panel]');
		var panels = root.querySelectorAll('[data-v6-content]');
		if (!railBtns.length || !panels.length) return;

		var searchInput = root.querySelector('[data-v6-search]');
		var searchClear = root.querySelector('[data-v6-search-clear]');
		var searchResults = root.querySelector('[data-v6-search-results]');
		var searchList = root.querySelector('[data-v6-search-list]');
		var searchMeta = root.querySelector('[data-v6-search-meta]');
		var searchEmpty = root.querySelector('[data-v6-search-empty]');
		var searchIndexNode = root.parentNode.querySelector('[data-v6-search-index]');
		var searchIndex = [];

		if (searchIndexNode) {
			try {
				searchIndex = JSON.parse(searchIndexNode.textContent || '[]');
			} catch (e) {
				searchIndex = [];
			}
		}

		function updatePanelScroll(panel) {
			var grid = panel.querySelector('.catalog-menu-v6__grid');
			if (!grid || root.classList.contains('is-searching')) {
				if (panel) {
					panel.classList.remove('is-scrollable', 'is-at-bottom');
				}
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
			if (root.classList.contains('is-searching')) {
				return;
			}

			railBtns.forEach(function (btn) {
				btn.classList.toggle('is-active', btn.getAttribute('data-v6-panel') === panelId);
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

		function normalizeSearchValue(value) {
			return (value || '').toLowerCase().replace(/ё/g, 'е').trim();
		}

		function escapeHtml(value) {
			return String(value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function highlightMatch(text, query) {
			var source = normalizeSearchValue(text);
			var index = source.indexOf(query);
			if (!query || index === -1) {
				return escapeHtml(text);
			}

			return escapeHtml(text.slice(0, index))
				+ '<mark>' + escapeHtml(text.slice(index, index + query.length)) + '</mark>'
				+ escapeHtml(text.slice(index + query.length));
		}

		function filterLocalCols(query) {
			panels.forEach(function (panel) {
				var cols = panel.querySelectorAll('[data-v6-col]');
				cols.forEach(function (col) {
					var colText = normalizeSearchValue(col.getAttribute('data-v6-search-text') || '');
					var links = col.querySelectorAll('[data-v6-search-text]');
					var colMatch = query && colText.indexOf(query) !== -1;
					var visibleLinks = 0;

					links.forEach(function (linkItem) {
						var linkText = normalizeSearchValue(linkItem.getAttribute('data-v6-search-text') || '');
						var linkMatch = query && linkText.indexOf(query) !== -1;
						linkItem.hidden = !!(query && !colMatch && !linkMatch);
						if (!linkItem.hidden) {
							visibleLinks++;
						}
					});

					col.hidden = !!(query && !colMatch && visibleLinks === 0);
				});
			});
		}

		function renderSearchResults(query) {
			if (!searchResults || !searchList) {
				return;
			}

			var matches = searchIndex.filter(function (item) {
				return normalizeSearchValue(item.name).indexOf(query) !== -1
					|| normalizeSearchValue(item.path).indexOf(query) !== -1;
			});

			searchList.innerHTML = '';

			if (!matches.length) {
				if (searchEmpty) searchEmpty.hidden = false;
				if (searchMeta) searchMeta.textContent = '';
				return;
			}

			if (searchEmpty) searchEmpty.hidden = true;
			if (searchMeta) {
				searchMeta.textContent = 'Найдено: ' + matches.length;
			}

			matches.slice(0, 40).forEach(function (item) {
				var li = document.createElement('li');
				li.className = 'catalog-menu-v6__search-item';

				var link = document.createElement('a');
				link.className = 'catalog-menu-v6__search-link';
				link.href = item.url;

				if (item.image) {
					var thumb = document.createElement('span');
					thumb.className = 'catalog-menu-v6__search-thumb';
					thumb.innerHTML = '<img src="' + escapeHtml(item.image) + '" alt="" loading="lazy" decoding="async">';
					link.appendChild(thumb);
				}

				var body = document.createElement('span');
				body.className = 'catalog-menu-v6__search-body';
				body.innerHTML = '<span class="catalog-menu-v6__search-name">' + highlightMatch(item.name, query) + '</span>'
					+ '<span class="catalog-menu-v6__search-path">' + highlightMatch(item.path, query) + '</span>';
				link.appendChild(body);

				li.appendChild(link);
				searchList.appendChild(li);
			});

			if (matches.length > 40 && searchMeta) {
				searchMeta.textContent = 'Найдено: ' + matches.length + ' · показаны первые 40';
			}
		}

		function setSearchState(query) {
			var hasQuery = query.length >= 2;

			root.classList.toggle('is-searching', hasQuery);
			if (searchResults) searchResults.hidden = !hasQuery;
			if (searchClear) searchClear.hidden = !query;

			if (!hasQuery) {
				if (searchList) searchList.innerHTML = '';
				if (searchEmpty) searchEmpty.hidden = true;
				if (searchMeta) searchMeta.textContent = '';
				panels.forEach(function (panel) {
					panel.querySelectorAll('[data-v6-col], [data-v6-search-text]').forEach(function (node) {
						node.hidden = false;
					});
				});
				updateAllPanels();
				return;
			}

			filterLocalCols(query);
			renderSearchResults(query);
		}

		panels.forEach(function (panel) {
			var grid = panel.querySelector('.catalog-menu-v6__grid');
			if (!grid) return;
			grid.addEventListener('scroll', function () {
				updatePanelScroll(panel);
			}, { passive: true });
		});

		window.addEventListener('resize', updateAllPanels);

		railBtns.forEach(function (btn) {
			btn.addEventListener('mouseenter', function () {
				activate(btn.getAttribute('data-v6-panel'));
			});
			btn.addEventListener('focus', function () {
				activate(btn.getAttribute('data-v6-panel'));
			});
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				activate(btn.getAttribute('data-v6-panel'));
			});
		});

		if (searchInput) {
			searchInput.addEventListener('input', function () {
				setSearchState(normalizeSearchValue(searchInput.value));
			});

			searchInput.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					searchInput.value = '';
					setSearchState('');
					searchInput.blur();
				}
			});
		}

		if (searchClear) {
			searchClear.addEventListener('click', function () {
				if (!searchInput) return;
				searchInput.value = '';
				setSearchState('');
				searchInput.focus();
			});
		}

		updateAllPanels();
	});
})();
</script>
