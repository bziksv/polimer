<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$currentPage = mb_strtolower(\Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage());
$basketPage = mb_strtolower($arParams['PATH_TO_BASKET']);
$isBasketPage = strncmp($currentPage, $basketPage, mb_strlen($basketPage)) === 0;
?>

<?php if (!$arResult['DISABLE_USE_BASKET']): ?>
<a href="<?= $arParams['PATH_TO_BASKET'] ?>" class="header__cart cart<?= $isBasketPage ? ' is-active' : '' ?>"<?= $isBasketPage ? ' aria-current="page"' : '' ?>>
	<span class="header__cart-icon">
		<i class="fa fa-shopping-basket header__fa-icon" aria-hidden="true"></i>
		<span class="cart__number"><?= $arResult['TOTAL_QUANTITY'] ?></span>
	</span>
    <?php if ($arResult['TOTAL_PRICE']): ?>
        <span class="cart__sum"><span class="cart__sum--numbers"><?= $arResult['TOTAL_PRICE'] ?></span></span>
    <?php endif; ?>
</a>
<?php endif; ?>
