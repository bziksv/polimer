<?php
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_PUBLIC_MODE', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/polimer_catalog_image_audit.php';

if (!PolimerCatalogImageAudit::isAllowed()) {
	header('HTTP/1.1 403 Forbidden');
	header('Content-Type: text/html; charset=utf-8');
	echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Access denied</title></head><body>';
	echo '<h1>Access denied</h1>';
	echo '<p>' . htmlspecialchars(PolimerCatalogImageAudit::getAccessDeniedReason()) . '</p>';
	echo '</body></html>';
	exit;
}

$format = (string)($_GET['format'] ?? 'html');
$priority = (string)($_GET['priority'] ?? '');
$minScore = max(0, (int)($_GET['min_score'] ?? 450));
$q = trim((string)($_GET['q'] ?? ''));
$refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$limit = max(1, min(500, (int)($_GET['limit'] ?? 200)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$selectedSections = PolimerCatalogImageAudit::parseSectionFilterFromRequest();
$expandedSections = PolimerCatalogImageAudit::expandSectionFilterIds($selectedSections);
$sectionOptions = PolimerCatalogImageAudit::getSectionFilterOptions();

$building = PolimerCatalogImageAudit::isBuilding();
$buildingStarted = false;

if ($refresh) {
	$buildingStarted = PolimerCatalogImageAudit::startBackgroundBuild();
	if ($buildingStarted) {
		$building = true;
	}
}

$report = PolimerCatalogImageAudit::loadCache(true);

if (!$report) {
	if ($format === 'json') {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code($building ? 503 : 404);
		echo json_encode([
			'status' => $building ? 'building' : 'missing',
			'message' => $building
				? 'Отчёт собирается в фоне. Обновите страницу через 3–5 минут.'
				: 'Кэш не найден. Откройте страницу с ?refresh=1 для запуска сборки.',
			'building' => $building,
			'log_tail' => PolimerCatalogImageAudit::getBuildLogTail(),
		], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}

	$baseUrl = '/tools/catalog-image-audit.php';
	$refreshUrl = $baseUrl . '?refresh=1';
	$logTail = PolimerCatalogImageAudit::getBuildLogTail();
	header('Content-Type: text/html; charset=utf-8');
	?>
	<!doctype html>
	<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="refresh" content="15">
		<title>Аудит картинок каталога</title>
		<style>
			body { margin: 0; font: 14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f7fb; color: #1f2937; }
			.wrap { max-width: 760px; margin: 40px auto; padding: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; }
			h1 { margin: 0 0 12px; font-size: 24px; }
			p { margin: 0 0 12px; }
			a.btn { display: inline-block; margin-top: 8px; padding: 10px 16px; background: #056ac8; color: #fff; text-decoration: none; border-radius: 8px; }
			pre { background: #111827; color: #e5e7eb; padding: 12px; border-radius: 8px; overflow: auto; font-size: 12px; white-space: pre-wrap; }
			.note { color: #6b7280; }
		</style>
	</head>
	<body>
	<div class="wrap">
		<h1>Аудит картинок каталога</h1>
		<?php if ($building): ?>
			<p><b>Отчёт собирается.</b> Это ~12&nbsp;000 товаров, занимает 3–5 минут. Страница обновится сама.</p>
			<p class="note">504 больше не будет: сборка идёт в фоне через CLI, не через nginx.</p>
		<?php else: ?>
			<p>Кэш отчёта ещё не собран на этом сервере.</p>
			<a class="btn" href="<?=htmlspecialchars($refreshUrl)?>">Запустить сборку</a>
		<?php endif; ?>
		<?php if ($logTail !== ''): ?>
			<p class="note">Лог сборки:</p>
			<pre><?=htmlspecialchars($logTail)?></pre>
		<?php endif; ?>
	</div>
	</body>
	</html>
	<?php
	exit;
}

$items = $report['items'] ?? [];
PolimerCatalogImageAudit::ensureItemSections($items);
if ($minScore > 0) {
	$items = array_values(array_filter($items, static function (array $row) use ($minScore): bool {
		return (int)$row['score'] >= $minScore;
	}));
}
if ($priority !== '') {
	$items = array_values(array_filter($items, static function (array $row) use ($priority): bool {
		return $row['priority'] === $priority;
	}));
}
if ($expandedSections) {
	$items = array_values(array_filter($items, static function (array $row) use ($expandedSections): bool {
		return PolimerCatalogImageAudit::itemMatchesSections($row, $expandedSections);
	}));
}
if ($q !== '') {
	$qLower = mb_strtolower($q);
	$items = array_values(array_filter($items, static function (array $row) use ($qLower): bool {
		return mb_stripos($row['name'], $qLower) !== false
			|| mb_stripos($row['code'], $qLower) !== false
			|| (string)$row['id'] === $qLower;
	}));
}

$totalFiltered = count($items);
$itemsPage = array_slice($items, $offset, $limit);

if ($format === 'json') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode([
		'generated_at' => $report['generated_at'] ?? null,
		'generated_at_human' => $report['generated_at_human'] ?? null,
		'stats' => $report['stats'] ?? [],
		'rules' => $report['rules'] ?? [],
		'total_filtered' => $totalFiltered,
		'min_score' => $minScore,
		'sections' => $selectedSections,
		'offset' => $offset,
		'limit' => $limit,
		'items' => $itemsPage,
	], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

$stats = $report['stats'] ?? [];
$rules = $report['rules'] ?? [];
$baseUrl = '/tools/catalog-image-audit.php';
$refreshUrl = $baseUrl . '?refresh=1';
$jsonUrl = $baseUrl . '?format=json';
$buildAuditQuery = static function (array $extra = []) use ($priority, $q, $minScore, $limit, $selectedSections): string {
	$params = array_filter([
		'priority' => $priority,
		'q' => $q,
		'min_score' => $minScore,
		'limit' => $limit,
	], static function ($value): bool {
		return $value !== '' && $value !== null;
	});
	if ($selectedSections) {
		$params['sections'] = $selectedSections;
	}

	return http_build_query(array_merge($params, $extra));
};
$siteOrigin = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
	. '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8084');

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Аудит картинок каталога</title>
	<style>
		:root {
			--bg: #f4f7fb;
			--card: #fff;
			--text: #1f2937;
			--muted: #6b7280;
			--line: #e5e7eb;
			--critical: #b91c1c;
			--urgent: #c2410c;
			--high: #b45309;
			--medium: #2563eb;
			--low: #6b7280;
		}
		* { box-sizing: border-box; }
		body {
			margin: 0;
			font: 14px/1.45 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			background: var(--bg);
			color: var(--text);
		}
		.wrap { max-width: 1400px; margin: 0 auto; padding: 20px; }
		h1 { margin: 0 0 8px; font-size: 24px; }
		.sub { color: var(--muted); margin-bottom: 18px; }
		.toolbar, .stats, .filters-panel, table { background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
		.toolbar { padding: 14px; margin-bottom: 14px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
		.filters-panel { padding: 16px; margin-bottom: 14px; }
		.penguin-help {
			background: #f0f7ff;
			border: 1px solid #cfe3f7;
			border-radius: 10px;
			padding: 14px 16px;
			margin-bottom: 14px;
			line-height: 1.5;
		}
		.penguin-help h2 {
			margin: 0 0 8px;
			font-size: 16px;
		}
		.penguin-help p {
			margin: 0 0 8px;
			color: #334155;
		}
		.penguin-help p:last-child { margin-bottom: 0; }
		.penguin-help ul {
			margin: 0;
			padding-left: 18px;
			color: #334155;
		}
		.penguin-help li { margin: 4px 0; }
		.filters-title {
			font-size: 16px;
			font-weight: 700;
			margin-bottom: 12px;
		}
		.filter-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 14px;
			margin-bottom: 12px;
		}
		.filter-field {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}
		.filter-field--wide { grid-column: 1 / -1; }
		.filter-field--sections .section-search {
			width: 100%;
		}
		.section-multi {
			width: 100%;
			min-height: 220px;
			max-height: 320px;
			padding: 8px;
			font-size: 13px;
			line-height: 1.35;
		}
		.section-multi option {
			padding: 4px 0;
			white-space: normal;
		}
		.section-multi option:checked {
			background: linear-gradient(0deg, #dbeafe 0%, #dbeafe 100%);
			color: #1e3a8a;
		}
		.section-selected-count {
			font-size: 12px;
			color: var(--muted);
		}
		.filter-label {
			font-size: 13px;
			font-weight: 600;
		}
		.filter-hint {
			font-size: 12px;
			color: var(--muted);
			line-height: 1.35;
		}
		.filter-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			align-items: center;
			margin-top: 4px;
		}
		.quick-filters {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			align-items: center;
			margin-bottom: 12px;
		}
		.quick-filters__label {
			font-size: 12px;
			color: var(--muted);
			margin-right: 4px;
		}
		.quick-filters a {
			display: inline-block;
			padding: 6px 10px;
			border: 1px solid var(--line);
			border-radius: 999px;
			background: #fff;
			text-decoration: none;
			color: inherit;
			font-size: 12px;
		}
		.quick-filters a:hover {
			border-color: #0464bb;
			color: #0464bb;
			background: #f0f7ff;
		}
		.toolbar a, .filter-actions button, .filter-actions select, .filter-actions input, .filters-panel select, .filters-panel input {
			border: 1px solid var(--line);
			background: #fff;
			border-radius: 8px;
			padding: 8px 12px;
			text-decoration: none;
			color: inherit;
		}
		.toolbar a.primary, .filter-actions button { background: #0464bb; color: #fff; border-color: #0464bb; }
		.stats {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
			gap: 10px;
			padding: 14px;
			margin-bottom: 14px;
		}
		.stat { padding: 10px; border: 1px solid var(--line); border-radius: 8px; min-height: 88px; }
		.stat b { display: block; font-size: 22px; line-height: 1.1; margin-bottom: 4px; }
		.stat .stat-label {
			display: flex;
			align-items: center;
			min-height: 18px;
			font-size: 12px;
			font-weight: 600;
			color: var(--text);
			line-height: 1.2;
			margin-bottom: 4px;
		}
		.stat .stat-hint {
			display: block;
			min-height: 28px;
			color: var(--muted);
			font-size: 11px;
			line-height: 1.25;
		}
		table { width: 100%; border-collapse: collapse; overflow: hidden; table-layout: fixed; }
		th, td { padding: 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
		th { text-align: left; background: #f9fafb; position: sticky; top: 0; z-index: 1; }
		.col-priority { width: 128px; }
		.col-preview { width: 112px; text-align: center; }
		.col-product { width: 28%; }
		.col-metrics { width: 18%; }
		.col-issues { width: 24%; }
		.col-links { width: 132px; }
		.priority-cell {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 6px;
			min-height: 117px;
			text-align: center;
		}
		.badge {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 4px 10px;
			border-radius: 999px;
			font-size: 12px;
			font-weight: 600;
			color: #fff;
			line-height: 1.2;
			white-space: nowrap;
		}
		.badge.critical { background: var(--critical); }
		.badge.urgent { background: var(--urgent); }
		.badge.high { background: var(--high); }
		.badge.medium { background: var(--medium); }
		.badge.low { background: var(--low); }
		.thumb {
			width: 88px;
			height: 117px;
			object-fit: contain;
			background: #fff;
			border: 1px solid var(--line);
			border-radius: 6px;
			display: block;
		}
		.issues { margin: 0; padding-left: 18px; }
		.issues li { margin: 2px 0; }
		.meta { color: var(--muted); font-size: 12px; }
		.pager { margin: 14px 0; color: var(--muted); }
		.rules { margin-top: 18px; padding: 14px; background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
		.rules h2 { margin: 0 0 8px; font-size: 16px; }
		.rules ul { margin: 0; padding-left: 18px; }
		.copy-line {
			display: grid;
			grid-template-columns: minmax(0, 1fr) 24px;
			align-items: start;
			gap: 6px;
		}
		.copy-line + .copy-line { margin-top: 6px; }
		.copy-line__text {
			min-width: 0;
			word-break: break-word;
		}
		.copy-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 24px;
			height: 24px;
			padding: 0;
			border: 1px solid var(--line);
			border-radius: 6px;
			background: #fff;
			color: var(--muted);
			cursor: pointer;
			flex: 0 0 24px;
		}
		.copy-btn:hover {
			color: #0464bb;
			border-color: #0464bb;
			background: #f0f7ff;
		}
		.copy-btn.copied {
			color: #15803d;
			border-color: #86efac;
			background: #f0fdf4;
		}
		.copy-btn svg {
			width: 14px;
			height: 14px;
			display: block;
		}
		.copy-toast {
			position: fixed;
			right: 20px;
			bottom: 20px;
			background: #111827;
			color: #fff;
			padding: 10px 14px;
			border-radius: 8px;
			font-size: 13px;
			opacity: 0;
			transform: translateY(8px);
			transition: opacity .2s ease, transform .2s ease;
			pointer-events: none;
			z-index: 1000;
		}
		.copy-toast.show {
			opacity: 1;
			transform: translateY(0);
		}
		@media (min-width: 1100px) {
			.stats { grid-template-columns: repeat(7, minmax(0, 1fr)); }
		}
	</style>
</head>
<body>
<div class="wrap">
	<h1>Аудит картинок каталога</h1>
	<div class="sub">
		Обновлено: <?=htmlspecialchars((string)($report['generated_at_human'] ?? '—'))?> (МСК) ·
		Проверено: <?=(int)($stats['checked'] ?? 0)?> товаров ·
		Проблемных: <?=(int)($stats['issues'] ?? 0)?>
		<?php if ($building): ?>
			· <span style="color:#b45309">Идёт пересборка в фоне…</span>
		<?php endif; ?>
	</div>

	<?php if ($buildingStarted): ?>
	<div class="penguin-help" style="background:#fff7ed;border-color:#fdba74;">
		<p style="margin:0">Запущена фоновая пересборка. Текущие данные — из кэша. Обновите страницу через 3–5 минут.</p>
	</div>
	<?php endif; ?>

	<div class="penguin-help">
		<h2>Что это за страница?</h2>
		<p>Тут список товаров, у которых <b>картинка в каталоге смотрится плохо</b>. Сверху таблицы — самые проблемные.</p>
		<ul>
			<li><b>Срочно</b> — картинки нет, файл пропал или она крошечная (&lt;150px).</li>
			<li><b>Очень важно</b> — горизонтальные фото: в карточке <b>обрезаются края</b> (органайзеры, ящики, сайдинг). Нужен кадр 3:4.</li>
			<li><b>Важно</b> — много пустого белого вокруг товара.</li>
			<li>Иконка 📋 рядом с названием / ID / ссылкой — <b>скопировать</b> в буфер.</li>
		</ul>
	</div>

	<div class="toolbar">
		<a class="primary" href="<?=htmlspecialchars($refreshUrl)?>">Обновить отчёт</a>
		<a href="<?=htmlspecialchars($jsonUrl)?>" target="_blank">JSON</a>
		<a href="<?=htmlspecialchars($baseUrl)?>">Сбросить фильтры</a>
		<div class="stat"><b><?=(int)($stats['total'] ?? 0)?></b><span class="stat-label">Всего активных</span><span class="stat-hint">Все товары в каталоге</span></div>
		<div class="stat"><b><?=(int)($stats['issues'] ?? 0)?></b><span class="stat-label">С проблемами</span><span class="stat-hint">Картинка требует внимания</span></div>
		<div class="stat"><b><?=(int)($stats['by_priority']['critical'] ?? 0)?></b><span class="stat-label">Срочно</span><span class="stat-hint">Нет фото / файл потерян</span></div>
		<div class="stat"><b><?=(int)($stats['by_priority']['urgent'] ?? 0)?></b><span class="stat-label">Очень важно</span><span class="stat-hint">Горизонтальные, обрезаются</span></div>
		<div class="stat"><b><?=(int)($stats['by_priority']['high'] ?? 0)?></b><span class="stat-label">Важно</span><span class="stat-hint">Много пустого поля</span></div>
		<div class="stat"><b><?=(int)($stats['by_priority']['medium'] ?? 0)?></b><span class="stat-label">Средне</span><span class="stat-hint">Можно поправить позже</span></div>
		<div class="stat"><b><?=(int)($stats['by_priority']['low'] ?? 0)?></b><span class="stat-label">Низкий</span><span class="stat-hint">Мелкие отклонения</span></div>
	</div>

	<form class="filters-panel" method="get" action="<?=htmlspecialchars($baseUrl)?>">
		<div class="filters-title">Поиск и фильтры</div>

		<div class="quick-filters">
			<span class="quick-filters__label">Быстрые кнопки:</span>
			<a href="<?=htmlspecialchars($baseUrl . '?priority=urgent&min_score=450')?>">Горизонтальные (обрезаются)</a>
			<a href="<?=htmlspecialchars($baseUrl . '?priority=critical&min_score=0')?>">Без картинки</a>
			<a href="<?=htmlspecialchars($baseUrl . '?priority=high&min_score=450')?>">Много пустого поля</a>
			<a href="<?=htmlspecialchars($baseUrl . '?min_score=0')?>">Показать всех</a>
		</div>

		<div class="filter-grid">
			<label class="filter-field">
				<span class="filter-label">Насколько плохо?</span>
				<select name="priority">
					<option value="">Все приоритеты</option>
					<?php foreach (['critical','urgent','high','medium','low'] as $p): ?>
						<option value="<?=$p?>" <?=($priority === $p ? 'selected' : '')?>><?=htmlspecialchars(PolimerCatalogImageAudit::priorityLabel($p))?></option>
					<?php endforeach; ?>
				</select>
				<span class="filter-hint">Выбери «Очень важно», если ищешь горизонтальные картинки, которые режутся по бокам.</span>
			</label>

			<label class="filter-field">
				<span class="filter-label">Минимальный балл (score)</span>
				<input type="number" name="min_score" value="<?=$minScore?>" min="0" max="3000" step="50">
				<span class="filter-hint"><b>450</b> — только важные (по умолчанию). <b>0</b> — показать всех с любыми проблемами. <b>700+</b> — совсем плохие.</span>
			</label>

			<label class="filter-field filter-field--wide">
				<span class="filter-label">Найти товар</span>
				<input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Например: органайзер, 65674, FIT 65553, сайдинг">
				<span class="filter-hint">Можно вбить часть названия, код товара или ID. Пример: <i>organayzer</i> или <i>44211</i>.</span>
			</label>

			<label class="filter-field filter-field--wide filter-field--sections">
				<span class="filter-label">Категории</span>
				<input type="search" id="sectionSearch" class="section-search" placeholder="Поиск по названию категории…">
				<select name="sections[]" id="sectionSelect" class="section-multi" multiple size="12">
					<?php foreach ($sectionOptions as $sec): ?>
						<option value="<?=(int)$sec['id']?>" <?=in_array((int)$sec['id'], $selectedSections, true) ? 'selected' : ''?>>
							<?=str_repeat('— ', max(0, (int)$sec['depth'] - 1))?><?=htmlspecialchars($sec['name'])?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="section-selected-count" id="sectionSelectedCount">
					<?php if ($selectedSections): ?>
						Выбрано категорий: <?=count($selectedSections)?> (с подкатегориями: <?=count($expandedSections)?>)
					<?php else: ?>
						Не выбрано — показываются все категории
					<?php endif; ?>
				</span>
				<span class="filter-hint">Ctrl / Cmd + клик — несколько категорий. Выбранная ветка включает все подкатегории.</span>
			</label>
		</div>

		<input type="hidden" name="limit" value="<?=$limit?>">
		<div class="filter-actions">
			<button type="submit">Показать</button>
			<a href="<?=htmlspecialchars($baseUrl)?>">Сбросить</a>
		</div>
	</form>

	<table>
		<thead>
		<tr>
			<th class="col-priority">Приоритет</th>
			<th class="col-preview">Превью</th>
			<th class="col-product">Товар</th>
			<th class="col-metrics">Метрики</th>
			<th class="col-issues">Проблемы</th>
			<th class="col-links">Ссылки</th>
		</tr>
		</thead>
		<tbody>
		<?php if (!$itemsPage): ?>
			<tr><td colspan="6">Ничего не найдено</td></tr>
		<?php else: ?>
			<?php foreach ($itemsPage as $row): ?>
				<tr>
					<td class="col-priority">
						<div class="priority-cell">
							<span class="badge <?=htmlspecialchars($row['priority'])?>"><?=htmlspecialchars($row['priority_label'])?></span>
							<div class="meta">score <?=(int)$row['score']?></div>
						</div>
					</td>
					<td class="col-preview">
						<?php
						$imageSrc = (string)($row['original_src'] ?: $row['preview_src'] ?? '');
						$imageUrl = $imageSrc !== '' ? ($siteOrigin . $imageSrc) : '';
						?>
						<?php if (!empty($row['preview_src'])): ?>
							<?php if ($imageSrc !== ''): ?>
								<a href="<?=htmlspecialchars($imageSrc)?>" target="_blank" title="Открыть оригинал картинки">
									<img class="thumb" src="<?=htmlspecialchars($row['preview_src'])?>" alt="">
								</a>
							<?php else: ?>
								<img class="thumb" src="<?=htmlspecialchars($row['preview_src'])?>" alt="">
							<?php endif; ?>
						<?php endif; ?>
					</td>
					<td class="col-product">
						<?php
						$productUrl = $siteOrigin . $row['url'];
						$adminUrl = $siteOrigin . $row['admin_url'];
						?>
						<div class="copy-line">
							<div class="copy-line__text"><b><?=htmlspecialchars($row['name'])?></b></div>
							<button type="button" class="copy-btn" data-copy="<?=htmlspecialchars($row['name'], ENT_QUOTES)?>" title="Копировать название" aria-label="Копировать название">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
						<div class="copy-line meta">
							<div class="copy-line__text">ID <?=(int)$row['id']?> · <?=htmlspecialchars($row['code'])?></div>
							<button type="button" class="copy-btn" data-copy="<?=(int)$row['id']?>" title="Копировать ID" aria-label="Копировать ID">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
						<?php if (!empty($row['section_names'])): ?>
							<div class="meta"><?=htmlspecialchars(implode(' · ', $row['section_names']))?></div>
						<?php endif; ?>
					</td>
					<td class="col-metrics meta">
						<?php if ($row['width'] && $row['height']): ?>
							<?=(int)$row['width']?>×<?=(int)$row['height']?><br>
							ratio <?=htmlspecialchars((string)$row['ratio'])?><br>
						<?php endif; ?>
						карточка: <?=(float)$row['card_fill_w']?>% × <?=(float)$row['card_fill_h']?>%<br>
						отступы: <?=(float)$row['pad_h_pct']?>% / <?=(float)$row['pad_v_pct']?>%<br>
						товар в кадре: <?=(float)$row['subject_fill_w']?>% × <?=(float)$row['subject_fill_h']?>%
					</td>
					<td class="col-issues">
						<ul class="issues">
							<?php foreach ($row['issues'] as $issue): ?>
								<li><?=htmlspecialchars($issue['label'])?></li>
							<?php endforeach; ?>
						</ul>
					</td>
					<td class="col-links meta">
						<div class="copy-line">
							<div class="copy-line__text"><a href="<?=htmlspecialchars($row['url'])?>" target="_blank">Карточка</a></div>
							<button type="button" class="copy-btn" data-copy="<?=htmlspecialchars($productUrl, ENT_QUOTES)?>" title="Копировать ссылку на карточку" aria-label="Копировать ссылку на карточку">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
						<div class="copy-line">
							<div class="copy-line__text"><a href="<?=htmlspecialchars($row['admin_url'])?>" target="_blank">Админка</a></div>
							<button type="button" class="copy-btn" data-copy="<?=htmlspecialchars($adminUrl, ENT_QUOTES)?>" title="Копировать ссылку в админку" aria-label="Копировать ссылку в админку">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
						<?php if ($imageSrc !== ''): ?>
						<div class="copy-line">
							<div class="copy-line__text"><a href="<?=htmlspecialchars($imageSrc)?>" target="_blank">Картинка</a></div>
							<button type="button" class="copy-btn" data-copy="<?=htmlspecialchars($imageUrl, ENT_QUOTES)?>" title="Копировать ссылку на картинку" aria-label="Копировать ссылку на картинку">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
							</button>
						</div>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<div class="pager">
		Показано <?=$offset + 1?>–<?=min($offset + $limit, $totalFiltered)?> из <?=$totalFiltered?>
		<?php if ($offset + $limit < $totalFiltered): ?>
			· <a href="<?=htmlspecialchars($baseUrl . '?' . $buildAuditQuery(['offset' => $offset + $limit]))?>">Дальше</a>
		<?php endif; ?>
	</div>

	<div class="rules">
		<h2>Как читать таблицу</h2>
		<ul>
			<li><b>Превью</b> — как картинка выглядит в карточке каталога (рамка 3:4).</li>
			<li><b>Метрики</b> — размер файла и насколько товар заполняет рамку. Чем меньше % — тем больше пустоты.</li>
			<li><b>Проблемы</b> — что именно не так. «Горизонтальная обрезается» = нужно новое фото 1024×1536.</li>
			<li><b>Карточка</b> — товар на сайте. <b>Админка</b> — где заменить фото. <b>Картинка</b> — оригинал файла (можно ткнуть и посмотреть).</li>
		</ul>
		<p class="meta">Идеальная картинка: <b>1024×1536</b> (3:4), белый фон, товар занимает ~90% высоты, отступы 4–6% от краёв.</p>
	</div>
</div>
<div class="copy-toast" id="copyToast">Скопировано</div>
<script>
(function () {
	var toast = document.getElementById('copyToast');
	var toastTimer;

	function showToast(text) {
		if (!toast) return;
		toast.textContent = text || 'Скопировано';
		toast.classList.add('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			toast.classList.remove('show');
		}, 1400);
	}

	function fallbackCopy(text) {
		var area = document.createElement('textarea');
		area.value = text;
		area.setAttribute('readonly', '');
		area.style.position = 'fixed';
		area.style.left = '-9999px';
		document.body.appendChild(area);
		area.select();
		try {
			document.execCommand('copy');
			return true;
		} catch (e) {
			return false;
		} finally {
			document.body.removeChild(area);
		}
	}

	function copyText(text, btn) {
		if (!text) return;
		var done = function (ok) {
			if (!ok) {
				showToast('Не удалось скопировать');
				return;
			}
			if (btn) {
				btn.classList.add('copied');
				setTimeout(function () { btn.classList.remove('copied'); }, 1200);
			}
			showToast('Скопировано');
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				done(true);
			}).catch(function () {
				done(fallbackCopy(text));
			});
			return;
		}
		done(fallbackCopy(text));
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.copy-btn');
		if (!btn) return;
		e.preventDefault();
		copyText(btn.getAttribute('data-copy') || '', btn);
	});

	var sectionSearch = document.getElementById('sectionSearch');
	var sectionSelect = document.getElementById('sectionSelect');
	var sectionSelectedCount = document.getElementById('sectionSelectedCount');
	if (sectionSearch && sectionSelect) {
		sectionSearch.addEventListener('input', function () {
			var q = this.value.toLowerCase().trim();
			Array.prototype.forEach.call(sectionSelect.options, function (opt) {
				opt.hidden = q !== '' && opt.text.toLowerCase().indexOf(q) === -1;
			});
		});
	}
	if (sectionSelect && sectionSelectedCount) {
		var updateSectionCount = function () {
			var count = Array.prototype.filter.call(sectionSelect.selectedOptions, function () {
				return true;
			}).length;
			sectionSelectedCount.textContent = count
				? ('Выбрано категорий: ' + count)
				: 'Не выбрано — показываются все категории';
		};
		sectionSelect.addEventListener('change', updateSectionCount);
	}
})();
</script>
</body>
</html>
