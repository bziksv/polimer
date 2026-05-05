<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Option;
	
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$moduleId = 'esol.allimportexport';
$moduleJsId = str_replace('.', '_', $moduleId);
$moduleDemoExpiredFunc = $moduleJsId.'_demo_expired';
$moduleShowDemoFunc = $moduleJsId.'_show_demo';
Loader::includeModule($moduleId);
Loc::loadMessages(__FILE__);
\CJSCore::Init(array($moduleJsId.'_import'));

include_once(dirname(__FILE__).'/../install/demo.php');
if (call_user_func($moduleDemoExpiredFunc)) {
	require ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	call_user_func($moduleShowDemoFunc);
	require ($DOCUMENT_ROOT."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$APPLICATION->SetTitle(Loc::getMessage("ESOL_AIE_OPTION_PAGE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
if (!call_user_func($moduleDemoExpiredFunc)) {
	call_user_func($moduleShowDemoFunc);
}

global $USER;
$bUserIsAdmin = $USER->IsAdmin();
if(!$bUserIsAdmin)
{
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}


$aTabs = array(
	array("DIV" => "edit0", "TAB" => Loc::getMessage("ESOL_AIE_GROUP_TABLES"), "ICON" => "", "TITLE" => Loc::getMessage("ESOL_AIE_GROUP_TABLES")),
	array("DIV" => "edit1", "TAB" => Loc::getMessage("ESOL_AIE_RIGHTS"), "ICON" => "", "TITLE" => Loc::getMessage("ESOL_AIE_RIGHTS_TITLE")),
);
$tabControl = new CAdminTabControl("emtab", $aTabs, true, true);

if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['RestoreDefaults']) && !empty($_GET['RestoreDefaults']) && check_bitrix_sessid())
{
	Option::delete($moduleId);

	LocalRedirect($APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID);
}

$redirectUrl = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if(isset($_POST['Update']) && $_POST['Update'] === 'Y' && is_array($_POST['SETTINGS']))
	{
		foreach($_POST['SETTINGS'] as $k=>$v)
		{
			if(in_array($k, array('TABLES')))
			{
				$v = serialize($v);
			}
			\Bitrix\Main\Config\Option::set($moduleId, $k, $v);
		}
		
		$arUTables = \Bitrix\EsolAie\Utils::Unserialize(\Bitrix\Main\Config\Option::get($moduleId, 'USER_TABLES', ''));
		if(!is_array($arUTables)) $arUTables = array();
		if(is_array($_POST['SETTINGS']['TABLES']))
		{
			foreach($_POST['SETTINGS']['TABLES'] as $k=>$v)
			{
				if($v=='N' && strpos($k, 'tbl_')!==false)
				{
					$tblName = substr($k, 4);
					if(array_key_exists($tblName, $arUTables)) unset($arUTables[$tblName]);
				}
			}
		}
		
		if($_POST['NTABLE'] && $_POST['NTABLE']['TABLE'])
		{
			$tblParams = array_map('trim', $_POST['NTABLE']);
			$tblParams['TABLE'] = str_replace(array('"', "'"), '', $tblParams['TABLE']);
			$conn = \Bitrix\Main\Application::getConnection();
			$helper = $conn->getSqlHelper();
			if($row = $conn->query("SHOW TABLES LIKE '".$tblParams['TABLE']."'")->Fetch())
			{
				if($tblParams['MODULE'])
				{
					\Bitrix\Main\Loader::includeModule($tblParams['MODULE']);
					$tbl = $tblParams['TABLE'];
					$firstPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$tblParams['MODULE'].'/lib/';
					$loop = 0;
					$classFile = $className = '';
					while(strlen($classFile)==0 && $loop < 10)
					{
						$path = $firstPath.str_repeat('*/', $loop).'*.php';
						$arFiles = glob($path);
						while(strlen($classFile)==0 && ($file = array_shift($arFiles)))
						{
							$c = file_get_contents($file);
							if(strpos($c, '"'.$tbl.'"')!==false || strpos($c, "'".$tbl."'")!==false && preg_match_all('/class\s+(\w+)/is', $c, $m))
							{
								$ns = '';
								if(preg_match('/namespace\s+([\\\\\w]+)\s*;/is', $c, $m2))
								{
									$ns = $m2[1].'\\';
									if(strpos($ns, '\\')!==0) $ns = '\\'.$ns;
								}
								foreach($m[1] as $cn)
								{
									if(class_exists($ns.$cn) && $ns.$cn instanceof \Main\Entity\DataManager && is_callable(array($ns.$cn, 'getTableName')) && call_user_func(array($ns.$cn, 'getTableName'))==$tbl)
									{
										$classFile = $file;
										$className = $ns.$cn;
									}
								}
							}
						}
						$loop++;
					}
					if(strlen($className) > 0) $tblParams['CLASS'] = $className;
				}
				
				$arUTables[$tblParams['TABLE']] = $tblParams;
			}
		}
		
		\Bitrix\Main\Config\Option::set($moduleId, 'USER_TABLES', serialize($arUTables));
		$redirectUrl = $APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID.($_POST['emtab_active_tab'] ? '&emtab_active_tab='.$_POST['emtab_active_tab'] : '');
	}
}


