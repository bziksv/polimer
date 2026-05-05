<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$moduleId = 'esol.allimportexport';
$moduleIdUl = $moduleFilePrefix = 'esol_allimportexport';
Loader::includeModule($moduleId);

$aMenu = array();

global $USER;
$bUserIsAdmin = $USER->IsAdmin();

$bHasWRight = true;
if($APPLICATION->GetGroupRight($moduleId) < "W")
{
	$bHasWRight = false;
}

if($bUserIsAdmin || $bHasWRight)
{
	$entityId = '';
	if(class_exists('\Bitrix\Main\Context'))
	{
		$oRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
		$entityId = $oRequest->get('entity');
	}

	$aSubMenu = array();
	$arGroups = array();
	if(class_exists('\Bitrix\EsolAie\Runner'))
	{
		$arGroups = \Bitrix\EsolAie\Runner::GetEntities();
	}
	foreach($arGroups as $k=>$v)
	{
		if(!is_array($v['ITEMS']) || empty($v['ITEMS'])) continue;

		$aSubMenu2 = array();
		foreach($v['ITEMS'] as $k2=>$arItem)
		{
			$aSubMenu2[] = array(
				"text" => $arItem['TITLE'],
				"title" => $arItem['TITLE'],
				"dynamic" => false,
				"module_id" => $moduleId,
				"items_id" => $moduleIdUl."_".$k."_".$k2,
				
				//"url" => $moduleFilePrefix."_export.php?lang=".LANGUAGE_ID."&entity=".$k2
				"items" => array(
					array(
						"text" => Loc::getMessage('ESOL_AIE_TITLE_EXPORT'),
						"title" => Loc::getMessage("ESOL_AIE_TITLE_EXPORT"),
						"url" => $moduleFilePrefix."_export.php?lang=".LANGUAGE_ID."&entity=".$k2,
						"more_url" => array(
							//($k2==$entityId ? $moduleFilePrefix."_export.php" : ''), 
						),
					),
					array(
						"text" => Loc::getMessage("ESOL_AIE_TITLE_IMPORT"),
						"title" => Loc::getMessage("ESOL_AIE_TITLE_IMPORT"),
						"url" => $moduleFilePrefix."_import.php?lang=".LANGUAGE_ID."&entity=".$k2,
						"more_url" => array(
							//($k2==$entityId ? $moduleFilePrefix."_import.php" : ''),
						),
					),
					array(
						"text" => Loc::getMessage("ESOL_AIE_TITLE_LIST"),
						"title" => Loc::getMessage("ESOL_AIE_TITLE_LIST"),
						"url" => $moduleFilePrefix."_list.php?lang=".LANGUAGE_ID."&entity=".$k2,
						"more_url" => array(
							//($k2==$entityId ? $moduleFilePrefix."_list.php" : ''),
						),
					)
				)
			);
		}
		if(strlen($v['TITLE']) > 0)
		{
			$aSubMenu[] = array(
				"text" => $v['TITLE'],
				"title" => $v['TITLE'],
				"dynamic" => false,
				"module_id" => $moduleId,
				"items_id" => $moduleIdUl."_".$k,
				"items" => $aSubMenu2
			);
		}
		else
		{
			foreach($aSubMenu2 as $v2)
			{
				$aSubMenu[] = $v2;
			}
		}
	}
	
	if($bUserIsAdmin)
	{
		$aSubMenu[] = array(
			"text" => Loc::getMessage("ESOL_AIE_TITLE_OPTIONS"),
			"title" => Loc::getMessage("ESOL_AIE_TITLE_OPTIONS"),
			"url" => $moduleFilePrefix."_options.php?lang=".LANGUAGE_ID,
			"module_id" => $moduleId,
			"items_id" => $moduleIdUl."_options",
		);
	}
	
	$aMenu[] = array(
		"parent_menu" => "global_menu_services",
		"section" => $moduleIdUl,
		"sort" => 1,
		"text" => Loc::getMessage("ESOL_AIE_TITLE_PARENT"),
		"title" => Loc::getMessage("ESOL_AIE_TITLE_PARENT"),
		"icon" => $moduleIdUl."_menu_icon",
		"items_id" => "menu_".$moduleIdUl."_parent",
		"module_id" => $moduleId,
		"items" => $aSubMenu,
	);
}

return $aMenu;
?>