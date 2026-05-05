<?
use Bitrix\Main\Entity\Query,
	Bitrix\Main\Entity\ExpressionField,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.allimportexport';
$moduleFilePrefix = 'esol_allimportexport';
$moduleJsId = str_replace('.', '_', $moduleId);
$moduleJsId2 = $moduleJsId;
$moduleDemoExpiredFunc = $moduleJsId2.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId2.'_show_demo';
Loader::includeModule($moduleId);
\CJSCore::Init(array($moduleJsId.'_list'));
Loc::loadMessages(__FILE__);

include_once(dirname(__FILE__).'/../install/demo.php');
if (call_user_func($moduleDemoExpiredFunc)) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	call_user_func($moduleShowDemoFunc);
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
$entityId = $oRequest->get('entity');

if($MODE!='AJAX' && !\Bitrix\EsolAie\Entity\Utils::EntityTableExists($entityId))
{
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	echo 'Entity not exists';
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

if ($REQUEST_METHOD == "POST" && $MODE=='AJAX')
{
	if($ACTION=='SHOW_MODULE_MESSAGE')
	{
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		?><div><?
		call_user_func($moduleShowDemoFunc, true);
		?></div><?
		die();
	}
}

$sTableID = "tbl_esolaie_".$entityId."_list";
$instance = \Bitrix\Main\Application::getInstance();
$context = $instance->getContext();
$request = $context->getRequest();

$oSort = new CAdminSorting($sTableID, "ID", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$fl = \Bitrix\EsolAie\Export\FieldList::getInstance($entityId, 'true');
$entityDataClass = $fl->GetEntityClass();
$entityTitle = $fl->GetEntityTitle();
//$arEntityFields = $fl->GetEntityFields();
$arEntityFields = $fl->GetEntityFieldsForFilter();
$ee = \Bitrix\EsolAie\Export\Exporter::getInstance($entityId);

$arFilterFields = array_map('ToLower', array_keys($arEntityFields));

$lAdmin->InitFilter($arFilterFields);

$filter = array();

foreach($arEntityFields as $k=>$v)
{
	$key = ToLower(str_replace('.', '___', $k));
	if($v['primary'])
	{
		if(!empty(${'find_entity_'.$key.'_start'})) $filter[">=".$k] = ${'find_entity_'.$key.'_start'};
		if(!empty(${'find_entity_'.$key.'_end'})) $filter["<=".$k] = ${'find_entity_'.$key.'_end'};
	}
	elseif($v['data_type']=='date' || $v['data_type']=='datetime')
	{
		\Bitrix\EsolAie\Export\Utils::AddDateFilter($filter, $_REQUEST, $k, 'find_entity_'.$key);
	}
	else
	{
		$value = ${'find_entity_'.$key};
		$comp = ${'find_entity_'.$key.'_comp'};
		$op = \Bitrix\EsolAie\Export\Utils::GetStringOperation($value, $comp);
		if(strlen($value) > 0 || $value===false)
		{
			$filter[$op.$k] = $value;
		}
	}
}


/*if($lAdmin->EditAction())
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$ID = (int)$ID;

		if ($ID <= 0 || !$lAdmin->IsUpdated($ID))
			continue;
		
		$dbRes = $entityDataClass::update($ID, $arFields);
		if(!$dbRes->isSuccess())
		{
			$error = '';
			if($dbRes->getErrors())
			{
				foreach($dbRes->getErrors() as $errorObj)
				{
					$error .= $errorObj->getMessage().'. ';
				}
			}
			if($error)
				$lAdmin->AddUpdateError($error, $ID);
			else
				$lAdmin->AddUpdateError(Loc::getMessage("ESOL_RR_ERROR_UPDATING_REC")." (".$arFields["ID"].", ".$arFields["ORL_URL"].", ".$arFields["NEW_URL"].")", $ID);
		}
	}
}

if(($arID = $lAdmin->GroupAction()))
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = Array();
		$dbResultList = $entityDataClass::getList(array('filter'=>$filter, 'select'=>array('ID')));
		while($arResult = $dbResultList->Fetch())
			$arID[] = $arResult['ID'];
	}

	foreach ($arID as $ID)
	{
		if(strlen($ID) <= 0)
			continue;

		switch ($_REQUEST['action'])
		{
			case "delete":
				$dbRes = $entityDataClass::delete($ID);
				if(!$dbRes->isSuccess())
				{
					$error = '';
					if($dbRes->getErrors())
					{
						foreach($dbRes->getErrors() as $errorObj)
						{
							$error .= $errorObj->getMessage().'. ';
						}
					}
					if($error)
						$lAdmin->AddGroupError($error, $ID);
					else
						$lAdmin->AddGroupError(Loc::getMessage("ESOL_RR_ERROR_DELETING_TYPE"), $ID);
				}
				break;
		}
	}
}*/

$arHeaders = array();
foreach($arEntityFields as $k=>$v)
{
	$arHeaders[] = array("id"=>$k, "content"=>$v['title'], "sort"=>$k, "default"=>(bool)(strpos($k, '.')===false));
}

$lAdmin->AddHeaders($arHeaders);

$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

$usePageNavigation = true;
$navyParams = CDBResult::GetNavParams(CAdminResult::GetNavSize(
	$sTableID,
	array('nPageSize' => 20, 'sNavID' => $APPLICATION->GetCurPage())
));
if ($navyParams['SHOW_ALL'])
{
	$usePageNavigation = false;
}
else
{
	$navyParams['PAGEN'] = (int)$navyParams['PAGEN'];
	$navyParams['SIZEN'] = (int)$navyParams['SIZEN'];
}

$arRels = array();
foreach($arVisibleColumns as $col)
{
	if(strpos($col, '.')!==false)
	{
		$arRels[] = preg_replace('/\.[^\.]*$/', '', $col);
	}
}

$arGroup = array();
$arSelect = array();
$arRelEntities = array();
foreach($arEntityFields as $k=>$v)
{
	if(strpos($k, '.')===false)
	{
		$arSelect[] = $k;
		if(isset($v['primary']) && $v['primary'])
		{
			$arGroup[] = $k;
		}
	}
	else
	{
		$key = str_replace('.', '/', $k);
		$base = preg_replace('/\.[^\.]*$/', '', $k);
		if(in_array($k, $arVisibleColumns) || (in_array($base, $arRels) && isset($v['primary']) && $v['primary']))
		{
			
			$arSelect[$key] = $k;
			if(!isset($arRelEntities[$base]))
			{
				$arRelEntities[$base] = array('primaries'=>array(), 'fields'=>array());
			}
			if(isset($v['primary']) && $v['primary'])
			{
				$arRelEntities[$base]['primaries'][] = $key;
			}
			$arRelEntities[$base]['fields'][] = $key;
		}
	}
}

foreach($arRelEntities as $k=>$v)
{
	if(empty($v['primaries'])) 
	{
		$arRelEntities[$k]['primaries'] = $v['fields'];
	}
}

$getListParams = array(
	'select' => $arSelect,
	'filter' => $filter,
);

if ($usePageNavigation)
{
	$getListParams['limit'] = $navyParams['SIZEN'];
	$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
}

if ($usePageNavigation)
{
	$countQuery = new Query($entityDataClass::getEntity());
	$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
	$countQuery->setFilter($getListParams['filter']);
	$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
	unset($countQuery);
	$totalCount = (int)$totalCount['CNT'];
	if ($totalCount > 0)
	{
		$totalPages = ceil($totalCount/$navyParams['SIZEN']);
		if ($navyParams['PAGEN'] > $totalPages)
			$navyParams['PAGEN'] = $totalPages;
		$getListParams['limit'] = $navyParams['SIZEN'];
		$getListParams['offset'] = $navyParams['SIZEN']*($navyParams['PAGEN']-1);
	}
	else
	{
		$navyParams['PAGEN'] = 1;
		$getListParams['limit'] = $navyParams['SIZEN'];
		$getListParams['offset'] = 0;
	}
}

$getListParams['order'] = array();
if(array_key_exists(ToUpper($by), $arEntityFields)) $getListParams['order'][ToUpper($by)] = ToUpper($order);
foreach($getListParams['order'] as $k=>$v)
{
	//if(!in_array($k, $arGroup)) unset($getListParams['order'][$k]);
}
foreach($arGroup as $k)
{
	if(!isset($getListParams['order'][$k])) $getListParams['order'][$k] = 'ASC';
}

if(method_exists('\Bitrix\Main\ORM\Query\Query', 'enablePrivateFields')) $getListParams['private_fields'] = true;
$rsData = new CAdminResult($entityDataClass::getList(array_merge($getListParams, array('select'=>$arGroup))), $sTableID);
if ($usePageNavigation)
{
	$rsData->NavStart($getListParams['limit'], $navyParams['SHOW_ALL'], $navyParams['PAGEN']);
	$rsData->NavRecordCount = $totalCount;
	$rsData->NavPageCount = $totalPages;
	$rsData->NavPageNomer = $navyParams['PAGEN'];
}
else
{
	$rsData->NavStart();
}

$lAdmin->NavText($rsData->GetNavPrint($entityTitle));


$arFilter = array();
while($arRecord = $rsData->NavNext(true, "f_"))
{
	if(count($arGroup) > 1)
	{
		$arFilter['LOGIC'] = 'OR';
		$arFilterPart = array();
		foreach($arGroup as $key)
		{
			$arFilterPart[$key] = $arRecord[$key];
		}
		$arFilter[] = $arFilterPart;
	}
	elseif(count($arGroup) == 1)
	{
		foreach($arGroup as $key)
		{
			if(!isset($arFilter[$key])) $arFilter[$key] = array();
			$arFilter[$key][] = $arRecord[$key];
		}
	}
}

$arData = array();
if(!empty($arFilter))
{
	$dbRes = $entityDataClass::getList(array_merge($getListParams, array('filter'=>$arFilter, 'limit'=>null, 'offset'=>null)));
	while($arRecord = $dbRes->Fetch())
	{
		foreach($arRecord as $key=>$val)
		{
			$key2 = str_replace('/', '.', $key);
			$arFieldParams = (isset($arEntityFields[$key2]) ? $arEntityFields[$key2] : array());
			if(is_array($val))
			{
				$arVals = array();
				foreach($val as $k2=>$v2)
				{
					$arVals[] = $ee->GetFieldValue($arFieldParams, $v2);
				}
				$arRecord[$key] = $arVals;
			}
			else
			{
				$arRecord[$key] = $ee->GetFieldValue($arFieldParams, $val);
			}
		}
		
		$arKeys = array();
		foreach($arGroup as $key)
		{
			$arKeys[$key] = $arRecord[$key];
		}
		$dataKey = serialize($arKeys);
		if(!isset($arData[$dataKey]))
		{
			$arData[$dataKey] = $arRecord;
		}
		else
		{
			foreach($arRelEntities as $relEntity)
			{
				$needAdd = false;
				foreach($relEntity['primaries'] as $prField)
				{
					if(is_array($arData[$dataKey][$prField]) || $arData[$dataKey][$prField]!=$arRecord[$prField]) $needAdd = true;
				}
				if($needAdd)
				{
					foreach($relEntity['fields'] as $rField)
					{
						if(!is_array($arData[$dataKey][$rField])) $arData[$dataKey][$rField] = array($arData[$dataKey][$rField]);
						$arData[$dataKey][$rField][] = $arRecord[$rField];
					}
				}
			}
		}
	}
}


//while($arRecord = $rsData->NavNext(true, "f_"))
foreach($arData as $arRecord)
{
	$row =& $lAdmin->AddRow($arRecord['ID'], $arRecord, '', '');
	
	foreach($arEntityFields as $k=>$v)
	{
		$k2 = str_replace('.', '/', $k);
		if(isset($arRecord[$k2]))
		{
			$val = $arRecord[$k2];
			if($v['serialized']) $val = serialize($val);
			elseif(is_array($val)) $val = implode($_GET['mode']=='excel' ? ';' : '<hr width="90%" size="1">', $val);
			if(isset($v['page_url']) && $v['page_url'])
			{
				$pageUrl = $v['page_url'];
				if(preg_match_all('/#([\w\d\_]+)#/', $pageUrl, $m))
				{
					foreach($m[0] as $k3=>$v3)
					{
						if(isset($arRecord[$m[1][$k3]]))
						{
							$varName = $m[1][$k3];
							if(strpos($k, '.')!==false) $varName = str_replace('.', '/', preg_replace('/\.[^\.]+$/', '.'.$varName, $k));
							$pageUrl = str_replace($v3, (is_array($arRecord[$varName]) ? reset($arRecord[$varName]) : $arRecord[$varName]), $pageUrl);
						}
					}
				}
				$val = '<a href="'.$pageUrl.'">'.$val.'</a>';
			}
			$row->AddField($k, $val);
		}
	}
	
	/*$arActions = array();
	$arActions[] = array("ICON"=>"edit", "TEXT"=>Loc::getMessage("ESOL_RR_TO_REDIRECT"), "ACTION"=>$lAdmin->ActionRedirect($moduleFilePrefix."_redirect_item.php?ID=".$arRedirect['ID']."&lang=".LANG), "DEFAULT"=>true);

	$arActions[] = array("SEPARATOR" => true);
	$arActions[] = array("ICON"=>"delete", "TEXT"=>Loc::getMessage("ESOL_RR_REDIRECT_DELETE"), "ACTION"=>"if(confirm('".GetMessageJS('ESOL_RR_REDIRECT_DELETE_CONFIRM')."')) ".$lAdmin->ActionDoGroup($arRedirect['ID'], "delete"));

	$row->AddActions($arActions);*/
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount()
		),
		array(
			"counter" => true,
			"title" => Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => "0"
		),
	)
);

