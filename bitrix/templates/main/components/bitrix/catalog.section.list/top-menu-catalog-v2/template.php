<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/" class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu catalog-menu-v2">
	<div class="wr">
		<ul class="first-sections">
			<?
			$allowRootCat = ['1153', '1492', '1496'];
			$inc = 1;
			foreach ($arResult['SECTIONS'] as $section):
				if ($section["DEPTH_LEVEL"] == "1" && in_array($section['ID'], $allowRootCat) == false) {
					continue;
				}
			?>
			<li>
				<a href="<?=$section['SECTION_PAGE_URL']?>">
					<?
					$icon = SITE_TEMPLATE_PATH . "/img/category_" . $inc . ".png";
					if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $icon)) {
						$icon = SITE_TEMPLATE_PATH . "/img/category_3.png";
					}
					?>
					<img src="<?=$icon?>" width="36" height="26" alt="<?=$section['NAME']?>" />
					<span class="catalog-menu-v2__label"><?=$section['NAME']?></span>
				</a>
				<? if (!empty($section['SECTION_1'])): ?>
				<div class="subsections">
					<div class="catalog-menu-v2__grid">
						<? foreach ($section['SECTION_1'] as $sec_2): ?>
						<div class="catalog-menu-v2__group">
							<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="catalog-menu-v2__title"><?=$sec_2['NAME']?></a>
							<? if (!empty($sec_2['SECTION_2'])): ?>
							<ul class="catalog-menu-v2__children">
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
			<?
			$inc++;
			endforeach; ?>
		</ul>
	</div>
</div>
