<?php
/** @noinspection DuplicatedCode */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Avito\Export\Admin\View\Attributes;

/** @var array $arParams */
/** @var CBitrixComponent $component */

Loc::loadLanguageFile(__DIR__ . '/template.php');

UI\Extension::load([
	'avitoexport.vendor.select2',
]);

$property = $arParams['~PROPERTY'];
$values = is_array($arParams['~VALUE']) ? $arParams['~VALUE'] : [];
$parentValues = is_array($arParams['~PARENT_VALUE']) ? $arParams['~PARENT_VALUE'] : [];
$control = $arParams['~CONTROL'];
$categoryOptions = $arParams['~CATEGORY_OPTIONS'];
$valueIndex = 0;

$htmlClass = 'avito-export-characteristic-' . $property['ID'];
$tableAttributes = Attributes::stringify([
	'data-value-name' => $control['VALUE'],
	'data-attribute-name' => $control['VALUE'],
	'data-category-options' => $categoryOptions,
]);

if (!empty($parentValues))
{
	$values = $parentValues + $values;
}

$valuesCount = count($values);

$html = <<<HEADER
	<input type="hidden" name="{$control['VALUE']}" value="" />
	<table class="bx-avito-export-characteristic {$htmlClass}" {$tableAttributes}>
HEADER;

foreach ($values as $attribute => $value)
{
	if (isset($value['VALUE']) && trim($value['VALUE']) === '') { continue; }

	$isLast = ($valueIndex === $valuesCount - 1);
	$valueName = $control['VALUE'] . '[' . $attribute .']';

	if (is_array($value))
	{
		$valueName .= '[]';
		$selectAttributes = 'multiple size="1"';
		$optionsHtml = implode('', array_map(function($option) {
			return '<option selected>'. htmlspecialcharsbx($option) .'</option>';
		}, $value));
	}
	else
	{
		$selectAttributes = '';
		$optionsHtml = '<option selected>'. htmlspecialcharsbx($value) .'</option>';
	}


	$deleteTitle = Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_DELETE');
	$deleteAttributes = $isLast ? '' : 'disabled';

	if (isset($parentValues[$attribute]))
	{
		$html .= <<<ROW
			<tr>
				<td class="bx-avito-export-characteristic__label" data-entity="attribute">{$attribute}</td>
				<td class="bx-avito-export-characteristic__value" data-entity="value">
					<select {$selectAttributes} class="bx-avito-export-characteristic__value-control" disabled>
						{$optionsHtml}
					</select>
				</td>
				<td class="bx-avito-export-characteristic__actions">&nbsp;</td>
			</tr>
ROW;
	}
	else
	{
		$html .= <<<ROW
			<tr>
				<td class="bx-avito-export-characteristic__label" data-entity="attribute">{$attribute}</td>
				<td class="bx-avito-export-characteristic__value" data-entity="value">
					<select {$selectAttributes} class="bx-avito-export-characteristic__value-control" name="{$valueName}">
						{$optionsHtml}
					</select>
				</td>
				<td class="bx-avito-export-characteristic__actions">
					<button class="bx-avito-export-characteristic__delete" type="button" data-entity="delete" {$deleteAttributes}>{$deleteTitle}</button>
				</td>
			</tr>
ROW;
	}

	++$valueIndex;
}

$addTitle = Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_ADD');
$pluginOptions = Json::encode([
	'component' => $this->getComponent()->getName(),
	'lang' => [
		'LOADING' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_LOADING'),
		'DELETE' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_DELETE'),
		'ERROR_ATTRIBUTE' => Loc::getMessage('AVITO_EXPORT_ADMIN_PROPERTY_CHARACTERISTIC_EMPTY_ATTRIBUTE'),
	],
]);

$html .= <<<FOOTER
	</table>
	<input class="adm-btn bx-avito-export-characteristic__add" type="button" value="{$addTitle}">
	<script>
		BX.ready(function() {
            setTimeout(function() {
                const elements = document.getElementsByClassName("{$htmlClass}");
                const readyToken = "avito-characteristic--ready";
                
                for (const element of elements) {
                    if (element.classList.contains(readyToken)) { continue; }
                    
                    // noinspection BadExpressionStatementJS
                    new BX.AvitoExport.Admin.Property.Characteristic.SingleField(element, {$pluginOptions})
                    
                    element.classList.add(readyToken);
                }
            });
	    });
	</script>
FOOTER;

$component->arResult['HTML'] = $html;