$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo LANGUAGE_ID?>" name="esol_allimportexport_settings">
<? echo bitrix_sessid_post();

/*
$tabControl->BeginNextTab();
*/
?>

<?$tabControl->BeginNextTab();?>
	
	<?
	$arTables = \Bitrix\EsolAie\Utils::Unserialize(\Bitrix\Main\Config\Option::get($moduleId, 'TABLES', ''));
	if(!is_array($arTables)) $arTables = array();
	$arGroups = \Bitrix\EsolAie\Runner::GetEntities(true);
	foreach($arGroups as $k=>$v)
	{
		if(!is_array($v['ITEMS']) || empty($v['ITEMS'])) continue;

		?>
		<tr class="heading">
			<td colspan="2">
				<?echo (strlen($v['TITLE']) > 0 ? $v['TITLE'] : Loc::getMessage("ESOL_AIE_TABLES_OTHER"));?>
			</td>
		</tr>
		<?

		$aSubMenu2 = array();
		foreach($v['ITEMS'] as $entity=>$arItem)
		{
			?>
			<tr>
				<td width="50%">
					<input type="hidden" name="SETTINGS[TABLES][<?echo $entity;?>]" value="N">
					<input type="checkbox" name="SETTINGS[TABLES][<?echo $entity;?>]" value="Y" id="id<?echo $entity;?>" <?if($arTables[$entity]!='N'){echo ' checked';}?>>
				</td>
				<td width="50%">
					<label for="id<?echo $entity;?>"><?echo $arItem['TITLE'];?></label>
				</td>
			</tr>
			<?
		}
	}
	?>
	
	<tr class="heading">
		<td colspan="2">
			<?echo Loc::getMessage("ESOL_AIE_TABLES_NEW_TABLE");?>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<?
			$arModules = array_keys(\Bitrix\Main\ModuleManager::getInstalledModules());
			$arTables = array();
			$conn = \Bitrix\Main\Application::getConnection();
			$dbRes = $conn->query("SHOW TABLES");
			while($arr = $dbRes->Fetch())
			{
				$tbl = current($arr);
				if($tbl && !in_array($tbl, $arTables)) $arTables[] = $tbl;
			}
			?>
			<input type="text" name="NTABLE[NAME]" size="35" placeholder="<?echo Loc::getMessage("ESOL_AIE_TABLES_NEW_TABLE_NAME");?>">
			<select name="NTABLE[TABLE]">
				<option value=""><?echo Loc::getMessage("ESOL_AIE_TABLES_NEW_TABLE_TABLE");?></option>
				<?foreach($arTables as $tbl){?>
					<option value="<?echo htmlspecialcharsbx($tbl);?>"><?echo $tbl;?></option>
				<?}?>
			</select>
			<select name="NTABLE[MODULE]">
				<option value=""><?echo Loc::getMessage("ESOL_AIE_TABLES_NEW_TABLE_MODULE");?></option>
				<?foreach($arModules as $mod){?>
					<option value="<?echo htmlspecialcharsbx($mod);?>"><?echo $mod;?></option>
				<?}?>
			</select>
		</td>
	</tr>

<?$tabControl->BeginNextTab();

$module_id = $moduleId;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
		
$tabControl->Buttons();?>
<script type="text/javascript">
function RestoreDefaults()
{
if (confirm('<? echo CUtil::JSEscape(Loc::getMessage("ESOL_AIE_OPTIONS_BTN_HINT_RESTORE_DEFAULT_WARNING")); ?>'))
	window.location = "<?echo $APPLICATION->GetCurPage()?>?lang=<? echo LANGUAGE_ID; ?>&mid_menu=1&mid=<? echo $moduleId; ?>&RestoreDefaults=Y&<?=bitrix_sessid_get()?>";
}
</script>
<input type="submit" name="Update" value="<?echo Loc::getMessage("ESOL_AIE_OPTIONS_BTN_SAVE")?>">
<input type="hidden" name="Update" value="Y">
<?/*?><input type="reset" name="reset" value="<?echo Loc::getMessage("ESOL_AIE_OPTIONS_BTN_RESET")?>">
<input type="button" title="<?echo Loc::getMessage("ESOL_AIE_OPTIONS_BTN_HINT_RESTORE_DEFAULT")?>" onclick="RestoreDefaults();" value="<?echo Loc::getMessage("ESOL_AIE_OPTIONS_BTN_RESTORE_DEFAULT")?>"><?*/?>
<?$tabControl->End();?>
</form>
<?
if(strlen($redirectUrl) > 0)
{
	LocalRedirect($redirectUrl);
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>