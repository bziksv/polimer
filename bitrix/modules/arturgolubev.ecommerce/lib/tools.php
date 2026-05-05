<?
namespace Arturgolubev\Ecommerce;

use \Bitrix\Main\Loader;

use \Arturgolubev\Ecommerce\Unitools as UTools;
use \Arturgolubev\Ecommerce\Encoding;

class Tools {
	const MODULE_ID = 'arturgolubev.ecommerce';
	
	static function addDebug($text){
		return (UTools::getBoolSetting('debug')) ? $text : '';
	}

	static function getMainSettings(){
		$settings = [
			'main' => [
				'container' => UTools::getSiteSetting('container', 'dataLayer'),
				'order_id_field' => UTools::getSetting('get_order_id_from', 'ID'),
			],
			'google' => [
				'enable' => (!UTools::getBoolSiteSetting("ga_off")),
				'params' => []
			],
			'yandex' => [
				'enable' => (!UTools::getBoolSiteSetting("ya_off")),
				'params' => []
			],
			'facebook' => [
				'enable' => (UTools::getSiteSetting("fb_off") == 'N'),
				'params' => []
			]
		];

		if($settings['google']['enable']){
			$settings['google']['params']['payship_type'] = UTools::getSiteSetting("ga_payship_type");
		}

		if($settings['yandex']['enable']){
			$settings['yandex']['params']['target_order'] = intval(UTools::getSiteSetting("yandex_target_order"));
			
			$settings['yandex']['params']['counter_id'] = intval(UTools::getSiteSetting("yandex_goal_counter_id"));
			$settings['yandex']['params']['goal_order'] = UTools::getBoolSiteSetting("yandex_goal_order");
			$settings['yandex']['params']['goal_cart'] = UTools::getBoolSiteSetting("yandex_goal_cart");
			$settings['yandex']['params']['goal_product'] = UTools::getBoolSiteSetting("yandex_goal_product");
			$settings['yandex']['params']['goal_add'] = UTools::getBoolSiteSetting("yandex_goal_add");
			$settings['yandex']['params']['goal_remove'] = UTools::getBoolSiteSetting("yandex_goal_remove");
			$settings['yandex']['params']['goal_purchase'] = UTools::getBoolSiteSetting("yandex_goal_purchase");
		}

		return $settings;
	}

	static function checkDisable(){
		if(!Loader::includeModule(self::MODULE_ID) || defined("ADMIN_SECTION"))
			return 1;
		
		if(UTools::getBoolSetting('off_mode') || UTools::getBoolSiteSetting('off_mode'))
			return 1;
		
		return 0;
	}
	
	static function incFooterComponent(){
		global $APPLICATION;
		
		ob_start();
			$APPLICATION->IncludeComponent("arturgolubev:ecommerce.check", ".default", [], false, ["HIDE_ICONS" => "Y"]);
			$actions = ob_get_contents();
		ob_end_clean();
		
		return $actions;
	}
	
	static function isCartPage($page = ''){
		if(!$page)
			$page = UTools::getCurPageParam();
		
		return (UTools::getSiteSetting('cart_page') && (Encoding::exStripos($page, UTools::getSiteSetting('cart_page')) !== false));
	}
	
	static function isOrderPage($page = ''){
		if(!$page)
			$page = UTools::getCurPageParam();

		return (UTools::getSiteSetting('order_page') && (Encoding::exStripos($page, UTools::getSiteSetting('order_page')) !== false));
	}
	
	static function addActionScript($result){
		$r = UTools::getStorage("scripts", "move_footer") . UTools::textOneLine($result);
		UTools::setStorage("scripts", 'move_footer', $r);
	}
	
	static function addEpilogScript($result){
		$r = UTools::getStorage("scripts", "epilog_check") . $result;
		UTools::setStorage("scripts", 'epilog_check', $r);
	}
	
	static function toDbLog($type, $description, $item = 1){
		$rType = "AGEC_".$type;
		
		\CEventLog::Add([
			"SEVERITY" => "DEBUG",
			"AUDIT_TYPE_ID" => $rType,
			"MODULE_ID" => self::MODULE_ID,
			"ITEM_ID" => $item,
			"DESCRIPTION" => $description,
		]);
	}
}