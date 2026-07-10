<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Варианты меню каталога');
$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');

$catalogMenuComponentParams = array(
    'ADD_SECTIONS_CHAIN' => 'N',
    'CACHE_GROUPS' => 'Y',
    'CACHE_TIME' => '36000000',
    'CACHE_TYPE' => 'A',
    'COUNT_ELEMENTS' => 'Y',
    'IBLOCK_ID' => '21',
    'IBLOCK_TYPE' => '1c_catalog',
    'SECTION_CODE' => '',
    'SECTION_FIELDS' => array('', ''),
    'SECTION_ID' => '',
    'SECTION_URL' => '',
    'SECTION_USER_FIELDS' => array('', ''),
    'SHOW_PARENT_NAME' => 'Y',
    'TOP_DEPTH' => '3',
    'VIEW_MODE' => 'LINE',
);

$menuVersions = array(
    'top-menu-catalog' => array(
        'title' => 'Текущее (оригинал)',
        'desc' => 'Как сейчас на сайте: иконки слева, 3 колонки, раскрытие по +.',
        'switch' => '?catalog_menu=default',
    ),
    'top-menu-catalog-v2' => array(
        'title' => 'V2 — Компактное',
        'desc' => 'Узкая колонка, мелкие иконки, 2 колонки подразделов, скролл если не влезает.',
        'switch' => '?catalog_menu=v2',
    ),
    'top-menu-catalog-v3' => array(
        'title' => 'V3 — Карточки',
        'desc' => 'Текстовый список слева, подразделы карточками в сетке.',
        'switch' => '?catalog_menu=v3',
    ),
    'top-menu-catalog-v4' => array(
        'title' => 'V4 — Вкладки',
        'desc' => 'Разделы вкладками сверху, подкатегории сеткой 4 колонки.',
        'switch' => '?catalog_menu=v4',
    ),
    'top-menu-catalog-v5' => array(
        'title' => 'V5 — Продуманное',
        'desc' => 'Боковая навигация по 3 разделам, карточки подкатегорий, счётчики товаров, аккуратная типографика.',
        'switch' => '?catalog_menu=v5',
    ),
);
?>

<style>
.catalog-menu-preview {
    max-width: 1200px;
    margin: 30px auto 60px;
    padding: 0 20px;
    font-family: 'roboto', sans-serif;
}
.catalog-menu-preview h1 {
    margin: 0 0 10px;
    font-size: 28px;
    color: #023d73;
}
.catalog-menu-preview__intro {
    margin: 0 0 24px;
    color: #555;
    line-height: 1.5;
}
.catalog-menu-preview__item {
    margin-bottom: 36px;
    border: 1px solid #d5e2ee;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
}
.catalog-menu-preview__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 18px;
    background: #f5f8fb;
    border-bottom: 1px solid #e3ebf2;
}
.catalog-menu-preview__head h2 {
    margin: 0;
    font-size: 18px;
    color: #0358a6;
}
.catalog-menu-preview__head p {
    margin: 4px 0 0;
    font-size: 13px;
    color: #666;
}
.catalog-menu-preview__apply {
    flex: 0 0 auto;
    padding: 8px 14px;
    border-radius: 6px;
    background: #0060bf;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}
.catalog-menu-preview__apply:hover {
    background: #00a1f2;
    color: #fff;
}
.catalog-menu-preview__demo {
    position: relative;
    min-height: 420px;
    background: #2f4d68;
    padding: 16px;
}
.catalog-menu-preview__demo .header__catalog {
    position: relative;
    display: inline-block;
    background: #014075;
    padding: 12px 16px;
    border-radius: 6px;
}
.catalog-menu-preview__demo .catalog-sections-menu {
    display: block !important;
    position: relative !important;
    top: 12px !important;
    left: 0 !important;
}
.catalog-menu-preview__demo .catalog__name,
.catalog-menu-preview__demo .catalog__trigger span {
    color: #fff;
}
.catalog-menu-preview__demo .catalog-menu-v4.catalog-sections-menu {
    width: 100%;
}
.catalog-menu-preview__demo .catalog-menu-v5.catalog-sections-menu {
    width: 100%;
    max-width: 100%;
}
.catalog-menu-preview__demo .catalog-menu-v5 .catalog-menu-v5__shell {
    max-height: 520px;
}
</style>

<div class="catalog-menu-preview">
    <h1>Варианты меню каталога</h1>
    <p class="catalog-menu-preview__intro">
        Текущее меню не удалено. Чтобы применить вариант на всём сайте, нажмите «Включить» или откройте
        <code>?catalog_menu=v2</code> / <code>v3</code> / <code>v4</code> / <code>v5</code>. Вернуть оригинал: <code>?catalog_menu=default</code>.
    </p>

    <?php foreach ($menuVersions as $template => $meta): ?>
    <section class="catalog-menu-preview__item">
        <div class="catalog-menu-preview__head">
            <div>
                <h2><?=htmlspecialcharsbx($meta['title'])?></h2>
                <p><?=htmlspecialcharsbx($meta['desc'])?></p>
            </div>
            <a class="catalog-menu-preview__apply" href="<?=htmlspecialcharsbx($meta['switch'])?>">Включить</a>
        </div>
        <div class="catalog-menu-preview__demo">
            <div class="header__catalog cl">
                <?php
                $params = $catalogMenuComponentParams;
                $params['COMPONENT_TEMPLATE'] = $template;
                $APPLICATION->IncludeComponent(
                    'bitrix:catalog.section.list',
                    $template,
                    $params,
                    false
                );
                ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>
</div>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
