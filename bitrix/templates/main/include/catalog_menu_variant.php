<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$catalogMenuAllowed = array(
    'top-menu-catalog',
    'top-menu-catalog-v2',
    'top-menu-catalog-v3',
    'top-menu-catalog-v4',
    'top-menu-catalog-v5',
    'top-menu-catalog-v6',
);

$catalogMenuVariants = array(
    'default' => array(
        'template' => 'top-menu-catalog',
        'label' => 'Оригинал',
        'title' => 'Текущее меню',
    ),
    'v2' => array(
        'template' => 'top-menu-catalog-v2',
        'label' => 'V2',
        'title' => 'Компактное',
    ),
    'v3' => array(
        'template' => 'top-menu-catalog-v3',
        'label' => 'V3',
        'title' => 'Карточки',
    ),
    'v4' => array(
        'template' => 'top-menu-catalog-v4',
        'label' => 'V4',
        'title' => 'Вкладки',
    ),
    'v5' => array(
        'template' => 'top-menu-catalog-v5',
        'label' => 'V5',
        'title' => 'Продуманное',
    ),
    'v6' => array(
        'template' => 'top-menu-catalog-v6',
        'label' => 'V6',
        'title' => 'С фото',
    ),
);

$catalogMenuTemplate = 'top-menu-catalog';
$catalogMenuActiveKey = 'default';

if (!empty($_GET['catalog_menu'])) {
    $catalogMenuVariant = preg_replace('/[^a-z0-9-]/', '', $_GET['catalog_menu']);

    if ($catalogMenuVariant === 'default') {
        $catalogMenuTemplate = 'top-menu-catalog';
        $catalogMenuActiveKey = 'default';
    } elseif (isset($catalogMenuVariants[$catalogMenuVariant])) {
        $catalogMenuTemplate = $catalogMenuVariants[$catalogMenuVariant]['template'];
        $catalogMenuActiveKey = $catalogMenuVariant;
    }

    if (in_array($catalogMenuTemplate, $catalogMenuAllowed, true)) {
        setcookie('catalog_menu_variant', $catalogMenuTemplate, time() + 86400 * 30, '/');
    } else {
        $catalogMenuTemplate = 'top-menu-catalog';
        $catalogMenuActiveKey = 'default';
    }
} elseif (!empty($_COOKIE['catalog_menu_variant']) && in_array($_COOKIE['catalog_menu_variant'], $catalogMenuAllowed, true)) {
    $catalogMenuTemplate = $_COOKIE['catalog_menu_variant'];

    foreach ($catalogMenuVariants as $key => $variant) {
        if ($variant['template'] === $catalogMenuTemplate) {
            $catalogMenuActiveKey = $key;
            break;
        }
    }
}

$catalogMenuHost = $_SERVER['HTTP_HOST'] ?? '';
$catalogMenuShowSwitcher = (
    strpos($catalogMenuHost, 'localhost') !== false
    || strpos($catalogMenuHost, '127.0.0.1') !== false
    || $catalogMenuHost === 'dev.polimer-vrn.ru'
);
