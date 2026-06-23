<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$compositeStub = (isset($arResult['COMPOSITE_STUB']) && $arResult['COMPOSITE_STUB'] == 'Y');
?>

<?php if (!$arResult['DISABLE_USE_BASKET']): ?>
<a href="<?= $arParams['PATH_TO_BASKET'] ?>" class="hmobile__cart cart">
    <span class="cart__number"><?= $arResult['TOTAL_QUANTITY'] ?></span>
</a>
<?php endif; ?>
