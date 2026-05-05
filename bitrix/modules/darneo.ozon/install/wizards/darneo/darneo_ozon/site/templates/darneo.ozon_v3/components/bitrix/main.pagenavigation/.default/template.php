<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */
/** @var PageNavigationComponent $component */

$component = $this->getComponent();

$this->setFrameMode(true);

?>

<div class='mt-10 mb-10'>
    <ul class='pagination'>
        <?php
        $prevPage = $arResult['CURRENT_PAGE'] - 1;
        $nextPage = $arResult['CURRENT_PAGE'] + 1;

        $prevDisabled = $arResult['CURRENT_PAGE'] <= 1 ? 'disabled' : '';
        $nextDisabled = $arResult['CURRENT_PAGE'] >= $arResult['PAGE_COUNT'] ? 'disabled' : '';

        $prevUrl = $prevDisabled ? '#' : htmlspecialcharsbx($component->replaceUrlTemplate($prevPage));
        $nextUrl = $nextDisabled ? '#' : htmlspecialcharsbx($component->replaceUrlTemplate($nextPage));
        ?>
        <li class='page-item previous <?= $prevDisabled ?>'>
            <a href='<?= $prevUrl ?>' class='page-link page-text'><?php echo GetMessage('round_nav_back') ?></a>
        </li>

        <?php
        $startPage = $arResult['REVERSED_PAGES'] ? $arResult['PAGE_COUNT'] : 1;
        $endPage = $arResult['REVERSED_PAGES'] ? 1 : $arResult['PAGE_COUNT'];
        $pageStep = $arResult['REVERSED_PAGES'] ? -1 : 1;

        for ($page = $startPage; $arResult['REVERSED_PAGES'] ? ($page >= $endPage) : ($page <= $endPage); $page += $pageStep):
            $activeClass = $page == $arResult['CURRENT_PAGE'] ? 'active' : '';
            $pageUrl = htmlspecialcharsbx($component->replaceUrlTemplate($page));
            ?>
            <li class='page-item <?= $activeClass ?>'>
                <a href='<?= $pageUrl ?>' class='page-link'><?= $page ?></a>
            </li>
        <?php endfor; ?>

        <li class='page-item next <?= $nextDisabled ?>'>
            <a href='<?= $nextUrl ?>' class='page-link page-text'><?php echo GetMessage('round_nav_forward') ?></a>
        </li>
    </ul>
</div>