/*$lAdmin->AddGroupActionTable(
	array(
		"delete" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE"),
	)
);*/

$aContext = array(
	/*array(
		"ICON" => "btn_new",
		"TEXT" => Loc::getMessage("ESOL_RR_NEW_REDIRECT"),
		"LINK" => $moduleFilePrefix."_redirect_item.php?lang=".LANG,
		"LINK_PARAM" => "",
		"TITLE" => Loc::getMessage("ESOL_RR_NEW_REDIRECT")
	),*/
);
$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle($entityTitle);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if (!call_user_func($moduleDemoExpiredFunc)) {
	call_user_func($moduleShowDemoFunc);
}

/*$aMenu = array(
	array(
		"TEXT" => Loc::getMessage("ESOL_RR_NEW_REDIRECT"),
		"ICON" => "btn_green",
		"LINK" => $moduleFilePrefix."_redirect_item.php?lang=".LANG
	)
);

$context = new CAdminContextMenu($aMenu);
$context->Show();*/
?>

<form name="find_form" method="GET" action="<?echo $APPLICATION->GetCurPage()?>?">
<?
$arFindFields = Array();
foreach($arEntityFields as $k=>$v)
{
	if(substr_count($k, '.') > 1) continue;
	$key = ToLower(str_replace('.', '___', $k));
	$arFindFields[$key] = $v['title'];
}
$oFilter = new CAdminFilter($sTableID."_filter", $arFindFields);

