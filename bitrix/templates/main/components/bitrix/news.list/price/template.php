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

<div class="row cl">
	<div class="pl__content">
		<h1 class="h1-pl"><?=$arResult['NAME']?></h1>
		<div class="date"><img src="/bitrix/templates/main/img/price.jpg" alt="Прайс-листы">Обновлено: <span><?=$arResult['TIMESTAMP_X'];?></span></div>
		<div class="block cl">


			<?foreach($arResult["ITEMS"] as $arItem):?>
			<div class="col ym-goal-price-dw">
				<div class="title cl">
					<div class="name"><?=str_replace(' ','<br>',$arItem['NAME'])?></div>

					<div class="download">
						<a target="_blank" href="<?=$arItem['PROPERTIES']['TITLE_PRICE']['VALUE'];?>">Скачать</a>
						<span><?=CFile::FormatSize(filesize($_SERVER['DOCUMENT_ROOT'].$arItem['PROPERTIES']['TITLE_PRICE']['VALUE']));?></span>
					</div>

				</div>
<!--
				<div class="list">
				<?foreach($arItem['PROPERTIES']['PRICE']['VALUE'] as $key => $v):?>
					<div class="line cl">
						<span><?=$arItem['PROPERTIES']['PRICE']['DESCRIPTION'][$key]?></span>
						<a target="_blank" href="<?=$v;?>" class="download">Скачать</a>
					</div>
				<? endforeach; ?>
				</div>
-->
			</div>
			<? endforeach; ?>

		</div>
	</div>

    <?if(false): //Скрыть на время?>
	<form class="ym-goal-subscribe-price" method="post" action="/ajax/price.php">
	<div class="pl__subscribe">
		<div class="form">
			<div class="name">Получайте новые <br>обновления прайс-листов <br>на Вашу почту</div>
			<div class="inp">
				<span>Представьтесь</span>
				<input type="hidden" name="charset" value="utf-8" />
				<input type="text" class="name" placeholder="Имя" name="Name">
			</div>
			<div class="inp">
				<span>E-mail*</span>
				<input type="email" placeholder="vash_mail@mail.ru" name="email" required>
			</div>
			<div class="inp">
				<span>Телефон</span>
				<input type="tel" name="phone" placeholder="+7-___-___-__-__" class="phone ru_phone_check" autocomplete="tel" inputmode="tel">
			</div>
			<div class="inp">
				<?php
				$consentName = 'rule';
				$consentChecked = !empty($_POST['rule']);
				$consentInvalid = false;
				require $_SERVER['DOCUMENT_ROOT'] . '/include/legal/consent-checkbox.php';
				?>
			</div>
			<br><br><br>
			<input class="btn_subscribe" type="submit" value=" Подписаться " />
			<div class="req">* - обязательное поле</div>
		</div>
	</div>
	</form>
    <?endif;?>
</div>

<script>
	$(function(){
		$('#rule').change(function(){
			$('.btn_subscribe').attr('disabled',$(this).prop('checked') ? false : true );
		});
	});
</script>
