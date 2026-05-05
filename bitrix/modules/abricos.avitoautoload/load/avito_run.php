<?
//<title>Avito</title>
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @var int $IBLOCK_ID */
/** @var string $SETUP_SERVER_NAME */
/** @var string $SETUP_FILE_NAME */
/** @var array $V */
/** @var array|string $XML_DATA */
/** @var bool $firstStep */
/** @var int $CUR_ELEMENT_ID */
/** @var bool $finalExport */
/** @var bool $boolNeedRootSection */
/** @var int $intMaxSectionID */

use Bitrix\Main,
	Bitrix\Currency,
	Bitrix\Iblock,
	Bitrix\Catalog;
    if (CModule::IncludeModuleEx("abricos.avitoautoload")==2 or CModule::IncludeModuleEx("abricos.avitoautoload")==1)
{
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_yandex.php');
IncludeModuleLangFile(__FILE__);

$MAX_EXECUTION_TIME = (isset($MAX_EXECUTION_TIME) ? (int)$MAX_EXECUTION_TIME : 0);
if ($MAX_EXECUTION_TIME <= 0)
	$MAX_EXECUTION_TIME = 0;
if (defined('BX_CAT_CRON') && BX_CAT_CRON == true)
{
	$MAX_EXECUTION_TIME = 0;
	$firstStep = true;
}
if (defined("CATALOG_EXPORT_NO_STEP") && CATALOG_EXPORT_NO_STEP == true)
{
	$MAX_EXECUTION_TIME = 0;
	$firstStep = true;
}
if ($MAX_EXECUTION_TIME == 0)
	set_time_limit(0);

$CHECK_PERMISSIONS = (isset($CHECK_PERMISSIONS) && $CHECK_PERMISSIONS == 'Y' ? 'Y' : 'N');
if ($CHECK_PERMISSIONS == 'Y')
	$permissionFilter = array('CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'R', 'PERMISSIONS_BY' => 0);
else
	$permissionFilter = array('CHECK_PERMISSIONS' => 'N');

if (!isset($firstStep))
	$firstStep = true;
if(CAbricosAvitoautoload::CurFile()==true)  {
	$MAX_EXECUTION_TIME =0;
	}
$pageSize = 10;
$navParams = array('nTopCount' => $pageSize);

$SETUP_VARS_LIST = 'IBLOCK_ID,SITE_ID,V,XML_DATA,SETUP_SERVER_NAME,COMPANY_NAME,AVITO_CATEGORY_TYPE,AVITO_CONDITION,AVITO_CATEGORY,AVITO_APPAREL,AVITO_LISTINGFEE,AVITO_ADRESS,AVITO_ADTYPE,AVITO_SIZE,AVITO_PRICE,AVITO_VIDEO,AVITO_PHONE,SETUP_FILE_NAME,AVITO_CATEGORY_CUSTOM,AVITO_FILTER,USE_HTTPS,FILTER_AVAILABLE,DISABLE_REFERERS,MAX_EXECUTION_TIME,CHECK_PERMISSIONS,FILTER_INPUT,FILTER_PREF,FILTER_DATA,ABRICOS_AVITO_PHOTO,AVITO_TITLE_PROP,AVITO_NODETAIL_PHOTO,AVITO_NOPHOTO,AVITO_NODESCR,AVITO_DESCR_PROP,AVITO_STORE,AVITO_PRICE_MAX,AVITO_TITLE_TEMPL,AVITO_DESCR_TEMPL,AVITO_PROP_PRICE,DISPLAYAREAS,AVITO_CONDITION_ALT,PROP_DATA,PROP_NAME,PROP_DEF,AVITO_STOCK';
$INTERNAL_VARS_LIST = 'intMaxSectionID,boolNeedRootSection,arSectionIDs,arAvailGroups';
$SETUP_FILE_NAME_STOCK=substr($SETUP_FILE_NAME,0,-4).'_stock.php';
global $USER, $APPLICATION;
$bTmpUserCreated = false;
if (!CCatalog::IsUserExists())
{
	$bTmpUserCreated = true;
	if (isset($USER))
		$USER_TMP = $USER;
	$USER = new CUser();
}

CCatalogDiscountSave::Disable();
/** @noinspection PhpDeprecationInspection */
CCatalogDiscountCoupon::ClearCoupon();
if (empty($USER))
	$USER->Authorize(1);
if ($USER->IsAuthorized())
{
	/** @noinspection PhpDeprecationInspection */
	CCatalogDiscountCoupon::ClearCouponsByManage($USER->GetID());
}

$arYandexFields = array(
	'typePrefix', 'name', 	'description', 'price_min', 'price_max',
	'options', 'param'
);

$formatList = array(
	'none' => array(
		'vendor', 'vendorCode', 'sales_notes', 'manufacturer_warranty', 'country_of_origin',
		'adult'
	)

);

if (!function_exists("yandex_replace_special"))
{
	function yandex_replace_special($arg)
	{
		if (in_array($arg[0], array("&quot;", "&amp;", "&lt;", "&gt;")))
			return $arg[0];
		else
			return " ";
	}
}

if (!function_exists("yandex_text2xml"))
{
	function yandex_text2xml($text, $bHSC = false, $bDblQuote = false)
	{
		global $APPLICATION;

		$bHSC = (true == $bHSC ? true : false);
		$bDblQuote = (true == $bDblQuote ? true: false);

		if ($bHSC)
		{
			$text = htmlspecialcharsbx($text);
			if ($bDblQuote)
				$text = str_replace('&quot;', '"', $text);
		}
		$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
		$text = str_replace("'", "&apos;", $text);

		if (LANG_CHARSET!=='UTF-8')
		$text = $APPLICATION->ConvertCharset($text, LANG_CHARSET, 'UTF-8');
		return $text;
	}
}

if (!function_exists('yandex_get_value'))
{
function yandex_get_value($arOffer, $param, $PROPERTY, $arProperties, $arUserTypeFormat, $usedProtocol)
{
	global $iblockServerName;

	$strProperty = '';
	$bParam = (strncmp($param, 'PARAM_', 6) == 0);
	if (isset($arProperties[$PROPERTY]) && !empty($arProperties[$PROPERTY]))
	{
		$iblockProperty = $arProperties[$PROPERTY];
		$PROPERTY_CODE = $iblockProperty['CODE'];
		if (!isset($arOffer['PROPERTIES'][$PROPERTY_CODE]) && !isset($arOffer['PROPERTIES'][$PROPERTY]))
			return $strProperty;
		$arProperty = (
			isset($arOffer['PROPERTIES'][$PROPERTY_CODE])
			? $arOffer['PROPERTIES'][$PROPERTY_CODE]
			: $arOffer['PROPERTIES'][$PROPERTY]
		);
		if ($arProperty['ID'] != $PROPERTY)
			return $strProperty;

		$value = '';
		$description = '';
		switch ($iblockProperty['PROPERTY_TYPE'])
		{
			case 'USER_TYPE':
				if ($iblockProperty['MULTIPLE'] == 'Y')
				{
					if (!empty($arProperty['~VALUE']))
					{
						$arValues = array();
						foreach($arProperty["~VALUE"] as $oneValue)
						{
							$isArray = is_array($oneValue);
							if (
								($isArray && !empty($oneValue))
								|| (!$isArray && $oneValue != '')
							)
							{
								$arValues[] = call_user_func_array($arUserTypeFormat[$PROPERTY],
									array(
										$iblockProperty,
										array("VALUE" => $oneValue),
										array('MODE' => 'SIMPLE_TEXT'),
									)
								);
							}
						}
						$value = implode(', ', $arValues);
					}
				}
				else
				{
					$isArray = is_array($arProperty['~VALUE']);
					if (
						($isArray && !empty($arProperty['~VALUE']))
						|| (!$isArray && $arProperty['~VALUE'] != '')
					)
					{
						$value = call_user_func_array($arUserTypeFormat[$PROPERTY],
							array(
								$iblockProperty,
								array("VALUE" => $arProperty["~VALUE"]),
								array('MODE' => 'SIMPLE_TEXT'),
							)
						);
					}
				}
				break;
			case Iblock\PropertyTable::TYPE_ELEMENT:
				if (!empty($arProperty['VALUE']))
				{
					$arCheckValue = array();
					if (!is_array($arProperty['VALUE']))
					{
						$arProperty['VALUE'] = (int)$arProperty['VALUE'];
						if ($arProperty['VALUE'] > 0)
							$arCheckValue[] = $arProperty['VALUE'];
					}
					else
					{
						foreach ($arProperty['VALUE'] as $intValue)
						{
							$intValue = (int)$intValue;
							if ($intValue > 0)
								$arCheckValue[] = $intValue;
						}
						unset($intValue);
					}
					if (!empty($arCheckValue))
					{
						$filter = array(
							'@ID' => $arCheckValue
						);
						if ($iblockProperty['LINK_IBLOCK_ID'] > 0)
							$filter['=IBLOCK_ID'] = $iblockProperty['LINK_IBLOCK_ID'];

						$iterator = Iblock\ElementTable::getList(array(
							'select' => array('ID', 'NAME'),
							'filter' => array($filter)
						));
						while ($row = $iterator->fetch())
						{
							$value .= ($value ? ', ' : '').$row['NAME'];
						}
						unset($row, $iterator);
					}
				}
				break;
			case Iblock\PropertyTable::TYPE_SECTION:
				if (!empty($arProperty['VALUE']))
				{
					$arCheckValue = array();
					if (!is_array($arProperty['VALUE']))
					{
						$arProperty['VALUE'] = (int)$arProperty['VALUE'];
						if ($arProperty['VALUE'] > 0)
							$arCheckValue[] = $arProperty['VALUE'];
					}
					else
					{
						foreach ($arProperty['VALUE'] as $intValue)
						{
							$intValue = (int)$intValue;
							if ($intValue > 0)
								$arCheckValue[] = $intValue;
						}
						unset($intValue);
					}
					if (!empty($arCheckValue))
					{
						$filter = array(
							'@ID' => $arCheckValue
						);
						if ($iblockProperty['LINK_IBLOCK_ID'] > 0)
							$filter['=IBLOCK_ID'] = $iblockProperty['LINK_IBLOCK_ID'];

						$iterator = Iblock\SectionTable::getList(array(
							'select' => array('ID', 'NAME'),
							'filter' => array($filter)
						));
						while ($row = $iterator->fetch())
						{
							$value .= ($value ? ', ' : '').$row['NAME'];
						}
						unset($row, $iterator);
					}
				}
				break;
			case Iblock\PropertyTable::TYPE_LIST:
				if (!empty($arProperty['~VALUE']) || $arProperty['~VALUE'] === 0 || $arProperty['~VALUE'] === 0.0 ||$arProperty['~VALUE'] === '0')
				{
					if (is_array($arProperty['~VALUE']))
						$value .= implode(', ', $arProperty['~VALUE']);
					else
						$value .= $arProperty['~VALUE'];
				}
				break;
			case Iblock\PropertyTable::TYPE_FILE:
				if (!empty($arProperty['VALUE']))
				{
					if (is_array($arProperty['VALUE']))
					{
						foreach ($arProperty['VALUE'] as $intValue)
						{
							$intValue = (int)$intValue;
							if ($intValue > 0)
							{
								if ($ar_file = CFile::GetFileArray($intValue))
								{
									if(substr($ar_file["SRC"], 0, 1) == "/")
										$strFile = $usedProtocol.$iblockServerName.CHTTP::urnEncode($ar_file['SRC'], 'utf-8');
									else
										$strFile = $ar_file["SRC"];
									$value .= ($value ? ', ' : '').$strFile;
								}
							}
						}
						unset($intValue);
					}
					else
					{
						$arProperty['VALUE'] = (int)$arProperty['VALUE'];
						if ($arProperty['VALUE'] > 0 )
						{
							if ($ar_file = CFile::GetFileArray($arProperty['VALUE']))
							{
								if(substr($ar_file["SRC"], 0, 1) == "/")
									$strFile = $usedProtocol.$iblockServerName.CHTTP::urnEncode($ar_file['SRC'], 'utf-8');
								else
									$strFile = $ar_file["SRC"];
								$value = $strFile;
							}
						}
					}
				}
				break;
			default:
				if ($bParam && $iblockProperty['WITH_DESCRIPTION'] == 'Y')
				{
					$description = $arProperty['~DESCRIPTION'];
					$value = $arProperty['~VALUE'];
				}
				else
				{
					$value = is_array($arProperty['~VALUE']) ? implode(', ', $arProperty['~VALUE']) : $arProperty['~VALUE'];
				}
		}

		// !!!! check multiple properties and properties like CML2_ATTRIBUTES

		if ($bParam)
		{
			if (is_array($description))
			{
				foreach ($value as $key => $val)
				{
					$strProperty .= $strProperty ? " " : "";
					if($val || $val === 0 || $val === 0.0 ||$val === '0')
					$strProperty .= '<b>'.yandex_text2xml($description[$key], true).'</b>: '.
						yandex_text2xml($val).'';
				}
			}
			else
			{   if($value || $value === 0 || $value === 0.0 ||$value === '0')
				$strProperty .= '<b>'.yandex_text2xml($iblockProperty['NAME'], true).'</b>: '.
					yandex_text2xml($value).'';
			}
		}
		else
		{

			$strProperty .= $value;
		}

		unset($iblockProperty);
	}

	return $strProperty;
}
}

$arRunErrors = array();

if (isset($XML_DATA))
{
	if (is_string($XML_DATA) && CheckSerializedData($XML_DATA))
		$XML_DATA = unserialize(stripslashes($XML_DATA));
}
if (!isset($XML_DATA) || !is_array($XML_DATA))
	$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_XML_DATA');

$yandexFormat = 'none';
if (isset($XML_DATA['TYPE']) && isset($formatList[$XML_DATA['TYPE']]))
	$yandexFormat = $XML_DATA['TYPE'];

$productFormat = ($yandexFormat != 'none' ? ' type="'.htmlspecialcharsbx($yandexFormat).'"' : '');

$fields = array();
$parametricFields = array();
$fieldsExist = !empty($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']);
$parametricFieldsExist = false;
if ($fieldsExist)
{
	foreach ($XML_DATA['XML_DATA'] as $key => $value)
	{
		if ($key == 'PARAMS')
			$parametricFieldsExist = (!empty($value) && is_array($value));
		if (is_array($value))
			continue;
		$value = (string)$value;
		if ($value == '')
			continue;
		$fields[$key] = $value;
	}
	unset($key, $value);
	$fieldsExist = !empty($fields);
}

if ($parametricFieldsExist)
{
	$parametricFields = $XML_DATA['XML_DATA']['PARAMS'];
	if (!empty($parametricFields))
	{
		foreach (array_keys($parametricFields) as $index)
		{
			if ((string)$parametricFields[$index] === '')
				unset($parametricFields[$index]);
		}
	}
	$parametricFieldsExist = !empty($parametricFields);
}

$needProperties = $fieldsExist || $parametricFieldsExist;
$yandexNeedPropertyIds = array();
if ($fieldsExist)
{
	foreach ($fields as $id)
		$yandexNeedPropertyIds[$id] = true;
	unset($id);
}
if ($parametricFieldsExist)
{
	foreach ($parametricFields as $id)
		$yandexNeedPropertyIds[$id] = true;
	unset($id);
}

$commonFields = [
	'DESCRIPTION' => 'PREVIEW_TEXT'
];
if (!empty($XML_DATA['COMMON_FIELDS']) && is_array($XML_DATA['COMMON_FIELDS']))
	$commonFields = array_merge($commonFields, $XML_DATA['COMMON_FIELDS']);
$descrField = $commonFields['DESCRIPTION'];

$propertyFields = array(
	'ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE'
);

$IBLOCK_ID = (int)$IBLOCK_ID;
$db_iblock = CIBlock::GetByID($IBLOCK_ID);
if (!($ar_iblock = $db_iblock->Fetch()))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_FOUND_EXT'));
}
/*elseif (!CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, 'iblock_admin_display'))
{
	$arRunErrors[] = str_replace('#IBLOCK_ID#',$IBLOCK_ID,GetMessage('CET_ERROR_IBLOCK_PERM'));
} */
else
{
	$ar_iblock['PROPERTY'] = array();
	$rsProps = \CIBlockProperty::GetList(
		array('SORT' => 'ASC', 'NAME' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
	);
	while ($arProp = $rsProps->Fetch())
	{
		$arProp['ID'] = (int)$arProp['ID'];
		$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
		$arProp['CODE'] = (string)$arProp['CODE'];
		if ($arProp['CODE'] == '')
			$arProp['CODE'] = $arProp['ID'];
		$arProp['LINK_IBLOCK_ID'] = (int)$arProp['LINK_IBLOCK_ID'];
		$ar_iblock['PROPERTY'][$arProp['ID']] = $arProp;
	}
}
$SETUP_SERVER_NAME = (isset($SETUP_SERVER_NAME) ? trim($SETUP_SERVER_NAME) : '');
$COMPANY_NAME = (isset($COMPANY_NAME) ? trim($COMPANY_NAME) : '');
$SITE_ID = (isset($SITE_ID) ? (string)$SITE_ID : '');
if ($SITE_ID === '')
	$SITE_ID = $ar_iblock['LID'];
$iterator = Main\SiteTable::getList(array(
	'select' => array('LID', 'SERVER_NAME', 'SITE_NAME', 'DIR'),
	'filter' => array('=LID' => $SITE_ID, '=ACTIVE' => 'Y')
));
$site = $iterator->fetch();
unset($iterator);
if (empty($site))
{
	$arRunErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SITE');
}
else
{
	$site['SITE_NAME'] = (string)$site['SITE_NAME'];
	if ($site['SITE_NAME'] === '')
		$site['SITE_NAME'] = (string)Main\Config\Option::get('main', 'site_name');
	$site['COMPANY_NAME'] = $COMPANY_NAME;
	if ($site['COMPANY_NAME'] === '')
		$site['COMPANY_NAME'] = (string)Main\Config\Option::get('main', 'site_name');
	$site['SERVER_NAME'] = (string)$site['SERVER_NAME'];
	if ($SETUP_SERVER_NAME !== '')
		$site['SERVER_NAME'] = $SETUP_SERVER_NAME;
	if ($site['SERVER_NAME'] === '')
	{
		$site['SERVER_NAME'] = (defined('SITE_SERVER_NAME')
			? SITE_SERVER_NAME
			: (string)Main\Config\Option::get('main', 'server_name')
		);
	}
	if ($site['SERVER_NAME'] === '')
	{
		$arRunErrors[] = GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_BAD_SERVER_NAME');
	}
}

global $iblockServerName;
$iblockServerName = $site['SERVER_NAME'];

$arProperties = array();
if (isset($ar_iblock['PROPERTY']))
	$arProperties = $ar_iblock['PROPERTY'];

$boolOffers = false;
$arOffers = false;
$arOfferIBlock = false;
$intOfferIBlockID = 0;
$offersCatalog = false;
$arSelectOfferProps = array();
$arSelectedPropTypes = array(
	Iblock\PropertyTable::TYPE_STRING,
	Iblock\PropertyTable::TYPE_NUMBER,
	Iblock\PropertyTable::TYPE_LIST,
	Iblock\PropertyTable::TYPE_ELEMENT,
	Iblock\PropertyTable::TYPE_SECTION
);
$arOffersSelectKeys = array(
	YANDEX_SKU_EXPORT_ALL,
	YANDEX_SKU_EXPORT_MIN_PRICE,
	YANDEX_SKU_EXPORT_PROP,
);
$arCondSelectProp = array(
	'ZERO',
	'NONZERO',
	'EQUAL',
	'NONEQUAL',
);
$arSKUExport = array();

$arCatalog = CCatalogSku::GetInfoByIBlock($IBLOCK_ID);
if (empty($arCatalog))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_IS_CATALOG'));
}
else
{
	$arCatalog['VAT_ID'] = (int)$arCatalog['VAT_ID'];
	$arOffers = CCatalogSku::GetInfoByProductIBlock($IBLOCK_ID);
	if (!empty($arOffers['IBLOCK_ID']))
	{
		$intOfferIBlockID = $arOffers['IBLOCK_ID'];
		$rsOfferIBlocks = CIBlock::GetByID($intOfferIBlockID);
		if (($arOfferIBlock = $rsOfferIBlocks->Fetch()))
		{
			$boolOffers = true;
			$rsProps = \CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'NAME' => 'ASC'),
				array('IBLOCK_ID' => $intOfferIBlockID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while ($arProp = $rsProps->Fetch())
			{
				$arProp['ID'] = (int)$arProp['ID'];
				if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
				{
					$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
					$arProp['CODE'] = (string)$arProp['CODE'];
					if ($arProp['CODE'] == '')
						$arProp['CODE'] = $arProp['ID'];
					$arProp['LINK_IBLOCK_ID'] = (int)$arProp['LINK_IBLOCK_ID'];

					$ar_iblock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
					$arProperties[$arProp['ID']] = $arProp;
					if (in_array($arProp['PROPERTY_TYPE'], $arSelectedPropTypes))
						$arSelectOfferProps[] = $arProp['ID'];
				}
			}
			$arOfferIBlock['LID'] = $site['LID'];
		}
		else
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_OFFERS_IBLOCK_ID');
		}
	}
	if ($boolOffers)
	{
		$offersCatalog = \CCatalog::GetByID($intOfferIBlockID);
		$offersCatalog['VAT_ID'] = (int)$offersCatalog['VAT_ID'];
		if (empty($XML_DATA['SKU_EXPORT']))
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_SKU_SETTINGS_ABSENT');
		}
		else
		{
			$arSKUExport = $XML_DATA['SKU_EXPORT'];;
			if (empty($arSKUExport['SKU_EXPORT_COND']) || !in_array($arSKUExport['SKU_EXPORT_COND'],$arOffersSelectKeys))
			{
				$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_CONDITION_ABSENT');
			}
			if (YANDEX_SKU_EXPORT_PROP == $arSKUExport['SKU_EXPORT_COND'])
			{
				if (empty($arSKUExport['SKU_PROP_COND']) || !is_array($arSKUExport['SKU_PROP_COND']))
				{
					$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
				}
				else
				{
					if (empty($arSKUExport['SKU_PROP_COND']['PROP_ID']) || !in_array($arSKUExport['SKU_PROP_COND']['PROP_ID'],$arSelectOfferProps))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
					}
					if (empty($arSKUExport['SKU_PROP_COND']['COND']) || !in_array($arSKUExport['SKU_PROP_COND']['COND'],$arCondSelectProp))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_COND_ABSENT');
					}
					else
					{
						if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
						{
							if (empty($arSKUExport['SKU_PROP_COND']['VALUES']))
							{
								$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_VALUES_ABSENT');
							}
						}
					}
				}
			}
		}
	}
}

