<?

IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
 $module_id = "abricos.avitoautoload";
CModule::IncludeModule($module_id);

 if($_POST['ABRICOS_AVITOAUTOLOAD_TIME'])
 {CAgent::RemoveAgent("Agent_FileMerge();", "abricos.avitoautoload");
CAgent::AddAgent(
    "Agent_FileMerge();",
    "abricos.avitoautoload",
    "N",
    ($_POST['ABRICOS_AVITOAUTOLOAD_TIME']*60),
    "",
    "Y",
    "",
    10);
 }
$arAllOptions = array(
	"main" => Array(
		Array("ABRICOS_AVITOAUTOLOAD_TEXT",  GetMessage('ABRICOS_AVITOAUTOLOAD_TEXT'),  "", Array("textarea",2)),
		Array("ABRICOS_AVITOAUTOLOAD_PHOTO",  GetMessage('ABRICOS_AVITOAUTOLOAD_PHOTO'),  "", Array("text", "")),
	//Array("ABRICOS_AVITOAUTOLOAD_CATEGORY",  GetMessage('ABRICOS_AVITOAUTOLOAD_CATEGORY'),  "", Array("text","")),
		Array("ABRICOS_AVITOAUTOLOAD_FILE",  GetMessage('ABRICOS_AVITOAUTOLOAD_FILE'),  "", Array("textarea",2)),
	    Array("ABRICOS_AVITOAUTOLOAD_TIME",  GetMessage('ABRICOS_AVITOAUTOLOAD_TIME'),  "", Array("text", "")),
                  )
);

$aTabs = array(
	array("DIV" => "avitoautoload", "TAB" => GetMessage("ABRICOS_AVITOAUTOLOAD_TAB_NAME_SETTINGS"), "TITLE" => GetMessage("ABRICOS_AVITOAUTOLOAD_TAB_NAME_SETTINGS")),

	);

if( (isset($_REQUEST['save']) || isset($_REQUEST['apply']) ) && check_bitrix_sessid()){
	__AdmSettingsSaveOptions($module_id, $arAllOptions['main']);
}
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();

?><form method="post" name="abricos_options" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>"><?
echo bitrix_sessid_post();
$tabControl->BeginNextTab();
__AdmSettingsDrawList('abricos.avitoautoload',$arAllOptions['main']);
$tabControl->Buttons(array());
$tabControl->End();
?></form>
<?
echo BeginNote();
echo GetMessage("ABRICOS_AVITOAUTOLOAD_NOTE_SETTINGS");
echo EndNote();
?>