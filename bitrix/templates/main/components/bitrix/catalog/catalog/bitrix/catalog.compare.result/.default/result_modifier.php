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

		if (!empty($arResult['SHOW_PROPERTIES']))
		{
			foreach ($arResult['SHOW_PROPERTIES'] as $code => $arProp)
			{
				$hasValue = false;
				foreach ($arResult['ITEMS'] as $arItem)
				{
					$val = $arItem['DISPLAY_PROPERTIES'][$code]['VALUE'] ?? null;
					if ($val !== null && $val !== '' && $val !== array())
					{
						$hasValue = true;
						break;
					}
				}
				if (!$hasValue)
				{
					unset($arResult['SHOW_PROPERTIES'][$code]);
				}
			}
		}
	}

	// Служебные / маркетплейс-свойства не показываем в сравнении
	if (!empty($arResult['SHOW_PROPERTIES']) && function_exists('polimerFilterPublicCatalogProps'))
	{
		$arResult['SHOW_PROPERTIES'] = polimerFilterPublicCatalogProps($arResult['SHOW_PROPERTIES']);
	}

	$csid = (int)$arResult['COMPARE_SECTION_ID'];
	$csidSuffix = $csid > 0 ? '&csid='.$csid : '';
	$arResult['COMPARE_URL_DIFFERENT_N'] = $comparePage.'?DIFFERENT=N'.$csidSuffix;
	$arResult['COMPARE_URL_DIFFERENT_Y'] = $comparePage.'?DIFFERENT=Y'.$csidSuffix;
}
?>