$oFilter->Begin();

foreach($arEntityFields as $k=>$v)
{
	if(substr_count($k, '.') > 1) continue;
	$key = ToLower(str_replace('.', '___', $k));
	if($v['primary'])
	{
	?>
	<tr>
		<td><?echo $v['title']?>:</td>
		<td nowrap>
			<input type="text" name="find_entity_<?echo $key;?>_start" size="10" value="<?echo htmlspecialcharsex(${'find_entity_'.$key.'_start'})?>">
			...
			<input type="text" name="find_entity_<?echo $key;?>_end" size="10" value="<?echo htmlspecialcharsex(${'find_entity_'.$key.'_end'})?>">
		</td>
	</tr>
	<?
	}
	elseif($v['data_type']=='date' || $v['data_type']=='datetime')
	{
		$GLOBALS["find_entity_".$key."_from_FILTER_PERIOD"] = ${'find_entity_'.$key.'_from_FILTER_PERIOD'};
		$GLOBALS["find_entity_".$key."_from_FILTER_DIRECTION"] = ${'find_entity_'.$key.'_from_FILTER_DIRECTION'};
	?>
	<tr>
		<td><?echo $v['title']?>:</td>
		<td>
			<?echo CalendarPeriod("find_entity_".$key."_from", htmlspecialcharsex(${'find_entity_'.$key.'_from'}), "find_entity_".$key."_to", htmlspecialcharsex(${'find_entity_'.$key.'_from'}), "find_form", "Y")?></font>
		</td>
	</tr>
	<?
	}
	else
	{
	?>
	<tr>
		<td><?echo $v['title']?>:</td>
		<td>
			<select name="find_entity_<?=$key?>_comp">
				<option value="eq" <?if(${'find_entity_'.$key.'_comp'}=='eq'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_COMPARE_EQ')?></option>
				<option value="neq" <?if(${'find_entity_'.$key.'_comp'}=='neq'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_COMPARE_NEQ')?></option>
				<option value="contain" <?if(${'find_entity_'.$key.'_comp'}=='contain'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_COMPARE_CONTAIN')?></option>
				<option value="not_contain" <?if(${'find_entity_'.$key.'_comp'}=='not_contain'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_COMPARE_NOT_CONTAIN')?></option>
				<option value="empty" <?if(${'find_entity_'.$key.'_comp'}=='empty'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_IS_EMPTY')?></option>
				<option value="not_empty" <?if(${'find_entity_'.$key.'_comp'}=='not_empty'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_IS_NOT_EMPTY')?></option>
				<option value="logical" <?if(${'find_entity_'.$key.'_comp'}=='logical'){echo 'selected';}?>><?=Loc::getMessage('ESOL_AIE_IS_LOGICAL')?></option>
			</select>
			<input type="text" name="find_entity_<?=$key?>" value="<?echo htmlspecialcharsex(${'find_entity_'.$key})?>" size="30">
		</td>
	</tr>
	<?
	}
}

$oFilter->Buttons(
	array(
		"table_id" => $sTableID,
		"url" => $APPLICATION->GetCurPage().'?entity='.$entityId,
		"form" => "find_form"
	)
);
$oFilter->End();
?>
</form>

<?
$lAdmin->DisplayList();
require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
?>