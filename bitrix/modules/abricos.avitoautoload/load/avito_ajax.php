<?
use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog;
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("abricos.avitoautoload");
CModule::IncludeModule("catalog");
CModule::IncludeModule("iblock");
if($_POST['iblockId'])
{
	$IBLOCK_ID=$_POST['iblockId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	}
							$intCount = 0;
							if($FILTER_DATA)
							{
								foreach ($FILTER_DATA as $arParamDetail)
								{
									echo CAbricosAvitoautoload::addParamRow($arIBlock, $intCount, $arParamDetail, '',$FILTER_PREF[$intCount],$FILTER_INPUT[$intCount]);
									?>

									<?
									$intCount++;
								}
							}
							if ($intCount == 0)
							{
								echo CAbricosAvitoautoload::addParamRow($arIBlock, $intCount, '', '','','');
								$intCount++;
							}
}
if($_POST['iblId'])
{ 	$IBLOCK_ID=$_POST['iblId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	} echo CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'ABRICOS_AVITO_PHOTO',$ABRICOS_AVITO_PHOTO);
}
if($_POST['IbTitleId'])
{
$IBLOCK_ID=$_POST['IbTitleId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	}
 echo  CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_TITLE_PROP',$AVITO_TITLE_PROP);

}
if($_POST['IbVideoId'])
{
$IBLOCK_ID=$_POST['IbVideoId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	}
 echo  CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_VIDEO',$AVITO_TITLE_PROP);

}
if($_POST['descrId'])
{
$IBLOCK_ID=$_POST['descrId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	}
 echo  CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_DESCR_PROP',$AVITO_DESCR_PROP);

}
if($_POST['prId'])
{
$IBLOCK_ID=$_POST['prId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	$arIBlock['OFFERS_PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
	if ($boolOffers)
	{
		$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
		while ($arProp = $rsProps->Fetch())
		{
			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arProp['VALUES'] = array();
					$rsPropEnums = CIBlockProperty::GetPropertyEnum($arProp['ID'],array('sort' => 'asc'),array('IBLOCK_ID' => $intOfferIBlockID));
					while ($arPropEnum = $rsPropEnums->Fetch())
					{
						$arProp['VALUES'][$arPropEnum['ID']] = $arPropEnum['VALUE'];
					}
				}
				$arIBlock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
					$arSelectOfferProps[] = $arProp['ID'];
			}
		}
	}
 echo  CAbricosAvitoautoload::addRowParam($arIBlock['PROPERTY'], 'AVITO_PROP_PRICE',$AVITO_PROP_PRICE);
}
if($_POST['propId'])
{
	$IBLOCK_ID=$_POST['propId'];
	$dbRes = CIBlockProperty::GetList(
		array('SORT' => 'ASC'),
		array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y')
	);
	$arIBlock['PROPERTY'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arIBlock['PROPERTY'][$arRes['ID']] = $arRes;
	}
							$intCountProp = 0;
							if($PROP_DATA)
							{
								foreach ($PROP_DATA as $arParamDetail)
								{
									echo CAbricosAvitoautoload::addPropRow($arIBlock, $intCountProp, $arParamDetail, '',$PROP_NAME[$intCountProp],$PROP_DEF[$intCountProp]);
									?>

									<?
									$intCountProp++;
								}
							}
							if ($intCountProp == 0)
							{
								echo CAbricosAvitoautoload::addPropRow($arIBlock, $intCountProp, '', '','','');
								$intCountProp++;
							}
}

?>