$propertyIdList = array_keys($arProperties);
if (empty($arRunErrors))
{
	if (
		$arCatalog['CATALOG_TYPE'] == CCatalogSku::TYPE_FULL
		|| $arCatalog['CATALOG_TYPE'] == CCatalogSku::TYPE_PRODUCT
	)
		$propertyIdList[] = $arCatalog['SKU_PROPERTY_ID'];
}

$arUserTypeFormat = array();
foreach($arProperties as $key => $arProperty)
{
	$arUserTypeFormat[$arProperty['ID']] = false;
	if ($arProperty['USER_TYPE'] == '')
		continue;

	$arUserType = \CIBlockProperty::GetUserType($arProperty['USER_TYPE']);
	if (isset($arUserType['GetPublicViewHTML']))
	{
		$arUserTypeFormat[$arProperty['ID']] = $arUserType['GetPublicViewHTML'];
		$arProperties[$key]['PROPERTY_TYPE'] = 'USER_TYPE';
	}
}

$bAllSections = false;
$arSections = array();
if (empty($arRunErrors))
{
	if (is_array($V))
	{
		foreach ($V as $key => $value)
		{
			if (trim($value)=="0")
			{
				$bAllSections = true;
				break;
			}
			$value = (int)$value;
			if ($value > 0)
			{
				$arSections[] = $value;
			}
		}
	}
	if (!$bAllSections && !empty($arSections) && $CHECK_PERMISSIONS == 'Y')
	{
		$clearedValues = array();
		$filter = array(
			'IBLOCK_ID' => $IBLOCK_ID,
			'ID' => $arSections
		);
		$iterator = CIBlockSection::GetList(
			array(),
			array_merge($filter, $permissionFilter),
			false,
			array('ID')
		);
		while ($row = $iterator->Fetch())
			$clearedValues[] = (int)$row['ID'];
		unset($row, $iterator);
		$arSections = $clearedValues;
	}

	if (!$bAllSections && empty($arSections))
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_NO_SECTION_LIST');
	}
}

