<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Агенты');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:system.agent.list',
    '',
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>