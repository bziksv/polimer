<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
/** @var \CBitrixComponent $component */
/** @var array $arResult */

include __DIR__. '/partials/messages.php';
?>
<div class="avito-export-editor-wrapper" id="AVITO_EXPORT_ORDER_VIEW">
	<span class="avito-export-editor-wrapper__title"><?= Loc::getMessage('AVITO_EXPORT_ORDER_VIEW_TITLE', [
		'#EXTERNAL_ID#' => $arResult['EXTERNAL_ID'],
	]) ?></span>
	<button
		class="ui-btn ui-btn-link ui-btn-xs avito-export-editor-wrapper__refresh"
		type="button"
		onclick="BX.UI.EntityEditor.items['avito-export-order-tab'].reload()"
	><?= Loc::getMessage('AVITO_EXPORT_ORDER_VIEW_REFERSH') ?></button>
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:ui.form',
		'',
		$arResult['EDITOR'] + [
			'GUID' => 'avito-export-order-tab',
			'CONFIG_ID' => 'avito-export-order-tab-' . $arResult['SETUP_ID'],
			'ENTITY_TYPE_ID' => null,
			'ENTITY_TYPE_NAME' => 'avito-export-order',
			'ENTITY_ID' => $arResult['EXTERNAL_ID'],
			'INITIAL_MODE' => 'view',
			'ENABLE_SECTION_EDIT' => false,
			'ENABLE_SECTION_CREATION' => false,
			'ENABLE_USER_FIELD_CREATION' => false,
			'ENABLE_SECTION_DRAG_DROP' => false,
			'ENABLE_FIELD_DRAG_DROP' => false,
			'ENABLE_FIELDS_CONTEXT_MENU' => false,
			'FORCE_DEFAULT_CONFIG' => true,
			'ENABLE_AJAX_FORM' => false,
			'READ_ONLY' => false,
			'SERVICE_URL' => '/bitrix/admin/avito_export_trading_order.php',
			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $component->getName(),
				'SIGNED_PARAMETERS' => $component->getSignedParameters(),
				'RELOAD_ACTION_NAME' => 'reload',
			],
			'CONTEXT_ID' => 'avito-export-order-' . $arResult['EXTERNAL_ID'],
			'CONTEXT' => [
				'EXTERNAL_ID' => $arResult['EXTERNAL_ID'],
				'SETUP_ID' => $arResult['SETUP_ID'],
			],
		],
		$component,
		[ 'HIDE_ICONS' => 'Y' ]
	);
	?>
</div>
