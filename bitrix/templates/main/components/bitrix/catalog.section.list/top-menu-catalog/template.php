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
$this->setFrameMode(true);
?>


<a href="/catalog/" class="catalog__trigger" aria-label="Каталог товаров">
	<i class="fa fa-bars header__fa-icon" aria-hidden="true"></i>
</a>

<a href="/catalog/"  class="catalog__name">Каталог товаров</a>

<div class="catalog-sections-menu">
	<div class="wr">
		<ul class="first-sections">

			<?
			$allowRootCat = ['1153', '1492', '1496'];
			$inc = 1;
			foreach($arResult['SECTIONS'] as $key => $section): 
			
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

					<img src="<?=$icon?>" width="90" height="65" alt="<?=$section['NAME']?>" />
					<?=str_replace(' ','<br>',$section['NAME'])?>
				</a>
                <? if ($section['SECTION_1']) : ?>
				<div class="subsections">
					<?
					$col_arr = ceil(count($section['SECTION_1']) / 3);
					$three_sect = array_chunk($section['SECTION_1'], $col_arr);
					for($i = 0; $i < count($three_sect);$i++):
					?>
					<ul>
					<? foreach($three_sect[$i] as $sec_2): ?>

						<li >
							<? if(isset($sec_2['SECTION_2']) && count($sec_2['SECTION_2']) != 0): ?><a class="dd" href="#">+</a><? endif; ?>
							<a href="<?=$sec_2['SECTION_PAGE_URL']?>" class="title"><?=$sec_2['NAME']?></a>
							<ul class="inner">
							<? foreach($sec_2['SECTION_2'] as $sec_3): ?>
								<li><a href="<?=$sec_3['SECTION_PAGE_URL']?>"><?=$sec_3['NAME']?></a></li>
							<? endforeach; ?>
							</ul>
						</li>

					<? endforeach; ?>
					</ul>
					<? endfor; ?>
				</div>
                <? endif; ?>
			</li>
			<?
			$inc++;
			endforeach; ?>
		</ul>
	</div>
</div>
