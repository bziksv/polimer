<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Bitrix\Main;

/** @var $component \Avito\Export\Components\AdminFormEdit */
/** @var CMain $APPLICATION */
/** @var array $arResult */

$formActionUri = !empty($arParams['FORM_ACTION_URI'])
    ? $arParams['FORM_ACTION_URI']
    : htmlspecialcharsbx($APPLICATION->GetCurPageParam());

if ($component->hasErrors())
{
    $component->showErrors();
}

if ($arResult['SUCCESS'])
{
    $data = [
        'action' => $arResult['ACTION'],
        'primary' => $arResult['PRIMARY'],
        'data' => $arResult['ITEM'],
    ];

    ?>
    <script>
        (function() {
            let levelWindow = window;

            while (levelWindow) {
                if (levelWindow.BX) {
                    levelWindow.BX.onCustomEvent('avitoExportFormSave', [<?= Main\Web\Json::encode($data, JSON_UNESCAPED_UNICODE) ?>]);
                    break;
                }

                levelWindow = levelWindow.parent;
            }
        })();
    </script>
    <?php

    if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) { die(); }
}

$this->addExternalCss('/bitrix/panel/main/admin-public.css');

?>
<form class="avito-export-form" method="POST" action="<?= $formActionUri ?>" enctype="multipart/form-data" novalidate>
    <?php
    echo bitrix_sessid_post();
    ?>
    <table class="edit-table" width="100%">
        <?php
        foreach ($arResult['TABS'] as $tab)
        {
            $isActiveTab = true;
            $fields = $tab['FIELDS'];

            include __DIR__ . '/hidden.php';
            include __DIR__ . '/tab-default.php';
        }
        ?>
    </table>
</form>