<?php
/**
 * Создаёт UF раздела каталога для разделения «Похожие» / «Для подключения»
 * и включает их на разделе «Радиаторы» (ID 1929):
 *   UF_SPLIT_RELATED = Y
 *   UF_ACCESSORIES_SECTION = 1364 (арматура), 1373 (комплектующие)
 *
 * Запуск (из корня сайта):
 *   php local/tools/setup-related-split-uf.php
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!CModule::IncludeModule('iblock'))
{
	fwrite(STDERR, "iblock module missing\n");
	exit(1);
}

$iblockId = 21;
$entityId = 'IBLOCK_' . $iblockId . '_SECTION';
$radiatorsSectionId = 1929;
$accessoriesSectionIds = [1364, 1373];

function polimerEnsureSectionUf(array $fields): int
{
	$existing = CUserTypeEntity::GetList([], [
		'ENTITY_ID' => $fields['ENTITY_ID'],
		'FIELD_NAME' => $fields['FIELD_NAME'],
	])->Fetch();

	if ($existing)
	{
		echo "UF exists: {$fields['FIELD_NAME']} (#{$existing['ID']})\n";
		return (int)$existing['ID'];
	}

	$ob = new CUserTypeEntity();
	$id = (int)$ob->Add($fields);
	if ($id <= 0)
	{
		global $APPLICATION;
		$err = $APPLICATION->GetException();
		fwrite(STDERR, "Failed to add {$fields['FIELD_NAME']}: " . ($err ? $err->GetString() : 'unknown') . "\n");
		exit(1);
	}

	echo "UF created: {$fields['FIELD_NAME']} (#{$id})\n";
	return $id;
}

polimerEnsureSectionUf([
	'ENTITY_ID' => $entityId,
	'FIELD_NAME' => 'UF_SPLIT_RELATED',
	'USER_TYPE_ID' => 'boolean',
	'XML_ID' => 'UF_SPLIT_RELATED',
	'SORT' => 500,
	'MULTIPLE' => 'N',
	'MANDATORY' => 'N',
	'SHOW_FILTER' => 'N',
	'SHOW_IN_LIST' => 'Y',
	'EDIT_IN_LIST' => 'Y',
	'IS_SEARCHABLE' => 'N',
	'SETTINGS' => [
		'DEFAULT_VALUE' => 0,
		'DISPLAY' => 'CHECKBOX',
		'LABEL' => ['', ''],
		'LABEL_CHECKBOX' => 'Разделять похожие / для подключения',
	],
	'EDIT_FORM_LABEL' => [
		'ru' => 'Разделять похожие / для подключения',
		'en' => 'Split similar / accessories',
	],
	'LIST_COLUMN_LABEL' => [
		'ru' => 'Разделять похожие',
		'en' => 'Split related',
	],
	'HELP_MESSAGE' => [
		'ru' => 'Если включено — на карточке товара два блока: «Похожие» (этот раздел) и «Для подключения» (разделы из поля ниже).',
	],
]);

polimerEnsureSectionUf([
	'ENTITY_ID' => $entityId,
	'FIELD_NAME' => 'UF_ACCESSORIES_SECTION',
	'USER_TYPE_ID' => 'iblock_section',
	'XML_ID' => 'UF_ACCESSORIES_SECTION',
	'SORT' => 510,
	'MULTIPLE' => 'Y',
	'MANDATORY' => 'N',
	'SHOW_FILTER' => 'N',
	'SHOW_IN_LIST' => 'Y',
	'EDIT_IN_LIST' => 'Y',
	'IS_SEARCHABLE' => 'N',
	'SETTINGS' => [
		'IBLOCK_TYPE_ID' => '1c_catalog',
		'IBLOCK_ID' => $iblockId,
		'DISPLAY' => 'LIST',
		'LIST_HEIGHT' => 8,
		'ACTIVE_FILTER' => 'Y',
	],
	'EDIT_FORM_LABEL' => [
		'ru' => 'Разделы комплектующих («Для подключения»)',
		'en' => 'Accessories sections',
	],
	'LIST_COLUMN_LABEL' => [
		'ru' => 'Комплектующие',
		'en' => 'Accessories',
	],
	'HELP_MESSAGE' => [
		'ru' => 'Разделы, из которых брать товары в блок «Для подключения» (для радиаторов: арматура + комплектующие).',
	],
]);

polimerEnsureSectionUf([
	'ENTITY_ID' => $entityId,
	'FIELD_NAME' => 'UF_ACCESSORIES_TITLE',
	'USER_TYPE_ID' => 'string',
	'XML_ID' => 'UF_ACCESSORIES_TITLE',
	'SORT' => 520,
	'MULTIPLE' => 'N',
	'MANDATORY' => 'N',
	'SHOW_FILTER' => 'N',
	'SHOW_IN_LIST' => 'Y',
	'EDIT_IN_LIST' => 'Y',
	'IS_SEARCHABLE' => 'N',
	'SETTINGS' => [
		'SIZE' => 50,
		'ROWS' => 1,
		'REGEXP' => '',
		'MIN_LENGTH' => 0,
		'MAX_LENGTH' => 0,
		'DEFAULT_VALUE' => '',
	],
	'EDIT_FORM_LABEL' => [
		'ru' => 'Заголовок блока комплектующих',
		'en' => 'Accessories block title',
	],
	'LIST_COLUMN_LABEL' => [
		'ru' => 'Заголовок комплектующих',
		'en' => 'Accessories title',
	],
	'HELP_MESSAGE' => [
		'ru' => 'Если заполнено — вместо «Для подключения» на карточке товара. Пусто = «Для подключения».',
	],
]);

$section = CIBlockSection::GetList(
	[],
	['IBLOCK_ID' => $iblockId, 'ID' => $radiatorsSectionId, 'CHECK_PERMISSIONS' => 'N'],
	false,
	['ID', 'NAME', 'UF_SPLIT_RELATED', 'UF_ACCESSORIES_SECTION']
)->Fetch();

if (!$section)
{
	fwrite(STDERR, "Section {$radiatorsSectionId} not found in iblock {$iblockId}\n");
	exit(1);
}

$ok = (new CIBlockSection())->Update($radiatorsSectionId, [
	'UF_SPLIT_RELATED' => 1,
	'UF_ACCESSORIES_SECTION' => $accessoriesSectionIds,
]);

if (!$ok)
{
	global $APPLICATION;
	$err = $APPLICATION->GetException();
	fwrite(STDERR, 'Section update failed: ' . ($err ? $err->GetString() : 'unknown') . "\n");
	exit(1);
}

echo "Section #{$radiatorsSectionId} «{$section['NAME']}»: split ON, accessories=["
	. implode(',', $accessoriesSectionIds) . "]\n";
echo "Done.\n";
