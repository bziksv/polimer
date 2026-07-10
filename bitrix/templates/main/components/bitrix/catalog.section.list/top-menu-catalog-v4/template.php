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
?>

<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/" class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu catalog-menu-v4">
	<div class="wr">
		<div class="catalog-menu-v4__panel">
			<ul class="catalog-menu-v4__tabs">
				<? foreach ($rootSections as $i => $section): ?>
				<li>
					<button type="button"
						class="catalog-menu-v4__tab<?= $i === 0 ? ' is-active' : '' ?>"
						data-tab="menu-v4-<?=$section['ID']?>">
						<?=$section['NAME']?>
					</button>
				</li>
				<? endforeach; ?>
			</ul>

			<? foreach ($rootSections as $i => $section): ?>
			<div class="catalog-menu-v4__content<?= $i === 0 ? ' is-active' : '' ?>" id="menu-v4-<?=$section['ID']?>">
				<a href="<?=$section['SECTION_PAGE_URL']?>" class="catalog-menu-v4__root-link">Весь раздел «<?=$section['NAME']?>»</a>
				<? if (!empty($section['SECTION_1'])): ?>
				<div class="catalog-menu-v4__grid">
					<? foreach ($section['SECTION_1'] as $sec_2): ?>
					<div class="catalog-menu-v4__col">
						<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v4__col-title"><?=$sec_2['NAME']?></a>
						<? if (!empty($sec_2['SECTION_2'])): ?>
						<ul>
							<? foreach ($sec_2['SECTION_2'] as $sec_3): ?>
							<li><a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=$sec_3['NAME']?></a></li>
							<? endforeach; ?>
						</ul>
						<? endif; ?>
					</div>
					<? endforeach; ?>
				</div>
				<? endif; ?>
			</div>
			<? endforeach; ?>
		</div>
	</div>
</div>

<script>
(function () {
	document.querySelectorAll('.catalog-menu-v4__tab').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var panel = btn.closest('.catalog-menu-v4__panel');
			if (!panel) return;
			panel.querySelectorAll('.catalog-menu-v4__tab').forEach(function (t) { t.classList.remove('is-active'); });
			panel.querySelectorAll('.catalog-menu-v4__content').forEach(function (c) { c.classList.remove('is-active'); });
			btn.classList.add('is-active');
			var target = panel.querySelector('#' + btn.getAttribute('data-tab'));
			if (target) target.classList.add('is-active');
		});
	});
})();
</script>
