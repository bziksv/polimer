<?
namespace Arturgolubev\Ecommerce\Admin;

use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;

use \Arturgolubev\Ecommerce\Unitools as UTools;

class SettingsHelper {
	const MODULE_ID = 'arturgolubev.ecommerce';
	
	static function checkModuleRules(){
		$arSearchNoteSettings = array();
		
		$logDays = IntVal(Option::get("main", "event_log_cleanup_days"));
		if(!$logDays){
			$arSearchNoteSettings[] = GetMessage("ARTURGOLUBEV_EC_MAIN_CLEAR_LOG_NOSET");
		}
		
		if(count($arSearchNoteSettings)>0){
			\CAdminMessage::ShowMessage(array("DETAILS"=>implode('<br>', $arSearchNoteSettings), "MESSAGE" => GetMessage("ARTURGOLUBEV_EC_ERROS_SETTING_TITLE"), "HTML"=>true));
		}
	}
}