$selectedPriceType = 0;
if (!empty($XML_DATA['PRICE']))
{
	$XML_DATA['PRICE'] = (int)$XML_DATA['PRICE'];
	if ($XML_DATA['PRICE'] > 0)
	{
		$rsCatalogGroups = CCatalogGroup::GetGroupsList(array('CATALOG_GROUP_ID' => $XML_DATA['PRICE'],'GROUP_ID' => 2));
		if (!($arCatalogGroup = $rsCatalogGroups->Fetch()))
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
		}
		else
		{
			$selectedPriceType = $XML_DATA['PRICE'];
		}
	}
	else
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
	}
}

$usedProtocol = (isset($USE_HTTPS) && $USE_HTTPS == 'Y' ? 'https://' : 'http://');
$filterAvailable = (isset($FILTER_AVAILABLE) && $FILTER_AVAILABLE == 'Y');
$disableReferers = (isset($DISABLE_REFERERS) && $DISABLE_REFERERS == 'Y');

$vatExportSettings = array(
	'ENABLE' => 'N',
	'BASE_VAT' => ''
);

$vatRates = array(
	'0%' => 'VAT_0',
	'10%' => 'VAT_10',
	'18%' => 'VAT_18'
);
$vatList = array();

if (!empty($XML_DATA['VAT_EXPORT']) && is_array($XML_DATA['VAT_EXPORT']))
	$vatExportSettings = array_merge($vatExportSettings, $XML_DATA['VAT_EXPORT']);
$vatExport = $vatExportSettings['ENABLE'] == 'Y';
if ($vatExport)
{
	if ($vatExportSettings['BASE_VAT'] == '')
	{
		$vatExport = false;
	}
	else
	{
		if ($vatExportSettings['BASE_VAT'] != '-')
			$vatList[0] = 'NO_VAT';

		$filter = array('=RATE' => array_keys($vatRates));
		if (isset($vatRates[$vatExportSettings['BASE_VAT']]))
			$filter['!=RATE'] = $vatExportSettings['BASE_VAT'];
		$iterator = Catalog\VatTable::getList(array(
			'select' => array('ID', 'RATE'),
			'filter' => $filter,
			'order' => array('ID' => 'ASC')
		));
		while ($row = $iterator->fetch())
		{
			$row['ID'] = (int)$row['ID'];
			$row['RATE'] = (float)$row['RATE'];
			$index = $row['RATE'].'%';
			if (isset($vatRates[$index]))
				$vatList[$row['ID']] = $vatRates[$index];
		}
		unset($index, $row, $iterator);
	}
}

$itemOptions = array(
	'PROTOCOL' => $usedProtocol,
	'SITE_NAME' => $site['SERVER_NAME'],
	'SITE_DIR' => $site['DIR'],
	'DESCRIPTION' => $descrField,
	'MAX_DESCRIPTION_LENGTH' => 4000
);

$sectionFileName = '';
$itemFileName = '';
if (strlen($SETUP_FILE_NAME) <= 0)
{
	$arRunErrors[] = GetMessage("CATI_NO_SAVE_FILE");
}
elseif (preg_match(BX_CATALOG_FILENAME_REG,$SETUP_FILE_NAME))
{
	$arRunErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
}
else
{
	$SETUP_FILE_NAME = Rel2Abs("/", $SETUP_FILE_NAME);
}
if (empty($arRunErrors))
{
/*	if ($GLOBALS["APPLICATION"]->GetFileAccessPermission($SETUP_FILE_NAME) < "W")
	{
		$arRunErrors[] = str_replace('#FILE#', $SETUP_FILE_NAME,GetMessage('YANDEX_ERR_FILE_ACCESS_DENIED'));
	} */
	$sectionFileName = $SETUP_FILE_NAME.'_sections';
	$itemFileName = $SETUP_FILE_NAME.'_items';
	$sectionFileName2 = $SETUP_FILE_NAME_STOCK.'_sections';
	$itemFileName2 = $SETUP_FILE_NAME_STOCK.'_items';
}

$itemsFile = null;

$BASE_CURRENCY = Currency\CurrencyManager::getBaseCurrency();
if (!function_exists('yandexPrepareItems'))
{
	function yandexPrepareItems(array &$list, array $parents, array $options,$IBLOCK_ID)
	{
	    $descrField = 'PREVIEW_TEXT';
		$descrTypeField = 'PREVIEW_TEXT_TYPE';
		if (isset($options['DESCRIPTION']))
		{
			$descrField = $options['DESCRIPTION'];
			$descrTypeField = $options['DESCRIPTION'].'_TYPE';
		}

		foreach (array_keys($list) as $index)
		{
			$row = &$list[$index];

			$row['DETAIL_PAGE_URL'] = (string)$row['DETAIL_PAGE_URL'];
			if ($row['DETAIL_PAGE_URL'] !== '')
			{
				$safeRow = array();
				foreach ($row as $field => $value)
				{
					if ($field == 'PREVIEW_TEXT' || $field == 'DETAIL_TEXT')
						continue;
					if (strncmp($field, 'CATALOG_', 8) == 0)
						continue;
					if (is_array($value))
						continue;
					if (preg_match("/[;&<>\"]/", $value))
						$safeRow[$field] = htmlspecialcharsEx($value);
					else
						$safeRow[$field] = $value;
					$safeRow['~'.$field] = $value;
				}
				unset($field, $value);

				if (isset($row['PARENT_ID']) && isset($parents[$row['PARENT_ID']]))
				{
					$safeRow['~DETAIL_PAGE_URL'] = str_replace(
						array('#SERVER_NAME#', '#SITE_DIR#', '#PRODUCT_URL#'),
						array($options['SITE_NAME'], $options['SITE_DIR'], $parents[$row['PARENT_ID']]),
						$safeRow['~DETAIL_PAGE_URL']
					);
				}
				else
				{
					$safeRow['~DETAIL_PAGE_URL'] = str_replace(
						array('#SERVER_NAME#', '#SITE_DIR#'),
						array($options['SITE_NAME'], $options['SITE_DIR']),
						$safeRow['~DETAIL_PAGE_URL']
					);
				}
				$row['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl($safeRow['~DETAIL_PAGE_URL'], $safeRow, false, 'E');
				unset($safeRow);
			}

			if ($row['DETAIL_PAGE_URL'] == '')
				$row['DETAIL_PAGE_URL'] = '/';
			else
				$row['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $row['DETAIL_PAGE_URL']);
				 if (COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_CATEGORY")) {
                $res = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("CODE" => COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_CATEGORY")));
                if ($ob = $res->GetNext())
				    {
				        $row['CATEGORY_CUSTOM'] = $ob['VALUE'];
				    }
				    }
			$row['PICTURE'] = false;


					$row['DETAIL_PICTURE'] = (int)$row['DETAIL_PICTURE'];
					$row['PREVIEW_PICTURE'] = (int)$row['PREVIEW_PICTURE'];
					if ($row['DETAIL_PICTURE'] > 0 || $row['PREVIEW_PICTURE'] > 0)
					{
						$pictureFile = CFile::GetFileArray($row['DETAIL_PICTURE'] > 0 ? $row['DETAIL_PICTURE'] : $row['PREVIEW_PICTURE']);
						if (!empty($pictureFile))
						{
							if (strncmp($pictureFile['SRC'], '/', 1) == 0)
								$picturePath = $options['PROTOCOL'].$options['SITE_NAME'].CHTTP::urnEncode($pictureFile['SRC'], 'utf-8');
							else
								$picturePath = $pictureFile['SRC'];
							$row['PICTURE'] = $picturePath;
							unset($picturePath);
						}
						unset($pictureFile);
					}
				$row['DESCRIPTION'] = '';
			if ($row[$descrField] !== null)
			{
				$row['DESCRIPTION'] =
					TruncateText(
						$row[$descrTypeField] == 'html'
						? strip_tags($row[$descrField],'<p>,<br>,<strong>,<em>,<ul>,<ol>,<li>')
						: preg_replace_callback("'&[^;]*;'", 'yandex_replace_special', $row[$descrField]),
						$options['MAX_DESCRIPTION_LENGTH']
					);
				//$row['DESCRIPTION'] = str_replace("<p>", "", $row['DESCRIPTION']);
				$row['DESCRIPTION'] = str_replace(array("\r\n", "\r", "\n"), '<br>', $row['DESCRIPTION']);
				$row['DESCRIPTION'] = str_replace("  ", " ", $row['DESCRIPTION']);
				$row['DESCRIPTION'] = str_replace("&nbsp;", " ", $row['DESCRIPTION']);
				$row['DESCRIPTION'] = str_replace("<br><br>", "<br>", $row['DESCRIPTION']);
               $row['DESCRIPTION'] = str_replace("<br><br>", "<br/>", $row['DESCRIPTION']);
				//$row['DESCRIPTION'] = str_replace("</p>", "<br>", $row['DESCRIPTION']);

				//$row['DESCRIPTION'] = str_replace("<br>", "<br/>", $row['DESCRIPTION']);
				$row['DESCRIPTION']=preg_replace('~(<(.*)[^<>]*>\s*<\/\\2>)~i','',$row['DESCRIPTION']);

			}
			unset($row);
		}
		unset($index);
	}
}

if ($firstStep)
{
	if (empty($arRunErrors))
	{
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);
       CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME_STOCK);
		if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$sectionFileName, "wb"))
		{
			$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
		}

	}
    if($AVITO_STOCK=='Y')
	{
	$fps = @fopen($_SERVER["DOCUMENT_ROOT"].$sectionFileName2, "wb");
	}	

       $date=str_replace('*','T',date('Y-m-d*H:i:s'));
	if (empty($arRunErrors))
	{
		/** @noinspection PhpUndefinedVariableInspection */
		fwrite($fp, '<?header("Content-Type: text/xml; charset=UTF-8");'."\n");
		fwrite($fp, 'echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">"?>'."\n");
        fwrite($fp, '<Ads formatVersion="3" target="Avito.ru">'."\n");
        if($AVITO_STOCK=='Y')
        {
			fwrite($fps, '<?header("Content-Type: text/xml; charset=UTF-8");'."\n");
			fwrite($fps, 'echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">"?>'."\n");
	        fwrite($fps, '<items date="'.$date.'" formatVersion="1">'."\n");
        }
		fwrite($fp, $strTmp);
		unset($strTmp);

		//*****************************************//


		//*****************************************//
		$intMaxSectionID = 0;

		$strTmpCat = '';
		$strTmpOff = '';

		$arSectionIDs = array();
		$arAvailGroups = array();
		if (!$bAllSections)
		{
			for ($i = 0, $intSectionsCount = count($arSections); $i < $intSectionsCount; $i++)
			{
				$sectionIterator = CIBlockSection::GetNavChain($IBLOCK_ID, $arSections[$i], array('ID', 'IBLOCK_SECTION_ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN'));
				$curLEFT_MARGIN = 0;
				$curRIGHT_MARGIN = 0;
				while ($section = $sectionIterator->Fetch())
				{
					$section['ID'] = (int)$section['ID'];
					$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
					if ($arSections[$i] == $section['ID'])
					{
						$curLEFT_MARGIN = (int)$section['LEFT_MARGIN'];
						$curRIGHT_MARGIN = (int)$section['RIGHT_MARGIN'];
						$arSectionIDs[$section['ID']] = $section['ID'];
					}
					$arAvailGroups[$section['ID']] = array(
						'ID' => $section['ID'],
						'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID'],
						'NAME' => $section['NAME']
					);
					if ($intMaxSectionID < $section['ID'])
						$intMaxSectionID = $section['ID'];
				}
				unset($section, $sectionIterator);

				$filter = array(
					'IBLOCK_ID' => $IBLOCK_ID,
					'>LEFT_MARGIN' => $curLEFT_MARGIN,
					'<RIGHT_MARGIN' => $curRIGHT_MARGIN,
					'GLOBAL_ACTIVE' => 'Y'
				);
				$sectionIterator = CIBlockSection::GetList(
					array('LEFT_MARGIN' => 'ASC'),
					array_merge($filter, $permissionFilter),
					false,
					array('ID', 'IBLOCK_SECTION_ID', 'NAME')
				);
				while ($section = $sectionIterator->Fetch())
				{
					$section['ID'] = (int)$section['ID'];
					$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
					$arAvailGroups[$section['ID']] = $section;
					if ($intMaxSectionID < $section['ID'])
						$intMaxSectionID = $section['ID'];
				}
				unset($section, $sectionIterator);
			}
		}
		else
		{
			$filter = array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'GLOBAL_ACTIVE' => 'Y'
			);
			$sectionIterator = CIBlockSection::GetList(
				array('LEFT_MARGIN' => 'ASC'),
				array_merge($filter, $permissionFilter),
				false,
				array('ID', 'IBLOCK_SECTION_ID', 'NAME')
			);
			while ($section = $sectionIterator->Fetch())
			{
				$section['ID'] = (int)$section['ID'];
				$section['IBLOCK_SECTION_ID'] = (int)$section['IBLOCK_SECTION_ID'];
				$arAvailGroups[$section['ID']] = $section;
				$arSectionIDs[$section['ID']] = $section['ID'];
				if ($intMaxSectionID < $section['ID'])
					$intMaxSectionID = $section['ID'];
			}
			unset($section, $sectionIterator);
		}
        $sectArr=array();
		foreach ($arAvailGroups as $value)
			$sectArr[$value['ID']]=$value['NAME'];
		unset($value);


		fwrite($fp, $strTmpCat);
		fclose($fp);
		if($AVITO_STOCK=='Y')
		fclose($fps);
		unset($strTmpCat);

		$boolNeedRootSection = false;


		$itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName, 'wb');
		$itemsFileStock = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName2, 'wb');
		if (!$itemsFile)
		{
			$arRunErrors[] = str_replace('#FILE#', $itemFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
		}
	}
}
else
{
	$itemsFile = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName, 'ab');
	$itemsFileStock = @fopen($_SERVER["DOCUMENT_ROOT"].$itemFileName2, 'ab');
	if (!$itemsFile)
	{
		$arRunErrors[] = str_replace('#FILE#', $itemFileName, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
	}
}

