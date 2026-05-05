<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Avito\Export\Admin\View;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var CBitrixComponent $component */

Main\UI\Extension::load([
	'avitoexport.vendor.select2',
]);

$htmlClass = 'avito-export-admin-property-category-' . $arParams['PROPERTY']['ID'];

$addAttributes = $arParams['ADDITIONAL_ATTRIBUTES'] ?? [];
$needSkipInit = ($arParams['SKIP_INIT'] === 'Y');

$attributes = View\Attributes::stringify(array_filter([
	'class' => $needSkipInit ? null : $htmlClass,
	'name' => $arParams['CONTROL_NAME'],
	'multiple' => $arParams['MULTIPLE'] === 'Y',
	'style' => 'width: 400px; max-width: 100%',
]) + $addAttributes);

$options = '';

if ($arParams['ALLOW_NO_VALUE'] !== 'N')
{
	$options = sprintf('<option value="">%s</option>', Loc::getMessage('AVITO_EXPORT_ADMIN_COMPONENT_CATEGORY_NO_VALUE'));
}

$placeholder = Loc::getMessage('AVITO_EXPORT_ADMIN_COMPONENT_CATEGORY_SELECT_PLACEHOLDER');

if (!empty($arParams['PARENT_VALUE']))
{
	$placeholder = is_array($arParams['PARENT_VALUE']) ? implode(', ', $arParams['PARENT_VALUE']) : $arParams['PARENT_VALUE'];
}

if ($arParams['MULTIPLE'] === 'Y')
{
	$values = is_array($arParams['VALUE']) ? $arParams['VALUE'] : [];
}
else
{
	$values = [ $arParams['VALUE'] ];
}

foreach ($values as $value)
{
	if (empty($value)) { continue; }

	$options .= '<option selected>' . $value . '</option>';
}

$html = <<<SELECT
	<select {$attributes}>
		{$options}
	</select>
SELECT;

if (!$needSkipInit)
{
	$pluginOptions = Main\Web\Json::encode([
		'component' => $this->getComponent()->getName(),
		'language' => LANGUAGE_ID,
		'lang' => [
			'VALUE_PLACEHOLDER' => $placeholder,
		],
	], JSON_INVALID_UTF8_IGNORE);

	$html .= <<<SCRIPT
		<script>
			BX.ready(function() {
                setTimeout(function() {
                    const elements = document.getElementsByClassName("{$htmlClass}");
                    const readyToken = "avito-category--ready";
                    
                    for (const element of elements) {
                        if (element.classList.contains(readyToken)) { continue; }
                        
                        // noinspection BadExpressionStatementJS
                        new BX.AvitoExport.Admin.Property.Category(element, {$pluginOptions});
                        
                        element.classList.add(readyToken);
                    }
			    });
			});
		</script>
SCRIPT;
}

$component->arResult['HTML'] = $html;
