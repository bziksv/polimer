<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Права доступа');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:settings.access',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
        ],
        'SEF_FOLDER' => '#SITE_DIR#settings/access/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>