if (empty($arRunErrors))
{
	//*****************************************//
	Catalog\Product\Price\Calculation::setConfig(array(
		'CURRENCY' => $BASE_CURRENCY,
		'USE_DISCOUNTS' => true,
		'RESULT_WITH_VAT' => true,
		'RESULT_MODE' => Catalog\Product\Price\Calculation::RESULT_MODE_COMPONENT
	));

	if ($selectedPriceType > 0)
	{
		$priceTypeList = array($selectedPriceType);
	}
	else
	{
		$priceTypeList = array();
		$priceIterator = Catalog\GroupAccessTable::getList(array(
			'select' => array('CATALOG_GROUP_ID'),
			'filter' => array('@GROUP_ID' => 2),
			'order' => array('CATALOG_GROUP_ID' => 'ASC')
		));
		while ($priceType = $priceIterator->fetch())
		{
			$priceTypeId = (int)$priceType['CATALOG_GROUP_ID'];
			$priceTypeList[$priceTypeId] = $priceTypeId;
			unset($priceTypeId);
		}
		unset($priceType, $priceIterator);
	}

	$needDiscountCache = \CIBlockPriceTools::SetCatalogDiscountCache($priceTypeList, array(2), $site['LID']);

	$itemFields = array(
		'ID', 'XML_ID','IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME',
		'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE','DETAIL_PICTURE', 'PREVIEW_PICTURE',
		'CATALOG_AVAILABLE', 'CATALOG_TYPE','QUANTITY'
	);
	$offerFields = array(
		'ID', 'XML_ID','IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME',
		'DETAIL_TEXT', 'DETAIL_TEXT_TYPE','PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'DETAIL_PICTURE','PREVIEW_PICTURE','QUANTITY'
	);

	$allowedTypes = array();
	switch ($arCatalog['CATALOG_TYPE'])
	{
		case CCatalogSku::TYPE_CATALOG:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_PRODUCT => true,
				Catalog\ProductTable::TYPE_SET => true
			);
			break;
		case CCatalogSku::TYPE_OFFERS:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_OFFER => true
			);
			break;
		case CCatalogSku::TYPE_FULL:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_PRODUCT => true,
				Catalog\ProductTable::TYPE_SET => true,
				Catalog\ProductTable::TYPE_SKU => true
			);
			break;
		case CCatalogSku::TYPE_PRODUCT:
			$allowedTypes = array(
				Catalog\ProductTable::TYPE_SKU => true
			);
			break;
	}

   $filter =array();
	$filter['IBLOCK_ID'] = $IBLOCK_ID;
	if (!$bAllSections && !empty($arSectionIDs))
	{
		$filter['INCLUDE_SUBSECTIONS'] = 'Y';
		$filter['SECTION_ID'] = $arSectionIDs;
	}

	if (isset($FILTER_DATA) and isset($FILTER_PREF))
	{
     foreach($FILTER_DATA as $key=>$val)
      {
         $res0 = CIBlockProperty::GetByID($val, $IBLOCK_ID);
         $pref='';
			if($ar_res = $res0->GetNext())
			{
			  $typeProp=$ar_res['PROPERTY_TYPE'];
			  if($typeProp=='L')
			  $pref="_VALUE";
			  $codeProp=$ar_res['CODE'];
	         }
      	if(isset($FILTER_PREF[$key]) and $val!='')
      	{
      	  if($FILTER_PREF[$key]=='z')
      	      $filter["PROPERTY_".$codeProp] = false;
      	  elseif($FILTER_PREF[$key]=='nz')
              $filter["!PROPERTY_".$codeProp] = false;
      	  elseif($FILTER_PREF[$key]=='=')
	      	  $filter["PROPERTY_".$codeProp.$pref] = $FILTER_INPUT[$key];
	      elseif($FILTER_PREF[$key]=='>')
	      	  $filter[">PROPERTY_".$codeProp.$pref] = $FILTER_INPUT[$key];
      	  elseif($FILTER_PREF[$key]=='<')
	      	  $filter["<PROPERTY_".$codeProp.$pref] = $FILTER_INPUT[$key];
      	  elseif($FILTER_PREF[$key]=='=!')
	      	  $filter["!PROPERTY_".$codeProp.$pref] = $FILTER_INPUT[$key];
	      elseif($FILTER_PREF[$key]==1)
	      {
	      $FILTER_INPUT[$key]=str_replace(' ,',',',$FILTER_INPUT[$key]);
	      $FILTER_INPUT[$key]=str_replace(', ',',',$FILTER_INPUT[$key]);
	       $filter["PROPERTY_".$val.$pref] = explode(',',$FILTER_INPUT[$key]);
	      }
      	}
      }
	}


	$filter['ACTIVE'] = 'Y';
	$filter['ACTIVE_DATE'] = 'Y';


	if ($AVITO_PRICE)
    {   if($XML_DATA['PRICE']>0)
			$filter['>PRICE_'.$XML_DATA['PRICE']]  = $AVITO_PRICE;
		else
			$filter['>PRICE_1']  = $AVITO_PRICE;
	}
	 if ($AVITO_PRICE_MAX)
    {
	    if($XML_DATA['PRICE']>0)
			$filter['<PRICE_'.$XML_DATA['PRICE']]  = $AVITO_PRICE_MAX;
		else
			$filter['<PRICE_1']  = $AVITO_PRICE_MAX;
    }
	if(!empty($AVITO_STORE[0]) and !$boolOffers)
	 {
			$filter['STORE_NUMBER']=$AVITO_STORE;
			$filter['>STORE_AMOUNT'] = 0;
	 }
	if($AVITO_FILTER)
	$filter["!PROPERTY_".$AVITO_FILTER] = false;

	if ($filterAvailable)
		$filter['CATALOG_AVAILABLE'] = 'Y';
	$filter = array_merge($filter, $permissionFilter);


	$offersFilter['ACTIVE'] = 'Y';
	$offersFilter['ACTIVE_DATE'] = 'Y';

	 if(!empty($AVITO_STORE[0]))
	 {
			$offersFilter['STORE_NUMBER']=$AVITO_STORE;
			$offersFilter['>STORE_AMOUNT'] = 0;
	 }

	if ($AVITO_PRICE)
    {
	    if($XML_DATA['PRICE']>0)
			$offersFilter['>PRICE_'.$XML_DATA['PRICE']]  = $AVITO_PRICE;
		else
			$offersFilter['>PRICE_1']  = $AVITO_PRICE;
	}
    if ($AVITO_PRICE_MAX)
    {
	    if($XML_DATA['PRICE']>0)
			$offersFilter['<PRICE_'.$XML_DATA['PRICE']]  = $AVITO_PRICE_MAX;
		else
			$offersFilter['<PRICE_1']  = $AVITO_PRICE_MAX;
    }
	if ($filterAvailable)
		$offersFilter['CATALOG_AVAILABLE'] = 'Y';
	$offersFilter = array_merge($offersFilter, $permissionFilter);

	if (isset($allowedTypes[Catalog\ProductTable::TYPE_SKU]))
	{
		if ($arSKUExport['SKU_EXPORT_COND'] == YANDEX_SKU_EXPORT_PROP)
		{
			$strExportKey = '';
			$mxValues = false;
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'NONZERO' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$strExportKey = '!';
			$strExportKey .= 'PROPERTY_'.$arSKUExport['SKU_PROP_COND']['PROP_ID'];
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$mxValues = $arSKUExport['SKU_PROP_COND']['VALUES'];
			$offersFilter[$strExportKey] = $mxValues;
		}
	}

	do
	{
		if (isset($CUR_ELEMENT_ID) && $CUR_ELEMENT_ID > 0)
			$filter['>ID'] = $CUR_ELEMENT_ID;

		$existItems = false;

		$itemIdsList = array();
		$items = array();

		$skuIdsList = array();
		$simpleIdsList = array();

		$iterator = CIBlockElement::GetList(
			array('ID' => 'ASC'),
			$filter,
			false,
			$navParams,
			$itemFields
		);
		while ($row = $iterator->Fetch())
		{
			$finalExport = false; // items exist
			$existItems = true;

			$id = (int)$row['ID'];
			$CUR_ELEMENT_ID = $id;

			$row['CATALOG_TYPE'] = (int)$row['CATALOG_TYPE'];
			$elementType = $row['CATALOG_TYPE'];
			if (!isset($allowedTypes[$elementType]))
				continue;

			$row['SECTIONS'] = array();
			if ($needProperties || $needDiscountCache)
				$row['PROPERTIES'] = array();
			$row['PRICES'] = array();

			$items[$id] = $row;
			$itemIdsList[$id] = $id;

			if ($elementType == Catalog\ProductTable::TYPE_SKU)
				$skuIdsList[$id] = $id;
			else
				$simpleIdsList[$id] = $id;
		}
		unset($row, $iterator);

		if (!empty($items))
		{
			yandexPrepareItems($items, array(), $itemOptions,$IBLOCK_ID);

			foreach (array_chunk($itemIdsList, 500) as $pageIds)
			{
				$iterator = Iblock\SectionElementTable::getList(array(
					'select' => array('IBLOCK_ELEMENT_ID', 'IBLOCK_SECTION_ID'),
					'filter' => array('@IBLOCK_ELEMENT_ID' => $pageIds, '==ADDITIONAL_PROPERTY_ID' => null),
					'order' => array('IBLOCK_ELEMENT_ID' => 'ASC')
				));
				while ($row = $iterator->fetch())
				{
					$id = (int)$row['IBLOCK_ELEMENT_ID'];
					$sectionId = (int)$row['IBLOCK_SECTION_ID'];
					$items[$id]['SECTIONS'][$sectionId] = $sectionId;
					unset($sectionId, $id);
				}
				unset($row, $iterator);
			}
			unset($pageIds);

			if ($needProperties || $needDiscountCache)
			{
				if (!empty($propertyIdList))
				{
					\CIBlockElement::GetPropertyValuesArray(
						$items,
						$IBLOCK_ID,
						array(
							'ID' => $itemIdsList,
							'IBLOCK_ID' => $IBLOCK_ID
						),
						array('ID' => $propertyIdList),
						array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields)
					);
				}

				if ($needDiscountCache)
				{
					foreach ($itemIdsList as $id)
						\CCatalogDiscount::SetProductPropertiesCache($id, $items[$id]['PROPERTIES']);
					unset($id);
				}

				if (!$needProperties)
				{
					foreach ($itemIdsList as $id)
						$items[$id]['PROPERTIES'] = array();
					unset($id);
				}
				else
				{
					foreach ($itemIdsList as $id)
					{
						if (empty($items[$id]['PROPERTIES']))
							continue;
						foreach (array_keys($items[$id]['PROPERTIES']) as $index)
						{
							$propertyId = $items[$id]['PROPERTIES'][$index]['ID'];
							if (!isset($yandexNeedPropertyIds[$propertyId]))
								unset($items[$id]['PROPERTIES'][$index]);
						}
						unset($propertyId, $index);
					}
					unset($id);
				}
			}

			if ($needDiscountCache)
			{
				\CCatalogDiscount::SetProductSectionsCache($itemIdsList);
				\CCatalogDiscount::SetDiscountProductCache($itemIdsList, array('IBLOCK_ID' => $IBLOCK_ID, 'GET_BY_ID' => 'Y'));
			}

			if (!empty($skuIdsList))
			{
				$offerPropertyFilter = array();
				if ($needProperties || $needDiscountCache)
				{
					if (!empty($propertyIdList))
						$offerPropertyFilter = array('ID' => $propertyIdList);
				}

				$offers = \CCatalogSku::getOffersList(
					$skuIdsList,
					$IBLOCK_ID,
					$offersFilter,
					$offerFields,
					$offerPropertyFilter,
					array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields)
				);
				unset($offerPropertyFilter);

				if (!empty($offers))
				{
					$offerLinks = array();
					$offerIdsList = array();
					$parentsUrl = array();
					foreach (array_keys($offers) as $productId)
					{
						unset($skuIdsList[$productId]);
						$items[$productId]['OFFERS'] = array();
						$parentsUrl[$productId] = $items[$productId]['DETAIL_PAGE_URL'];
						foreach (array_keys($offers[$productId]) as $offerId)
						{
							$productOffer = $offers[$productId][$offerId];

							$productOffer['PRICES'] = array();
							if ($needDiscountCache)
								\CCatalogDiscount::SetProductPropertiesCache($offerId, $productOffer['PROPERTIES']);
							if (!$needProperties)
							{
								$productOffer['PROPERTIES'] = array();
							}
							else
							{
								if (!empty($productOffer['PROPERTIES']))
								{
									foreach (array_keys($productOffer['PROPERTIES']) as $index)
									{
										$propertyId = $productOffer['PROPERTIES'][$index]['ID'];
										if (!isset($yandexNeedPropertyIds[$propertyId]))
											unset($productOffer['PROPERTIES'][$index]);
									}
									unset($propertyId, $index);
								}
							}
							$items[$productId]['OFFERS'][$offerId] = $productOffer;
							unset($productOffer);

							$offerLinks[$offerId] = &$items[$productId]['OFFERS'][$offerId];
							$offerIdsList[$offerId] = $offerId;
						}
						unset($offerId);
					}
					if (!empty($offerIdsList))
					{
						yandexPrepareItems($offerLinks, $parentsUrl, $itemOptions,$IBLOCK_ID);

						foreach (array_chunk($offerIdsList, 500) as $pageIds)
						{
							if ($needDiscountCache)
							{
								\CCatalogDiscount::SetProductSectionsCache($pageIds);
								\CCatalogDiscount::SetDiscountProductCache(
									$pageIds,
									array('IBLOCK_ID' => $arCatalog['IBLOCK_ID'], 'GET_BY_ID' => 'Y')
								);
							}

							if (!$filterAvailable)
							{
								$iterator = Catalog\ProductTable::getList(array(
									'select' => ($vatExport ? array('ID', 'AVAILABLE', 'VAT_ID', 'VAT_INCLUDED') : array('ID', 'AVAILABLE')),
									'filter' => array('@ID' => $pageIds)
								));
								while ($row = $iterator->fetch())
								{
									$id = (int)$row['ID'];
									$offerLinks[$id]['CATALOG_AVAILABLE'] = $row['AVAILABLE'];
									if ($vatExport)
									{
										$row['VAT_ID'] = (int)$row['VAT_ID'];
										$offerLinks[$id]['CATALOG_VAT_ID'] = ($row['VAT_ID'] > 0 ? $row['VAT_ID'] : $offersCatalog['VAT_ID']);
										$offerLinks[$id]['CATALOG_VAT_INCLUDED'] = $row['VAT_INCLUDED'];
									}
								}
								unset($id, $row, $iterator);
							}

							$priceFilter = array(
								'@PRODUCT_ID' => $pageIds,
								'+<=QUANTITY_FROM' => 1,
								'+>=QUANTITY_TO' => 1,
							);
							if ($selectedPriceType > 0)
								$priceFilter['CATALOG_GROUP_ID'] = $selectedPriceType;
							else
								$priceFilter['@CATALOG_GROUP_ID'] = $priceTypeList;

							$priceIterator = \CPrice::GetListEx(
								array(),
								$priceFilter,
								false,
								false,
								array('ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY')
							);
							while ($price = $priceIterator->Fetch())
							{
								$id = (int)$price['PRODUCT_ID'];
								$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
								$offerLinks[$id]['PRICES'][$priceTypeId] = $price;
								unset($priceTypeId, $id);
							}
							unset($price, $priceIterator);
						}
						unset($pageIds);
					}

					unset($parentsUrl, $offerIdsList, $offerLinks);
				}
				unset($offers);

				if (!empty($skuIdsList))
				{
					foreach ($skuIdsList as $id)
					{
						unset($items[$id]);
						unset($itemIdsList[$id]);
					}
					unset($id);
				}
			}

			if (!empty($simpleIdsList))
			{
				foreach (array_chunk($simpleIdsList, 500) as $pageIds)
				{
					$priceFilter = array(
						'@PRODUCT_ID' => $pageIds,
						'+<=QUANTITY_FROM' => 1,
						'+>=QUANTITY_TO' => 1,
					);
					if ($selectedPriceType > 0)
						$priceFilter['CATALOG_GROUP_ID'] = $selectedPriceType;
					else
						$priceFilter['@CATALOG_GROUP_ID'] = $priceTypeList;

					$priceIterator = \CPrice::GetListEx(
						array(),
						$priceFilter,
						false,
						false,
						array('ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY')
					);
					while ($price = $priceIterator->Fetch())
					{
						$id = (int)$price['PRODUCT_ID'];
						$priceTypeId = (int)$price['CATALOG_GROUP_ID'];
						$items[$id]['PRICES'][$priceTypeId] = $price;
						unset($priceTypeId, $id);
					}
					unset($price, $priceIterator);
				}
				unset($pageIds);
			}
		}
        $stockContent = '';
		$itemsContent = '';
		if (!empty($items))
		{
			foreach ($itemIdsList as $id)
			{
				$CUR_ELEMENT_ID = $id;

				$row = $items[$id];

				if (!empty($row['SECTIONS']))
				{
					foreach ($row['SECTIONS'] as $sectionId)
					{
						if (!isset($arAvailGroups[$sectionId]))
							continue;
						$row['CATEGORY_ID'] = $sectionId;
					}
					unset($sectionId);
				}
				else
				{
					$boolNeedRootSection = true;
					$row['CATEGORY_ID'] = $intMaxSectionID;
				}
				if (!isset($row['CATEGORY_ID']))
					continue;

				if ($row['CATALOG_TYPE'] == Catalog\ProductTable::TYPE_SKU && !empty($row['OFFERS']))
				{
					$minOfferId = null;
					$minOfferPrice = null;

					foreach (array_keys($row['OFFERS']) as $offerId)
					{
						if (empty($row['OFFERS'][$offerId]['PRICES']))
						{
							unset($row['OFFERS'][$offerId]);
							continue;
						}

						$fullPrice = 0;
						$minPrice = 0;
						$minPriceCurrency = '';

						$calculatePrice = CCatalogProduct::GetOptimalPrice(
							$row['OFFERS'][$offerId]['ID'],
							1,
							array(2),
							'N',
							$row['OFFERS'][$offerId]['PRICES'],
							$site['LID'],
							array()
						);

						if (!empty($calculatePrice))
						{
							$minPrice = $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'];
							$fullPrice = $calculatePrice['RESULT_PRICE']['BASE_PRICE'];
							$minPriceCurrency = $calculatePrice['RESULT_PRICE']['CURRENCY'];
						}
						unset($calculatePrice);

						if ($minPrice <= 0)
						{
							unset($row['OFFERS'][$offerId]);
							continue;
						}

						$row['OFFERS'][$offerId]['RESULT_PRICE'] = array(
							'MIN_PRICE' => $minPrice,
							'FULL_PRICE' => $fullPrice,
							'CURRENCY' => $minPriceCurrency
						);
						if ($minOfferPrice === null || $minOfferPrice > $minPrice)
						{
							$minOfferId = $offerId;
							$minOfferPrice = $minPrice;
						}
					}
					unset($offerId);

					if ($arSKUExport['SKU_EXPORT_COND'] == YANDEX_SKU_EXPORT_MIN_PRICE)
					{
						if ($minOfferId === null)
							$row['OFFERS'] = array();
						else
							$row['OFFERS'] = array($minOfferId => $row['OFFERS'][$minOfferId]);
					}
					if (empty($row['OFFERS']))
						continue;
                   if (COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO") or $ABRICOS_AVITO_PHOTO)
				   {
	                 	    if(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO"))
		                 	    {
		                 	    	$photo=COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO");
			                         $res = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("CODE" => $photo));
		                         }
	                 	    if($ABRICOS_AVITO_PHOTO)
		                 	    {
                                     $res = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("ID" => $ABRICOS_AVITO_PHOTO));
		               	    	}

							$arrPhoto=array();
							while ($ob = $res->Fetch())
							    {   if(!empty($ob['VALUE']))
								    {
										$PHOTO_VALUE = $ob['VALUE'];
										$file = CFile::GetFileArray($PHOTO_VALUE);
										$PHOTO_SRC = $file['SRC'];
										if (strncmp($PHOTO_SRC, '/', 1) == 0)
											$picturePath = $itemOptions['PROTOCOL'].$itemOptions['SITE_NAME'].CHTTP::urnEncode($PHOTO_SRC, 'utf-8');
										else
											$picturePath = $PHOTO_SRC;
										$arrPhoto[]=$picturePath;
		                            }
							    }
								$row['PICTURES'] = $arrPhoto;
	                           unset($picturePath);
	                           unset($ob);

					}
					foreach ($row['OFFERS'] as $offer)
					{
                       if(empty($offer['PICTURE']) and $AVITO_NOPHOTO=='Y')
	                     continue;
					if(!empty($AVITO_STORE[0]))
					{
					  $storeAmountScu=0;
					  foreach($AVITO_STORE as $storeId)
					   	{
	                    $rsStoreScu = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' =>$offer['ID'], 'STORE_ID' => $storeId), false, false, array('AMOUNT'));
						if ($arStoreScu = $rsStoreScu->Fetch())
						{
							if ($arStoreScu['AMOUNT']> 0)
							  $storeAmountScu=$storeAmountScu+$arStoreScu['AMOUNT'];
						}
						}
					}
                       $dataAddress = explode(PHP_EOL, $AVITO_ADRESS);
                       $z=0;
						foreach ($dataAddress as $AdressArr)
						{
							if(strlen($AdressArr)<2)
						      continue;
							$itemsContent .= '<Ad>'."\n";
                            $stockContent.='<item>'."\n";
							if ($offer['XML_ID']>0)
							{
								$itemsContent .= "<Id>".$offer['XML_ID'].($z>0 ? '-'.$z : '')."</Id>\n";
								$stockContent.= "<id>".$offer['XML_ID'].($z>0 ? '-'.$z : '')."</id>\n";
							
							}
                            else
                            {
	                            $itemsContent .= "<Id>".$offer['ID'].($z>0 ? '-'.$z : '')."</Id>\n";
	                            $stockContent.= "<id>".$offer['ID'].($z>0 ? '-'.$z : '')."</id>\n";
	                          
	                        }
							if($AVITO_STOCK_CONST)
								$stockContent.='<stock>'.$AVITO_STOCK_CONST.'</stock>'."\n";
							elseif(!empty($AVITO_STORE[0]))
							      $stockContent.='<stock>'.$storeAmountScu.'</stock>'."\n";
							else
	                            $stockContent.='<stock>'.$offer['QUANTITY'].'</stock>'."\n";
		                    if(!empty($PROP_NAME) and $AVITO_CATEGORY_CUSTOM!='Y' and !$AVITO_CATEGORY)
	                          {
	                            $i=0;
	                          	foreach($PROP_NAME as $arrCat)
	                          	{
		                          	if($arrCat!='')
		                          	{
			                          	$itemsContent .= "<".CAbricosAvitoautoload::category_name($arrCat).">";
			                          	if($PROP_DATA[$i] and $content=CAbricosAvitoautoload::category_name(CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$PROP_DATA[$i])))
			                                $itemsContent .= $content;
			                            elseif($PROP_DEF[$i])
			                                $itemsContent .= yandex_text2xml($PROP_DEF[$i]);
			                          	$itemsContent .= "</".CAbricosAvitoautoload::category_name($arrCat).">\n";
		                          	}
		                          	$i++;
	                          	}
	                          }
                              else
                              {
	                            if($AVITO_CATEGORY_CUSTOM=='Y')
	                            {
									$ar_result=CIBlockSection::GetList(Array("SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID,"ID"=>$row['CATEGORY_ID']),false, Array("UF_*"));
		                                $res=$ar_result->GetNext();
										if($res) 
										{
										    foreach($res as $key => $value)
										    {

												if(strpos($key,'UF_AVITO_') and $value)
												{

												    $keyName=str_replace('~UF_AVITO_','',$key);
												    if($keyName=="CONDITION" and $AVITO_CONDITION_ALT)
												      continue;
												    else
													  $itemsContent .= "<".$keyName.">".yandex_text2xml($value)."</".$keyName.">\n";
												}
											}
		                            }
		                            	if($AVITO_CONDITION_ALT)
											$itemsContent .= "<Condition>".yandex_text2xml($AVITO_CONDITION_ALT)."</Condition>\n";
	                            }
	                            else
	                            {
		                            if($AVITO_CONDITION_ALT)
		                            {
		                              $itemsContent .= "<Condition>".yandex_text2xml($AVITO_CONDITION_ALT)."</Condition>\n";
		                            }
		                            else
		                            {
		                             if($AVITO_CONDITION=='new')
		                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_NEW"))."</Condition>\n";
		                             elseif($AVITO_CONDITION=='new2')
		                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_NEW2"))."</Condition>\n";
		                             elseif($AVITO_CONDITION=='bu')
		                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_BU"))."</Condition>\n";
                                    }
		                            if($AVITO_CATEGORY==GetMessage("AVITO_ZAP"))
			                            $AVITO_CATEGORY=GetMessage("AVITO_ZAPCAT");
		                            $itemsContent .= "<Category>".yandex_text2xml($AVITO_CATEGORY)."</Category>\n";

		                            if(($AVITO_CATEGORY==GetMessage("AVITO_VODA")) or ($AVITO_CATEGORY==GetMessage("AVITO_VODA2")))
			                            $itemsContent .= "<VehicleType>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</VehicleType>\n";
		                            elseif ($AVITO_CATEGORY==GetMessage("AVITO_ZAPCAT"))
		                               	$itemsContent .= "<TypeId>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</TypeId>\n";
		                            else
		                               	$itemsContent .= "<GoodsType>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</GoodsType>\n";

		                            if($AVITO_APPAREL) {
			                           	$itemsContent .= "<Apparel>".yandex_text2xml($AVITO_APPAREL)."</Apparel>\n";
		                           	}
	                              }
	                          }
	                              if($AVITO_PHONE)
	                               $itemsContent .= "<ContactPhone>".yandex_text2xml($AVITO_PHONE)."</ContactPhone>\n";
	                               $itemsContent .= "<Address>".yandex_text2xml($AdressArr)."</Address>\n";
	                               $itemsContent .= "<ListingFee>".yandex_text2xml($AVITO_LISTINGFEE)."</ListingFee>\n";
	                               $itemsContent .= "<AdType>".yandex_text2xml($AVITO_ADTYPE)."</AdType>\n";
                                  if($DISPLAYAREAS)
									{
									 $dareas = explode(PHP_EOL, $DISPLAYAREAS);
									 $itemsContent .= "<DisplayAreas>";
									 foreach ($dareas as $AreasArr)
										{
											if($AreasArr)
											{
												$itemsContent .= "<Area>";
												$itemsContent .= yandex_text2xml($AreasArr);
												$itemsContent .= "</Area>";
											}
										}
										$itemsContent .= "</DisplayAreas>";
									}

							$y = 0;
							//$avitoSizeValue='';
							foreach ($arYandexFields as $key)
							{
								switch ($key)
								{
									case 'name':
										if(!empty($AVITO_TITLE_TEMPL))
										{
											$itemsContent .= "<Title>".yandex_text2xml(CAbricosAvitoautoload::get_text($AVITO_TITLE_TEMPL,$row['NAME'],$sectArr[$row['CATEGORY_ID']],$offer['NAME']), true)."</Title>\n";
										}
                                        elseif(!empty($AVITO_TITLE_PROP))
										{ $title=false;
								          $title=CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$AVITO_TITLE_PROP);
									     if($title)
			                                 $itemsContent .= "<Title>".yandex_text2xml($title, true)."</Title>\n";
		                                  else
											$itemsContent .= "<Title>".yandex_text2xml($offer['NAME'], true)."</Title>\n";
		                                 }
										else
										$itemsContent .= "<Title>".yandex_text2xml($offer['NAME'], true)."</Title>\n";

									break;
									case 'param':
										break;
	                                case 'description':
	                                  if ($parametricFieldsExist)
										{   $itemsParam ='';
											foreach ($parametricFields as $paramKey => $prop_id)
											{
											if (COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO")) {
												$photo=COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO");
											 }
											if ($AVITO_SIZE and $prop_id==$AVITO_SIZE)
												   {
												   	 $valueS = yandex_get_value(
													$offer,
													0,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$usedProtocol

												);
												if ($valueS == '')
												{
													$valueS = yandex_get_value(
														$row,
														0,
														$prop_id,
														$arProperties,
														$arUserTypeFormat,
														$usedProtocol
													);
												}

												   $avitoSizeValue=$valueS;
	                                                unset($valueS);
												   }
												 if ($AVITO_VIDEO and $prop_id==$AVITO_VIDEO)
												   {
												   	 $valueS = yandex_get_value(
													$offer,
													0,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$usedProtocol

												);
												if ($valueS == '')
												{
													$valueS = yandex_get_value(
														$row,
														0,
														$prop_id,
														$arProperties,
														$arUserTypeFormat,
														$usedProtocol
													);
												}

												   $avitoVideo=$valueS;
	                                                unset($valueS);
												   }
												$value = yandex_get_value(
													$offer,
													'PARAM_'.$paramKey,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$usedProtocol
												);
												if ($value == '')
												{
													$value = yandex_get_value(
														$row,
														'PARAM_'.$paramKey,
														$prop_id,
														$arProperties,
														$arUserTypeFormat,
														$usedProtocol
													);
												}
												if ($value != '')
													$itemsParam .= $value.'<br/> ';
												unset($value);

											}
											unset($paramKey, $prop_id);
										}
										if(!empty($AVITO_DESCR_TEMPL))
										{

											$itemsContent .= "<Description><![CDATA[";
											$itemsContent .=yandex_text2xml(CAbricosAvitoautoload::get_text($AVITO_DESCR_TEMPL,$row['NAME'],$sectArr[$row['CATEGORY_ID']],$offer['NAME']))."<br>";
											if($AVITO_DESCR_TEMPL_PL=="Y")
												$itemsContent .=($offer['DESCRIPTION'] !== '' ? yandex_text2xml($offer['DESCRIPTION']) : yandex_text2xml($row['DESCRIPTION'])."\r\n");
										}
                                  		elseif(!empty($AVITO_DESCR_PROP))
										{ $descr=false;
								          $descr=CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$AVITO_DESCR_PROP);
												     if($descr)
						                                 $itemsContent .= "<Description><![CDATA[".yandex_text2xml($descr)." \n ";
					                                  else
														$itemsContent .= "<Description><![CDATA[".
											($offer['DESCRIPTION'] !== '' ? yandex_text2xml($offer['DESCRIPTION']) : yandex_text2xml($row['DESCRIPTION'])."\r\n");
		                                 }
									else
										$itemsContent .= "<Description><![CDATA[".
											($offer['DESCRIPTION'] !== '' ? yandex_text2xml($offer['DESCRIPTION']) : yandex_text2xml($row['DESCRIPTION']))."\r\n";
									if(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_TEXT")) $itemsContent .=yandex_text2xml(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_TEXT"));
									$itemsContent .=strip_tags($itemsParam,'<p>,<br>,<strong>,<em>,<ul>,<ol>,<li>');
											$itemsContent .="]]></Description>\n";
										break;
									}
							}
	                    	if($avitoSizeValue) {
	                                $itemsContent .= "<Size>".yandex_text2xml($avitoSizeValue)."</Size>\n";
	                                }
	                                elseif($AVITO_SIZE){
	                                	$itemsContent .= "<Size>".yandex_text2xml($AVITO_SIZE)."</Size>\n";
	                                }
	                                unset($avitoSizeValue);
	                           if (COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO"))
								   {
					                 	    $photo=COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO");
											$res = CIBlockElement::GetProperty($intOfferIBlockID, $offer['ID'], "sort", "asc", array("CODE" => $photo));

											$arrPhoto2=array();
											while ($ob = $res->GetNext())
											    {   if(!empty($ob['VALUE']))
												    {
														$PHOTO_VALUE = $ob['VALUE'];
														$file = CFile::GetFileArray($PHOTO_VALUE);
														$PHOTO_SRC = $file['SRC'];
														if (strncmp($PHOTO_SRC, '/', 1) == 0)
														  $picturePath = $itemOptions['PROTOCOL'].$itemOptions['SITE_NAME'].CHTTP::urnEncode($PHOTO_SRC, 'utf-8');
														else
															$picturePath = $PHOTO_SRC;
														$arrPhoto2[]=$picturePath;
						                            }
											    }
												$offer['PICTURES'] = $arrPhoto2;
												 unset($picturePath);

									}
	                        $minPrice = $offer['RESULT_PRICE']['MIN_PRICE'];
							$fullPrice = $offer['RESULT_PRICE']['FULL_PRICE'];
							$itemsContent .= "<Price>".round($minPrice)."</Price>\n";

							if(!empty($offer['PICTURE']) or !empty($row['PICTURE']) or !empty($row['PICTURES']) or !empty($offer['PICTURES']))
							{
	                             $itemsContent .= " <Images>\n";
                                 $i=0;
		                         if((!empty($offer['PICTURE']) or !empty($row['PICTURE'])))
		                         {
			                         if($AVITO_NODETAIL_PHOTO and $AVITO_NODETAIL_PHOTO=='N')
			                         {
										$picture = (!empty($offer['PICTURE']) ? $offer['PICTURE'] : $row['PICTURE']);
										if (!empty($picture))
										$itemsContent .= "<Image  url='".$picture."'/>\n";
										unset($picture);
										$i=1;
									}
								}

								if(is_countable($row['PICTURES']) && count($row['PICTURES'])>0) {
									foreach ($row['PICTURES'] as $pict)
									{   if($i<10)
										{
											$itemsContent .="<Image  url='".$pict."'/>\n";
											$i++;
										}
										else break;
									}
								}
								if(is_countable($offer['PICTURES']) && count($offer['PICTURES'])>0) {
									foreach ($offer['PICTURES'] as $pict)
									{ if($i<10)
										{
											$itemsContent .="<Image  url='".$pict."'/>\n";
											$i++;
										}
										else break;
									}
								}
								$itemsContent .="</Images>\n";
							}
							if(!$avitoVideo and $AVITO_VIDEO)
							{
								$db_props = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], array("sort" => "asc"), Array("ID"=>$AVITO_VIDEO));
								if($ar_props = $db_props->Fetch())
								    $avitoVideo = $ar_props["VALUE"];
							}
							if($avitoVideo)
							$itemsContent .= "<VideoURL>".$avitoVideo."</VideoURL>\n";
							$itemsContent .= '</Ad>'."\n";
							$stockContent.= '</item>'."\n";
							$z++;
						}
					}
					unset($avitoVideo);
					unset($AreasArr);
					unset($dareas);
					unset($offer);
				}
				elseif (isset($simpleIdsList[$id]) && !empty($row['PRICES']))
				{
					$row['CATALOG_VAT_ID'] = (int)$row['CATALOG_VAT_ID'];
					if ($row['CATALOG_VAT_ID'] == 0)
						$row['CATALOG_VAT_ID'] = $arCatalog['VAT_ID'];

					$fullPrice = 0;
					$minPrice = 0;
					$minPriceCurrency = '';
					if(!empty($AVITO_STORE[0]))
					{
					  $storeAmount=0;
					  foreach($AVITO_STORE as $storeId)
					   	{
	                    $rsStore = CCatalogStoreProduct::GetList(array(), array('PRODUCT_ID' =>$row['ID'], 'STORE_ID' => $storeId), false, false, array('AMOUNT'));
						if ($arStore = $rsStore->Fetch())
						{
							if ($arStore['AMOUNT']> 0)
							  $storeAmount=$storeAmount+$arStore['AMOUNT'];
						}
						}
						if($storeAmount<= 0)
						continue;
					}
					$calculatePrice = CCatalogProduct::GetOptimalPrice(
						$row['ID'],
						1,
						array(2),
						'N',
						$row['PRICES'],
						$site['LID'],
						array()
					);

					if (!empty($calculatePrice))
					{
						$minPrice = $calculatePrice['RESULT_PRICE']['DISCOUNT_PRICE'];
						$fullPrice = $calculatePrice['RESULT_PRICE']['BASE_PRICE'];
						$minPriceCurrency = $calculatePrice['RESULT_PRICE']['CURRENCY'];
					}
					unset($calculatePrice);

					if ($minPrice <= 0)
						continue;

  	                if(empty($row['DESCRIPTION']) and $AVITO_NODESCR=='Y')
	                     continue;
	                if(empty($row['PICTURE']) and $AVITO_NOPHOTO=='Y')
	                     continue;
                    if (COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO") or $ABRICOS_AVITO_PHOTO)
				   {
	                 	    if(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO"))
		                 	    {
		                 	    	$photo=COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_PHOTO");
			                         $res = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("CODE" => $photo));
		                         }
	                 	    if($ABRICOS_AVITO_PHOTO)
		                 	    {
                                     $res = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("ID" => $ABRICOS_AVITO_PHOTO));
		               	    	}

							$arrPhoto=array();
							while ($ob = $res->Fetch())
							    {   if(!empty($ob['VALUE']))
								    {
										$PHOTO_VALUE = $ob['VALUE'];
										$file = CFile::GetFileArray($PHOTO_VALUE);
										$PHOTO_SRC = $file['SRC'];
										if (strncmp($PHOTO_SRC, '/', 1) == 0)
											$picturePath = $itemOptions['PROTOCOL'].$itemOptions['SITE_NAME'].CHTTP::urnEncode($PHOTO_SRC, 'utf-8');
										else
											$picturePath = $PHOTO_SRC;
										$arrPhoto[]=$picturePath;
		                            }
							    }
								$row['PICTURES'] = $arrPhoto;
	                           unset($picturePath);
	                           unset($ob);

					}
                    $dataAddress = explode(PHP_EOL, $AVITO_ADRESS);
                    $z=0;
					foreach ($dataAddress as $AdressArr)
					{
						if(strlen($AdressArr)<2)
						      continue;
						$itemsContent .= "<Ad>\n";
                        $stockContent.='<item>'."\n";
								if ($row['XML_ID']>0)
								{
									$itemsContent .= "<Id>".$row['XML_ID'].($z>0 ? '-'.$z :'')."</Id>\n";
									$stockContent.= "<id>".$row['XML_ID'].($z>0 ? '-'.$z :'')."</id>\n";
								}
	                            else
	                            {
		                            $itemsContent .= "<Id>".$row['ID'].($z>0 ? '-'.$z : '')."</Id>\n";
		                            $stockContent.= "<id>".$row['ID'].($z>0 ? '-'.$z : '')."</id>\n";
                                 }
                                if($AVITO_STOCK_CONST)
								  $stockContent.='<stock>'.$AVITO_STOCK_CONST.'</stock>'."\n";
							    elseif(!empty($AVITO_STORE[0]))
							      $stockContent.='<stock>'.$storeAmount.'</stock>'."\n";
							    else
								  $stockContent.= "<stock>".$row['QUANTITY']."</stock>\n";

	                          if(!empty($PROP_NAME) and $AVITO_CATEGORY_CUSTOM!='Y' and !$AVITO_CATEGORY)
	                          {
	                            $i=0;
	                          	foreach($PROP_NAME as $arrCat)
	                          	{
		                          	if($arrCat!='')
		                          	{
			                          	$itemsContent .= "<".CAbricosAvitoautoload::category_name($arrCat).">";
			                          	if($PROP_DATA[$i] and $content=CAbricosAvitoautoload::category_name(CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$PROP_DATA[$i])))
			                                $itemsContent .= $content;
			                            elseif($PROP_DEF[$i])
			                                $itemsContent .= yandex_text2xml($PROP_DEF[$i]);
			                          	$itemsContent .= "</".CAbricosAvitoautoload::category_name($arrCat).">\n";
		                          	}
		                          	$i++;
	                          	}
	                          }
                              else
                              {
	                            if($AVITO_CATEGORY_CUSTOM=='Y')
	                               {
									   $ar_result=CIBlockSection::GetList(Array("SORT"=>"ASC"), Array("IBLOCK_ID"=>$IBLOCK_ID,"ID"=>$row['CATEGORY_ID']),false, Array("UF_*"));
		                               $res=$ar_result->GetNext();
									   if($res)
										{
										    foreach($res as $key => $value)
										    {
												if(strpos($key,'UF_AVITO_') and $value)
												{
												    $keyName=str_replace('~UF_AVITO_','',$key);
												    if($keyName=="CONDITION" and $AVITO_CONDITION_ALT)
												      continue;
												      else
													$itemsContent .= "<".$keyName.">".yandex_text2xml($value)."</".$keyName.">\n";
												}

											}
			                            }
			                            	if($AVITO_CONDITION_ALT)
												$itemsContent .= "<Condition>".yandex_text2xml($AVITO_CONDITION_ALT)."</Condition>\n";
	                               }
	                            else    //используем настройки
	                            {
		                             if($AVITO_CONDITION_ALT)
		                            {
		                            $itemsContent .= "<Condition>".yandex_text2xml($AVITO_CONDITION_ALT)."</Condition>\n";
		                            }
		                            else
		                            {

		                             if($AVITO_CONDITION=='new')
			                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_NEW"))."</Condition>\n";
		                             elseif($AVITO_CONDITION=='new2')
			                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_NEW2"))."</Condition>\n";
		                             elseif($AVITO_CONDITION=='bu')
			                             $itemsContent .= "<Condition>".yandex_text2xml(GetMessage("AVITO_CONDITION_BU"))."</Condition>\n";
                                     }
		                             if($AVITO_CATEGORY==GetMessage("AVITO_ZAP"))
			                             $AVITO_CATEGORY=GetMessage("AVITO_ZAPCAT");

			                         $itemsContent .= "<Category>".yandex_text2xml($AVITO_CATEGORY)."</Category>\n";

		                             if(($AVITO_CATEGORY==GetMessage("AVITO_VODA")) or ($AVITO_CATEGORY==GetMessage("AVITO_VODA2")))
		                                 $itemsContent .= "<VehicleType>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</VehicleType>\n";
		                             elseif ($AVITO_CATEGORY==GetMessage("AVITO_ZAPCAT"))
		                               	$itemsContent .= "<TypeId>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</TypeId>\n";
		                             else
		                             {
		                               	if($AVITO_CATEGORY_TYPE)
		                               	$itemsContent .= "<GoodsType>".yandex_text2xml($AVITO_CATEGORY_TYPE)."</GoodsType>\n";
		                              }
		                             if($AVITO_APPAREL)
		                             {
		                                $itemsContent .= "<Apparel>".yandex_text2xml($AVITO_APPAREL)."</Apparel>\n";
		                             }
	                            }

	                          }
	                            if($AVITO_PHONE)
	                                $itemsContent .= "<ContactPhone>".yandex_text2xml($AVITO_PHONE)."</ContactPhone>\n";
	                            $itemsContent .= "<Address>".yandex_text2xml($AdressArr)."</Address>\n";
	                            $itemsContent .= "<ListingFee>".yandex_text2xml($AVITO_LISTINGFEE)."</ListingFee>\n";
	                            $itemsContent .= "<AdType>".yandex_text2xml($AVITO_ADTYPE)."</AdType>\n";
                                  if($DISPLAYAREAS)
									{
									 $dareas = explode(PHP_EOL, $DISPLAYAREAS);
									 $itemsContent .= "<DisplayAreas>";
									 foreach ($dareas as $AreasArr)
										{
											if($AreasArr)
											{
												$itemsContent .= "<Area>";
												$itemsContent .= yandex_text2xml($AreasArr);
												$itemsContent .= "</Area>";
											}
										}
										$itemsContent .= "</DisplayAreas>";
									}
						$y = 0;
						$avitoSizeValue='';
						foreach ($arYandexFields as $key)
						{
							switch ($key)
							{
								case 'name':
								    if(!empty($AVITO_TITLE_TEMPL))
										{

											 $itemsContent .= "<Title>".yandex_text2xml(CAbricosAvitoautoload::get_text($AVITO_TITLE_TEMPL,$row['NAME'],$sectArr[$row['CATEGORY_ID']]), true)."</Title>\n";
										}
                                        elseif(!empty($AVITO_TITLE_PROP))
										{ $title=false;
								          $title=CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$AVITO_TITLE_PROP);
									     if($title)
			                                 $itemsContent .= "<Title>".yandex_text2xml($title, true)."</Title>\n";
		                                  else
											$itemsContent .= "<Title>".yandex_text2xml($row['NAME'], true)."</Title>\n";
		                                 }
										else
										$itemsContent .= "<Title>".yandex_text2xml($row['NAME'], true)."</Title>\n";

									break;
								case 'description':
									if ($parametricFieldsExist)
									{
									    $itemsParam ='';
										foreach ($parametricFields as $paramKey => $prop_id)
										{
	                                    if ($AVITO_VIDEO and $prop_id==$AVITO_VIDEO)
												   { $valueS = yandex_get_value(
													$row,
													0,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$usedProtocol

												);

												   $avitoVideo=$valueS;
	                                                unset($valueS);
												   }
										if ($AVITO_SIZE and $prop_id==$AVITO_SIZE)
												   { $valueS = yandex_get_value(
													$row,
													0,
													$prop_id,
													$arProperties,
													$arUserTypeFormat,
													$usedProtocol
												);
												$avitoSizeValue=$valueS;
												   unset($valueS);
												   }
											$value = yandex_get_value(
												$row,
												'PARAM_'.$paramKey,
												$prop_id,
												$arProperties,
												$arUserTypeFormat,
												$usedProtocol
											);
											if ($value != '')
												$itemsParam .= $value.'<br> ';

											   unset($value);
										}
										unset($paramKey, $prop_id);
									}

									if(!empty($AVITO_DESCR_TEMPL))
										{

											$itemsContent .= "<Description><![CDATA[";
											$itemsContent .=yandex_text2xml(CAbricosAvitoautoload::get_text($AVITO_DESCR_TEMPL,$row['NAME'],$sectArr[$row['CATEGORY_ID']]))."<br>";
											if($AVITO_DESCR_TEMPL_PL=="Y")
												$itemsContent .=yandex_text2xml($row['DESCRIPTION'])."\r\n";

										}
                                	elseif(!empty($AVITO_DESCR_PROP))
										{ $descr=false;
								          $descr=CAbricosAvitoautoload::GetProp($row['ID'],$IBLOCK_ID,$AVITO_DESCR_PROP);
												     if($descr)
						                                 $itemsContent .= "<Description><![CDATA[".yandex_text2xml($descr);
					                                  else
														$itemsContent .= "<Description><![CDATA[".yandex_text2xml($row['DESCRIPTION']);
		                                 }
									else
										$itemsContent .= "<Description><![CDATA[".yandex_text2xml($row['DESCRIPTION']);
									if(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_TEXT")) $itemsContent .=yandex_text2xml(COption::GetOptionString("abricos.avitoautoload", "ABRICOS_AVITOAUTOLOAD_TEXT"));
									$itemsContent .=strip_tags($itemsParam,'<p>,<br>,<strong>,<em>,<ul>,<ol>,<li>');
									$itemsContent .="]]></Description>\n";
									break;

								case 'param':
								 break;
								}
						}

	                    	if($avitoSizeValue) {
	                        $itemsContent .= "<Size>".yandex_text2xml($avitoSizeValue)."</Size>\n";
	                        }
	                        elseif($AVITO_SIZE){
	                                	$itemsContent .= "<Size>".yandex_text2xml($AVITO_SIZE)."</Size>\n";
	                                }
	                        unset($avitoSizeValue);
                        if($AVITO_PROP_PRICE)
	                    {
	                      $resName = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], "sort", "asc", array("ID" => $AVITO_PROP_PRICE));
                                 while ($ob = $resName->GetNext())
								    {
									     if(!empty($ob['VALUE']))
			                                 $propPrice= $ob['VALUE'];
	                                 }
	                      if($propPrice and is_numeric($propPrice))
	                      {
	                      $minPrice=$propPrice;
	                      }
	                    }
	                    $itemsContent .= "<Price>".round($minPrice)."</Price>\n";

	                    $i=0;
						if(!empty($offer['PICTURE']) or !empty($row['PICTURE']) or !empty($row['PICTURES']))
							{
	                           $itemsContent .= " <Images>\n";
	                         if(!empty($offer['PICTURE']) or !empty($row['PICTURE']))
	                         {   if($AVITO_NODETAIL_PHOTO and $AVITO_NODETAIL_PHOTO=='N')
			                         {
										$picture = $row['PICTURE'];
										if (!empty($picture))
										$itemsContent .= "<Image  url='".$picture."'/>\n";
										unset($picture);
										$i=1;
									}
							}
							if(is_countable($row['PICTURES']) && count($row['PICTURES'])>0)
							{
								foreach ($row['PICTURES'] as $pict) {
									if($i<10){
										$itemsContent .="<Image  url='".$pict."'/>\n";
										$i++;
									}
									else break;
								}
							}
							$itemsContent .="</Images>\n";
							}
							if(!$avitoVideo and $AVITO_VIDEO)
							{
								$db_props = CIBlockElement::GetProperty($IBLOCK_ID, $row['ID'], array("sort" => "asc"), Array("ID"=>$AVITO_VIDEO));
								if($ar_props = $db_props->Fetch())
								    $avitoVideo = $ar_props["VALUE"];
							}
							if($avitoVideo)
								$itemsContent .= "<VideoURL>".$avitoVideo."</VideoURL>\n";
						$itemsContent .= "</Ad>\n";
						$stockContent.= "</item>\n";
						$z++;
					}
                }
				unset($row);
				unset($pict);
				unset($AdressArr);
				unset($propPrice);
				unset($avitoVideo);
				unset($AreasArr);
				unset($dareas);

				if ($MAX_EXECUTION_TIME > 0 && (getmicrotime() - START_EXEC_TIME) >= $MAX_EXECUTION_TIME)
					break;
			}
			unset($id);

			\CCatalogDiscount::ClearDiscountCache(array(
				'PRODUCT' => true,
				'SECTIONS' => true,
				'PROPERTIES' => true
			));
		}

		if ($itemsContent !== '')
			fwrite($itemsFile, $itemsContent);
		if ($stockContent and $AVITO_STOCK=='Y')
			fwrite($itemsFileStock, $stockContent);
		unset($itemsContent);
        unset($stockContent);
		unset($simpleIdsList, $skuIdsList);
		unset($items, $itemIdsList);
		if(CAbricosAvitoautoload::CurFile()) break;
	}
	while ($MAX_EXECUTION_TIME == 0 && $existItems);
}

