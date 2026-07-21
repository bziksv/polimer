<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Type\Collection;

$arParams['TEMPLATE_THEME'] = (string)($arParams['TEMPLATE_THEME']);
if ($arParams['TEMPLATE_THEME'] != '')
{
	$arParams['TEMPLATE_THEME'] = preg_replace('/[^a-zA-Z0-9_\-\(\)\!]/', '', $arParams['TEMPLATE_THEME']);
	if ($arParams['TEMPLATE_THEME'] == 'site')
	{
		$templateId = COption::GetOptionString("main", "wizard_template_id", "eshop_bootstrap", SITE_ID);
		$templateId = (preg_match("/^eshop_adapt/", $templateId)) ? "eshop_adapt" : $templateId;
		$arParams['TEMPLATE_THEME'] = COption::GetOptionString('main', 'wizard_'.$templateId.'_theme_id', 'blue', SITE_ID);
	}
	if ($arParams['TEMPLATE_THEME'] != '')
	{
		if (!is_file($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/themes/'.$arParams['TEMPLATE_THEME'].'/style.css'))
			$arParams['TEMPLATE_THEME'] = '';
	}
}
if ($arParams['TEMPLATE_THEME'] == '')
	$arParams['TEMPLATE_THEME'] = 'blue';

$arResult['ALL_FIELDS'] = array();
$existShow = !empty($arResult['SHOW_FIELDS']);
$existDelete = !empty($arResult['DELETED_FIELDS']);
if ($existShow || $existDelete)
{
	if ($existShow)
	{
		foreach ($arResult['SHOW_FIELDS'] as $propCode)
		{
			$arResult['SHOW_FIELDS'][$propCode] = array(
				'CODE' => $propCode,
				'IS_DELETED' => 'N',
				'ACTION_LINK' => str_replace('#CODE#', $propCode, $arResult['~DELETE_FEATURE_FIELD_TEMPLATE']),
				'SORT' => $arResult['FIELDS_SORT'][$propCode]
			);
		}
		unset($propCode);
		$arResult['ALL_FIELDS'] = $arResult['SHOW_FIELDS'];
	}
	if ($existDelete)
	{
		foreach ($arResult['DELETED_FIELDS'] as $propCode)
		{
			$arResult['ALL_FIELDS'][$propCode] = array(
				'CODE' => $propCode,
				'IS_DELETED' => 'Y',
				'ACTION_LINK' => str_replace('#CODE#', $propCode, $arResult['~ADD_FEATURE_FIELD_TEMPLATE']),
				'SORT' => $arResult['FIELDS_SORT'][$propCode]
			);
		}
		unset($propCode, $arResult['DELETED_FIELDS']);
	}
	Collection::sortByColumn($arResult['ALL_FIELDS'], array('SORT' => SORT_ASC));
}

$arResult['ALL_PROPERTIES'] = array();
$existShow = !empty($arResult['SHOW_PROPERTIES']);
$existDelete = !empty($arResult['DELETED_PROPERTIES']);
if ($existShow || $existDelete)
{
	if ($existShow)
	{
		foreach ($arResult['SHOW_PROPERTIES'] as $propCode => $arProp)
		{
			$arResult['SHOW_PROPERTIES'][$propCode]['IS_DELETED'] = 'N';
			$arResult['SHOW_PROPERTIES'][$propCode]['ACTION_LINK'] = str_replace('#CODE#', $propCode, $arResult['~DELETE_FEATURE_PROPERTY_TEMPLATE']);
		}
		$arResult['ALL_PROPERTIES'] = $arResult['SHOW_PROPERTIES'];
	}
	unset($arProp, $propCode);
	if ($existDelete)
	{
		foreach ($arResult['DELETED_PROPERTIES'] as $propCode => $arProp)
		{
			$arResult['DELETED_PROPERTIES'][$propCode]['IS_DELETED'] = 'Y';
			$arResult['DELETED_PROPERTIES'][$propCode]['ACTION_LINK'] = str_replace('#CODE#', $propCode, $arResult['~ADD_FEATURE_PROPERTY_TEMPLATE']);
			$arResult['ALL_PROPERTIES'][$propCode] = $arResult['DELETED_PROPERTIES'][$propCode];
		}
		unset($arProp, $propCode, $arResult['DELETED_PROPERTIES']);
	}
	Collection::sortByColumn($arResult["ALL_PROPERTIES"], array('SORT' => SORT_ASC, 'ID' => SORT_ASC));
}

$arResult["ALL_OFFER_FIELDS"] = array();
$existShow = !empty($arResult["SHOW_OFFER_FIELDS"]);
$existDelete = !empty($arResult["DELETED_OFFER_FIELDS"]);
if ($existShow || $existDelete)
{
	if ($existShow)
	{
		foreach ($arResult["SHOW_OFFER_FIELDS"] as $propCode)
		{
			$arResult["SHOW_OFFER_FIELDS"][$propCode] = array(
				"CODE" => $propCode,
				"IS_DELETED" => "N",
				"ACTION_LINK" => str_replace('#CODE#', $propCode, $arResult['~DELETE_FEATURE_OF_FIELD_TEMPLATE']),
				'SORT' => $arResult['FIELDS_SORT'][$propCode]
			);
		}
		unset($propCode);
		$arResult['ALL_OFFER_FIELDS'] = $arResult['SHOW_OFFER_FIELDS'];
	}
	if ($existDelete)
	{
		foreach ($arResult['DELETED_OFFER_FIELDS'] as $propCode)
		{
			$arResult['ALL_OFFER_FIELDS'][$propCode] = array(
				"CODE" => $propCode,
				"IS_DELETED" => "Y",
				"ACTION_LINK" => str_replace('#CODE#', $propCode, $arResult['~ADD_FEATURE_OF_FIELD_TEMPLATE']),
				'SORT' => $arResult['FIELDS_SORT'][$propCode]
			);
		}
		unset($propCode, $arResult['DELETED_OFFER_FIELDS']);
	}
	Collection::sortByColumn($arResult['ALL_OFFER_FIELDS'], array('SORT' => SORT_ASC));
}

$arResult['ALL_OFFER_PROPERTIES'] = array();
$existShow = !empty($arResult["SHOW_OFFER_PROPERTIES"]);
$existDelete = !empty($arResult["DELETED_OFFER_PROPERTIES"]);
if ($existShow || $existDelete)
{
	if ($existShow)
	{
		foreach ($arResult['SHOW_OFFER_PROPERTIES'] as $propCode => $arProp)
		{
			$arResult["SHOW_OFFER_PROPERTIES"][$propCode]["IS_DELETED"] = "N";
			$arResult["SHOW_OFFER_PROPERTIES"][$propCode]["ACTION_LINK"] = str_replace('#CODE#', $propCode, $arResult['~DELETE_FEATURE_OF_PROPERTY_TEMPLATE']);
		}
		unset($arProp, $propCode);
		$arResult['ALL_OFFER_PROPERTIES'] = $arResult['SHOW_OFFER_PROPERTIES'];
	}
	if ($existDelete)
	{
		foreach ($arResult['DELETED_OFFER_PROPERTIES'] as $propCode => $arProp)
		{
			$arResult["DELETED_OFFER_PROPERTIES"][$propCode]["IS_DELETED"] = "Y";
			$arResult["DELETED_OFFER_PROPERTIES"][$propCode]["ACTION_LINK"] = str_replace('#CODE#', $propCode, $arResult['~ADD_FEATURE_OF_PROPERTY_TEMPLATE']);
			$arResult['ALL_OFFER_PROPERTIES'][$propCode] = $arResult["DELETED_OFFER_PROPERTIES"][$propCode];
		}
		unset($arProp, $propCode, $arResult['DELETED_OFFER_PROPERTIES']);
	}
	Collection::sortByColumn($arResult['ALL_OFFER_PROPERTIES'], array('SORT' => SORT_ASC, 'ID' => SORT_ASC));
}

// --- Вкладки по разделам каталога (как у Ситилинка) ---
$arResult['COMPARE_SECTIONS'] = array();
$arResult['COMPARE_SECTION_ID'] = 0;

// Чистые URL режимов (без текущего мусора в query и без двойного htmlspecialchars)
$comparePage = $APPLICATION->GetCurPage();
$arResult['COMPARE_URL_DIFFERENT_N'] = $comparePage.'?DIFFERENT=N';
$arResult['COMPARE_URL_DIFFERENT_Y'] = $comparePage.'?DIFFERENT=Y';

if (!empty($arResult['ITEMS']) && \Bitrix\Main\Loader::includeModule('iblock'))
{
	$sectionCounts = array();
	foreach ($arResult['ITEMS'] as $arItem)
	{
		$sid = (int)($arItem['IBLOCK_SECTION_ID'] ?? 0);
		if (!isset($sectionCounts[$sid]))
		{
			$sectionCounts[$sid] = 0;
		}
		$sectionCounts[$sid]++;
	}

	$sectionNames = array();
	$idsToLoad = array_filter(array_keys($sectionCounts));
	if ($idsToLoad)
	{
		$rsSect = CIBlockSection::GetList(
			array('LEFT_MARGIN' => 'ASC'),
			array('ID' => $idsToLoad, 'CHECK_PERMISSIONS' => 'N'),
			false,
			array('ID', 'NAME')
		);
		while ($sect = $rsSect->Fetch())
		{
			$sectionNames[(int)$sect['ID']] = $sect['NAME'];
		}
	}

	$sections = array();
	foreach ($sectionCounts as $sid => $cnt)
	{
		$sid = (int)$sid;
		$sections[$sid] = array(
			'ID' => $sid,
			'NAME' => ($sid > 0 && isset($sectionNames[$sid])) ? $sectionNames[$sid] : 'Без раздела',
			'COUNT' => (int)$cnt,
		);
	}

	$requestSid = isset($_REQUEST['csid']) ? (int)$_REQUEST['csid'] : -1;
	if ($requestSid >= 0 && isset($sections[$requestSid]))
	{
		$arResult['COMPARE_SECTION_ID'] = $requestSid;
	}
	elseif (count($sections) > 0)
	{
		$first = reset($sections);
		$arResult['COMPARE_SECTION_ID'] = (int)$first['ID'];
	}

	$different = !empty($arResult['DIFFERENT']) ? 'Y' : 'N';
	foreach ($sections as $sid => &$sect)
	{
		// без htmlspecialchars — экранируем в шаблоне
		$sect['URL'] = $comparePage.'?DIFFERENT='.$different.'&csid='.$sid;
	}
	unset($sect);

	$arResult['COMPARE_SECTIONS'] = array_values($sections);

	if (count($sections) > 1)
	{
		$activeSid = (int)$arResult['COMPARE_SECTION_ID'];
		$filtered = array();
		foreach ($arResult['ITEMS'] as $arItem)
		{
			if ((int)($arItem['IBLOCK_SECTION_ID'] ?? 0) === $activeSid)
			{
				$filtered[] = $arItem;
			}
		}
		$arResult['ITEMS'] = $filtered;
	}

	// Как в карточке: все заполненные публичные свойства, а не урезанный PROPERTY_CODE/features
	if (!empty($arResult['ITEMS']) && function_exists('polimerIsHiddenCatalogProp'))
	{
		$showProps = array();
		foreach ($arResult['ITEMS'] as &$arItem)
		{
			$elementId = (int)($arItem['ID'] ?? 0);
			if ($elementId <= 0)
			{
				continue;
			}

			$obElement = CIBlockElement::GetByID($elementId)->GetNextElement();
			if (!$obElement)
			{
				continue;
			}

			$props = $obElement->GetProperties();
			if (!is_array($props))
			{
				continue;
			}

			$arItem['DISPLAY_PROPERTIES'] = array();
			foreach ($props as $code => $prop)
			{
				$code = (string)$code;
				$propName = (string)($prop['NAME'] ?? '');
				if ($code === '' || polimerIsHiddenCatalogProp($code, $propName))
				{
					continue;
				}
				if (!polimerPropHasPublicValue($prop['VALUE'] ?? null))
				{
					continue;
				}

				$display = CIBlockFormatProperties::GetDisplayValue($arItem, $prop);
				if ($display === false || !is_array($display))
				{
					continue;
				}

				// Для списков/справочников в VALUE часто ID — берём только человекочитаемый DISPLAY_VALUE
				$displayValue = $display['DISPLAY_VALUE'] ?? null;
				if (!polimerPropHasPublicValue($displayValue))
				{
					// fallback: VALUE_ENUM для списков
					if (polimerPropHasPublicValue($prop['VALUE_ENUM'] ?? null))
					{
						$display['DISPLAY_VALUE'] = $prop['VALUE_ENUM'];
					}
					elseif (polimerPropHasPublicValue($prop['VALUE_ENUM_ID'] ?? null) === false
						&& isset($prop['VALUE']) && !is_numeric($prop['VALUE'])
						&& polimerPropHasPublicValue($prop['VALUE']))
					{
						$display['DISPLAY_VALUE'] = $prop['VALUE'];
					}
					else
					{
						continue;
					}
				}

				// Не показываем «голые» ID вместо названий
				$check = $display['DISPLAY_VALUE'];
				if (!is_array($check) && ctype_digit(trim((string)$check)))
				{
					if (polimerPropHasPublicValue($prop['VALUE_ENUM'] ?? null))
					{
						$display['DISPLAY_VALUE'] = is_array($prop['VALUE_ENUM'])
							? implode(', ', $prop['VALUE_ENUM'])
							: $prop['VALUE_ENUM'];
					}
					elseif (($prop['PROPERTY_TYPE'] ?? '') === 'L' || ($prop['USER_TYPE'] ?? '') === 'directory')
					{
						continue;
					}
				}

				$arItem['DISPLAY_PROPERTIES'][$code] = $display;
				if (!isset($showProps[$code]))
				{
					$showProps[$code] = array(
						'ID' => $prop['ID'],
						'CODE' => $code,
						'NAME' => $propName,
						'SORT' => (int)($prop['SORT'] ?? 500),
						'PROPERTY_TYPE' => $prop['PROPERTY_TYPE'] ?? '',
					);
				}
			}
		}
		unset($arItem);

		uasort($showProps, static function ($a, $b) {
			$sa = (int)($a['SORT'] ?? 500);
			$sb = (int)($b['SORT'] ?? 500);
			if ($sa === $sb)
			{
				return strcmp((string)$a['NAME'], (string)$b['NAME']);
			}
			return $sa <=> $sb;
		});

		$arResult['SHOW_PROPERTIES'] = $showProps;
	}
	elseif (!empty($arResult['SHOW_PROPERTIES']) && function_exists('polimerFilterPublicCatalogProps'))
	{
		$arResult['SHOW_PROPERTIES'] = polimerFilterPublicCatalogProps($arResult['SHOW_PROPERTIES']);
	}

	$csid = (int)$arResult['COMPARE_SECTION_ID'];
	$csidSuffix = $csid > 0 ? '&csid='.$csid : '';
	$arResult['COMPARE_URL_DIFFERENT_N'] = $comparePage.'?DIFFERENT=N'.$csidSuffix;
	$arResult['COMPARE_URL_DIFFERENT_Y'] = $comparePage.'?DIFFERENT=Y'.$csidSuffix;
}
?>
