<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!isset($catalogMenuShowSwitcher)) {
    require $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/catalog_menu_variant.php';
}

if (empty($catalogMenuShowSwitcher)) {
    return;
}
?>

<div class="catalog-menu-switcher" id="catalog-menu-switcher">
    <button type="button" class="catalog-menu-switcher__toggle" id="catalog-menu-switcher-toggle" title="Варианты меню каталога">
        Меню: <?=htmlspecialcharsbx($catalogMenuVariants[$catalogMenuActiveKey]['label'])?>
    </button>
    <div class="catalog-menu-switcher__panel" id="catalog-menu-switcher-panel" hidden>
        <div class="catalog-menu-switcher__title">Меню каталога</div>
        <div class="catalog-menu-switcher__buttons">
            <?php foreach ($catalogMenuVariants as $key => $variant): ?>
            <button type="button"
                class="catalog-menu-switcher__btn<?= $catalogMenuActiveKey === $key ? ' is-active' : '' ?>"
                data-catalog-menu="<?=htmlspecialcharsbx($key)?>"
                title="<?=htmlspecialcharsbx($variant['title'])?>">
                <?=htmlspecialcharsbx($variant['label'])?>
            </button>
            <?php endforeach; ?>
        </div>
        <a class="catalog-menu-switcher__preview" href="/catalog-menu-preview/">Все варианты →</a>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('catalog-menu-switcher');
    var toggle = document.getElementById('catalog-menu-switcher-toggle');
    var panel = document.getElementById('catalog-menu-switcher-panel');
    if (!root || !toggle || !panel) return;

    toggle.addEventListener('click', function () {
        var open = !panel.hidden;
        panel.hidden = open;
        root.classList.toggle('is-open', !open);
    });

    panel.querySelectorAll('[data-catalog-menu]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var variant = btn.getAttribute('data-catalog-menu');
            var url = new URL(window.location.href);
            url.searchParams.set('catalog_menu', variant);
            window.location.href = url.toString();
        });
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) {
            panel.hidden = true;
            root.classList.remove('is-open');
        }
    });
})();
</script>