if (empty($arRunErrors))
{
	if (is_resource($itemsFile))
		@fclose($itemsFile);
	if (is_resource($itemsFileStock))
		@fclose($itemsFileStock);
	unset($itemsFile);
	unset($itemsFileStock);
}

if (empty($arRunErrors))
{
	if ($MAX_EXECUTION_TIME == 0)
		$finalExport = true;
	if ($finalExport)
	{
		$process = true;
		$content = '';
        $contentStock ='';
		$items = file_get_contents($_SERVER["DOCUMENT_ROOT"].$itemFileName);
		if($AVITO_STOCK=='Y')
		$itemsStock = file_get_contents($_SERVER["DOCUMENT_ROOT"].$itemFileName2);
		if ($items === false)
		{
			$arRunErrors[] = GetMessage('YANDEX_STEP_ERR_DATA_FILE_NOT_READ');
			$process = false;
		}

		if ($process)
		{
			$content .= $items;
			if($AVITO_STOCK=='Y')
			$contentStock .= $itemsStock;
			unset($items);
			unset($itemsStock);
			$content .= "</Ads>\n";
			if($AVITO_STOCK=='Y')
			{
            $contentStock .= "</items>\n";
            file_put_contents($_SERVER["DOCUMENT_ROOT"].$sectionFileName2, $contentStock, FILE_APPEND);
            }
			if (file_put_contents($_SERVER["DOCUMENT_ROOT"].$sectionFileName, $content, FILE_APPEND) === false)
			{
				$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('YANDEX_ERR_SETUP_FILE_WRITE'));
				$process = false;
			}
		}
		if ($process)
		{
			unlink($_SERVER["DOCUMENT_ROOT"].$itemFileName);
           unlink($_SERVER["DOCUMENT_ROOT"].$itemFileName2);
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
			{
				if (!unlink($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
				{
					$arRunErrors[] = str_replace('#FILE#', $SETUP_FILE_NAME, GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_UNLINK_FILE'));
					$process = false;
				}
			}
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME_STOCK))
			{
				if (!unlink($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME_STOCK))
				{
					$arRunErrors[] = str_replace('#FILE#', $SETUP_FILE_NAME_STOCK, GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_UNLINK_FILE'));
					$process = false;
				}
			}
		}
		if ($process)
		{
			if (!rename($_SERVER["DOCUMENT_ROOT"].$sectionFileName, $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME))
			{
				$arRunErrors[] = str_replace('#FILE#', $sectionFileName, GetMessage('BX_CATALOG_EXPORT_YANDEX_ERR_UNLINK_FILE'));
			}
			rename($_SERVER["DOCUMENT_ROOT"].$sectionFileName2, $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME_STOCK);
		}
		unset($process);
	}
}
}
else $arRunErrors[] = GetMessage('ABRICOS_ERR_DEMO');
CCatalogDiscountSave::Enable();

if (!empty($arRunErrors))
	$strExportErrorMessage = implode('<br />',$arRunErrors);

if ($bTmpUserCreated)
{
	if (isset($USER_TMP))
	{
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}