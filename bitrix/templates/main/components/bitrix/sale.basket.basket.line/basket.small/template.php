<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?php if (!$arResult['DISABLE_USE_BASKET']): ?>
<a href="<?= $arParams['PATH_TO_BASKET'] ?>" class="header__cart cart">
	<span class="cart__number">
		<?= $arResult['TOTAL_QUANTITY'] ?>
	</span>
    <?php if ($arResult['TOTAL_PRICE']): ?>
        <span class="cart__sum"><span class="cart__sum--numbers"><?= $arResult['TOTAL_PRICE'] ?></span> </span>
    <?php endif; ?>
</a>
<?php endif; ?>
