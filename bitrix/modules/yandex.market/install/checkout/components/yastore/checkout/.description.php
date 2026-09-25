<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => 'Экспресс-чекаут YCP API',
    'DESCRIPTION' => 'JSON API для YCP: склады, корзина, заказы, настройки. ЧПУ api/v1/… и legacy ?method=.',
    'ICON' => '/images/icon.gif',
    'COMPLEX' => 'Y',
    'PATH' => [
        'ID' => 'yastore',
        'NAME' => 'YaStore',
        'CHILD' => [
            'ID' => 'checkout',
            'NAME' => 'Checkout API',
            'SORT' => 100,
            'CHILD' => [
                'ID' => 'checkout_api',
            ],
        ],
    ],
];
