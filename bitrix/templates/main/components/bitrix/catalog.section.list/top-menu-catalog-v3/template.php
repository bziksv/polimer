<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/" class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu catalog-menu-v3">
	<div class="wr">
		<ul class="first-sections">
			<?
			$allowRootCat = ['1153', '1492', '1496'];
			foreach ($arResult['SECTIONS'] as $section):
				if ($section["DEPTH_LEVEL"] == "1" && in_array($section['ID'], $allowRootCat) == false) {
					continue;
				}
			?>
			<li>
				<a href="<?=$section['SECTION_PAGE_URL']?>" class="catalog-menu-v3__root"><?=$section['NAME']?></a>
				<? if (!empty($section['SECTION_1'])): ?>
				<div class="subsections">
					<div class="catalog-menu-v3__cards">
						<? foreach ($section['SECTION_1'] as $sec_2): ?>
						<div class="catalog-menu-v3__card">
							<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v3__card-title"><?=$sec_2['NAME']?></a>
							<? if (!empty($sec_2['SECTION_2'])): ?>
							<ul class="catalog-menu-v3__card-links">
								<? foreach ($sec_2['SECTION_2'] as $sec_3): ?>
								<li><a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=$sec_3['NAME']?></a></li>
								<? endforeach; ?>
							</ul>
							<? endif; ?>
						</div>
						<? endforeach; ?>
					</div>
				</div>
				<? endif; ?>
			</li>
			<? endforeach; ?>
		</ul>
	</div>
</div>
