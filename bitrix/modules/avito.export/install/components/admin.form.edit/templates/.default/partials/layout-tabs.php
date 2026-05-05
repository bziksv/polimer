<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\UI\Extension;

global $APPLICATION;

/**
 * @var $component \Avito\Export\Components\AdminFormEdit
 * @var array $arResult
 * @var array $arParams
 */

Extension::load('avitoexport.admin.style');

if (!empty($arResult['CONTEXT_MENU']))
{
    $context = new CAdminContextMenu($arResult['CONTEXT_MENU']);
    $context->Show();
}

if ($component->hasErrors())
{
    $component->showErrors();
}

if (!empty($arParams['~MESSAGE']))
{
    echo (new CAdminMessage($arParams['~MESSAGE']))->Show();
}

$tabControl = new \CAdminTabControl($arParams['FORM_ID'], $arResult['TABS'], false, true);
$formActionUri = !empty($arParams['FORM_ACTION_URI']) ? $arParams['FORM_ACTION_URI'] : htmlspecialcharsbx($APPLICATION->GetCurPageParam());

?>
<form method="POST" action="<?= $formActionUri ?>">
    <?php
    if ($arParams['FORM_BEHAVIOR'] === 'steps')
    {
        ?>
        <input type="hidden" name="STEP" value="<?=$arResult['STEP'];?>"/>
        <?php
    }

    echo bitrix_sessid_post();

    $tabControl->Begin();
    $tabIndex = 0;

    foreach ($arResult['TABS'] as $tab)
    {
        $tabControl->BeginNextTab(['showTitle' => false]);

        $isActiveTab = ($arParams['FORM_BEHAVIOR'] !== 'steps' || $tab['STEP'] === $arResult['STEP']);
        $tabLayout = $tab['LAYOUT'] ?: 'default';
        $fields = $tab['FIELDS'];
        $hasVisibleFields = false;

        foreach ($fields as $siblingKey)
        {
            $field = $component->getField($siblingKey);

            if (empty($field['DEPEND_HIDDEN']))
            {
                $hasVisibleFields = true;
                break;
            }
        }

        if (!$hasVisibleFields)
        {
            ?>
            <script>document.getElementById('tab_cont_tab<?= $tabIndex ?>').classList.add('is--hidden')</script>
            <?php
        }

        include __DIR__ . '/hidden.php';
        include __DIR__ . '/tab-' . $tabLayout . '.php';

        ++$tabIndex;
    }

    $tabControl->Buttons();

    include __DIR__ . '/buttons.php';

    $tabControl->End();
    ?>
</form>
<?php
if ($arParams['FORM_BEHAVIOR'] === 'steps')
{
    ?>
    <script>
        <?php
        foreach ($arResult['TABS'] as $tab)
        {
	        if ($tab['STEP'] !== $arResult['STEP'])
	        {
		        ?>
		        <?= $arParams['FORM_ID']; ?>.DisableTab('<?= $tab['DIV']; ?>');
		        <?php
	        }
        }
        ?>
    </script>
    <?php
}
