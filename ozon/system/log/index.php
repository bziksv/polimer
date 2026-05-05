<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Логирование запросов');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:system.log',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => '#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '/ozon/system/log/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>