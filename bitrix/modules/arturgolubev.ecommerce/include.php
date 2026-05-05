<?
use \Bitrix\Main\Loader;

use \Bitrix\Sale;

use \Arturgolubev\Ecommerce\Tools as Tools;
use \Arturgolubev\Ecommerce\Unitools as UTools;

include 'autoload.php';

Class CArturgolubevEcommerce 
{
	const MODULE_ID = 'arturgolubev.ecommerce';
	const SESS = 'AG_ECOMMERCE';
	
	// f
	static function convertCurrencyBasket($proudctsArray){
		if(is_array($proudctsArray))
		{
			$convCurrency = UTools::getSiteSetting("convert_currency");
			if($convCurrency && Loader::includeModule('currency'))
			{
				foreach($proudctsArray as $k=>$basket){
					if($basket["CURRENCY"] == $convCurrency) continue;
					
					$proudctsArray[$k]["PRICE"] = round(CCurrencyRates::ConvertCurrency($basket["PRICE"], $basket["CURRENCY"], $convCurrency), 2);
					$proudctsArray[$k]["CURRENCY"] = $convCurrency;
				}
			}
		}
		
		return $proudctsArray;
	}
	
	static function _makeJsBasketString($arBasket){
		$s = '';
		
		if(is_array($arBasket) && !empty($arBasket)){
			foreach($arBasket as $arItem){
				if(!$arItem["QUANTITY"])
					$arItem["QUANTITY"] = 1;

				if($s) $s .= ', ';
				
				$s .= '{';
					$s .= '"id": "'.$arItem["ID"].'"';
					$s .= ', "name": "'.$arItem["NAME"].'"';
					$s .= ', "price": '.($arItem["PRICE"]*1).'';
					$s .= ', "category": "'.$arItem["SECTION_NAME"].'"';
					$s .= ', "brand": "'.$arItem["BRAND"].'"';
					$s .= ', "quantity": '.($arItem["QUANTITY"]*1);
					
					if(!empty($arItem["PROPS_VALUE"]))
						$s .= ', "variant": "'.implode('/', $arItem["PROPS_VALUE"]).'"';
					
					if(UTools::getBoolSiteSetting('ga_gbv'))
						$s .= ', "google_business_vertical": "retail"';
				$s .= '}';
			}
		}
		
		return $s;
	}
	
	static function saveUtmToCookie(){
		$utmList = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'];
		foreach($utmList as $utm){
			$cookieName = 'agec_'.$utm;
			
			if(isset($_REQUEST[$utm]) && $_REQUEST[$utm] && $_REQUEST[$utm] != $_COOKIE[$cookieName]){
				setcookie($cookieName, htmlspecialcharsbx($_REQUEST[$utm]), time()+60*60*24*30, "/");
			}
		}
	}
	
	// p
	static function ProtectEpilogStart(){
		if (!UTools::isAdminPage() && !Tools::checkDisable()){
			if(!isset($_SESSION[self::SESS])){
				$_SESSION[self::SESS] = [
					'FORMS' => [],
					'ORDERS_TO_SEND' => [],
					'ADD_TO_BASKET' => [],
					'PRE_DELETE_FROM_BASKET' => [],
					'DELETE_FROM_BASKET' => [],
				];
			}
			
			self::saveUtmToCookie();
			
			\CJSCore::Init();
			
			$regular = '<script>';
				$regular .= 'window.dataLayer = window.dataLayer || []; ';
				
				if(!UTools::getBoolSiteSetting("ya_off")){
					$cName = UTools::getSiteSetting('container', 'dataLayer');
					if($cName != 'dataLayer'){
						$regular .= 'window.'.$cName.' = window.'.$cName.' || []; ';
					}
				}
				
				if(UTools::getSiteSetting("fb_off") == 'N'){
					$regular .= 'window.fbqCCount = window.fbqCCount || 0; ';
					$regular .= 'if(typeof fbqChecker != "function"){function fbqChecker(a, b){window.fbqCCount = window.fbqCCount + 1; try {fbq("track", a, b);}catch(err){if(window.fbqCCount <= 20){setTimeout(function(){fbqChecker(a, b);}, 500);}}}};';
				}
				
				$regular .= 'if (typeof gtag != "function") {function gtag(){dataLayer.push(arguments);}}; ';
			$regular .= '</script>';
			
			UTools::addString($regular, true, \Bitrix\Main\Page\AssetLocation::BEFORE_CSS);

			$vers = filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/arturgolubev.ecommerce/main_init.js');
			UTools::addString('<script src="/bitrix/js/arturgolubev.ecommerce/main_init.js?'.$vers.'"></script>', true);
			
			// UTools::addJs("/bitrix/js/arturgolubev.ecommerce/main_init.js");
			
			$mode = UTools::getSetting('request_mode');
			
			$lockAutoRequests = 0;
			
			if(defined("LOCK_ECOMMERCE_REQUESTS")){
				$lockAutoRequests = 1;
			}
			
			if(Tools::isOrderPage() && !defined("ALLOW_ECOMMERCE_REQUESTS")){
				$lockAutoRequests = 1;
			}

			if(!$lockAutoRequests){
				if($mode == 'events'){
					UTools::addJs("/bitrix/js/arturgolubev.ecommerce/script_event_mode.js");
				}elseif($mode == 'noajax'){
					UTools::addJs("/bitrix/js/arturgolubev.ecommerce/script_noajax_mode.js");
				}else{
					UTools::addJs("/bitrix/js/arturgolubev.ecommerce/script_v2.js");
				}
			}
			
			if($lockAutoRequests || $mode != 'events'){
				if(!strstr(UTools::getCurPage(), 'setTheme.php')){
					self::getScriptBeginingCheckout();
					$action_scripts = Tools::incFooterComponent();
					Tools::addEpilogScript($action_scripts);
				}
			}
		}
	}
	
	static function onBufferContent(&$bufferContent){
		if (!UTools::isAdminPage() && !Tools::checkDisable()){
			if(!isset($_SESSION[self::SESS])){
				$_SESSION[self::SESS] = [
					'FORMS' => [],
					'ORDERS_TO_SEND' => [],
					'ADD_TO_BASKET' => [],
					'PRE_DELETE_FROM_BASKET' => [],
					'DELETE_FROM_BASKET' => [],
				];
			}
			
			$action_scripts = UTools::getStorage("scripts", "epilog_check");
			if($action_scripts){
				$bufferContent = UTools::addBodyScript($action_scripts, $bufferContent);
			}
		}
	}

	// session work
	static function _saveSessionOrder($orderId){
		$_SESSION[self::SESS]["ORDERS_TO_SEND"][$orderId] = $orderId;
		Tools::toDbLog('ORDER_ADD', 'Order add', $orderId);
		UTools::setSetting('work_purchase', time());
	}

	static function _saveSessionAdd($bItemID, $productInfo, $eventName){
		if(is_array($_SESSION[self::SESS]["ADD_TO_BASKET"][$bItemID])){
			$_SESSION[self::SESS]["ADD_TO_BASKET"][$bItemID]['QUANTITY'] += $productInfo['QUANTITY'];
		}else{
			$_SESSION[self::SESS]["ADD_TO_BASKET"][$bItemID] = $productInfo;
		}
		
		Tools::toDbLog('BASKET_ADD', 'Basket add. Event = '.$eventName, $bItemID);
		UTools::setSetting('work_addcart', time());
	}
	
	static function _saveSessionDelete($bItemID, $productInfo, $eventName){
		if(is_array($_SESSION[self::SESS]["DELETE_FROM_BASKET"][$bItemID])){
			$_SESSION[self::SESS]["DELETE_FROM_BASKET"][$bItemID]['QUANTITY'] += $productInfo['QUANTITY'];
		}else{
			$_SESSION[self::SESS]["DELETE_FROM_BASKET"][$bItemID] = $productInfo;
		}
		
		Tools::toDbLog('BASKET_DEL', 'Basket delete. Event = '.$eventName, $bItemID);
		UTools::setSetting('work_removecart', time());
	}

	
	// s
	static function onBasketBeforeDeleteD7($event){
		if(!Tools::checkDisable()){
			$basketID = $event->getParameter('id');
			
			$basketItem = self::getBasketPositionD7($basketID);
			if($basketItem){
				$productInfo = self::getBasketProductInfo($basketItem["ID"]); 
				$_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$basketItem["ID"]] = $productInfo;
			}
		}
	}
	static function onBasketAfterDeleteD7($event){
		if(!Tools::checkDisable()){
			$basketID = $event->getParameter('id');
			
			if(is_array($_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$basketID["ID"]])){
				self::_saveSessionDelete($basketID["ID"], $_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$basketID["ID"]], 'remove');
				unset($_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$basketID["ID"]]);
			}
		}
	}
	
	static function onBeforeOrderSaveD7($event){
		if(!Loader::includeModule(self::MODULE_ID) || defined("ADMIN_SECTION") || UTools::getBoolSetting('off_mode'))
			return 0;
		
		$order = $event->getParameter('ENTITY');
		if($order){
			if(!$order->getID()){
				$arSaveData = [];
				
				if(UTools::getBoolSetting('collect_utm')){
					$arSaveData['AGEC_UTM_SOURCE'] = $_COOKIE['agec_utm_source'];
					$arSaveData['AGEC_UTM_MEDIUM'] = $_COOKIE['agec_utm_medium'];
					$arSaveData['AGEC_UTM_CAMPAIGN'] = $_COOKIE['agec_utm_campaign'];
					$arSaveData['AGEC_UTM_CONTENT'] = $_COOKIE['agec_utm_content'];
					$arSaveData['AGEC_UTM_TERM'] = $_COOKIE['agec_utm_term'];
				}
				
				if(UTools::getBoolSetting('collect_client_id')){
					$arSaveData['AGEC_YM_CLIENTID'] = $_COOKIE['_ym_uid'];
					$arSaveData['AGEC_GA_CLIENTID'] = $_COOKIE['_ga'];
				}
				
				if(count($arSaveData)){
					$propertyCollection = $order->getPropertyCollection();
					foreach ($propertyCollection as $propertyItem) {
						if($arSaveData[$propertyItem->getField("CODE")]){
							$propertyItem->setField("VALUE", $arSaveData[$propertyItem->getField("CODE")]);
						}
					}
				}
			}
		}
	}

	static function onOrderAddD7($event){
		if(!Tools::checkDisable()){
			$order = $event->getParameter('ENTITY');
			
			if($event->getParameter('IS_NEW')){
				self::_saveSessionOrder($order->getId());
			}
		}
	}
	static function onBasketAddD7($event){
		if(!Tools::checkDisable()){
			$basketID = $event->getParameter('id');
			$basketItem = self::getBasketPositionD7($basketID);
			if($basketItem){
				$productInfo = self::getBasketProductInfo($basketItem["ID"], $basketItem); 
				self::_saveSessionAdd($basketItem['ID'], $productInfo, 'add');
			}
		}
	}

	static function onBasketUpdD7($event){
		if(!Tools::checkDisable()){
			$basketID = $event->getParameter('id');
			$fields = $event->getParameter('fields');
			
			if($fields["QUANTITY"]){
				$basketItem = self::getBasketPositionD7($basketID);
				if($basketItem){
					$productInfo = self::getBasketProductInfo($basketItem["ID"], $basketItem); 

					if($basketItem["QUANTITY"] < $fields["QUANTITY"]){
						$productInfo['QUANTITY'] = $fields["QUANTITY"] - $basketItem["QUANTITY"];
						self::_saveSessionAdd($basketItem['ID'], $productInfo, 'update');
					}elseif($basketItem["QUANTITY"] > $fields["QUANTITY"]){ 
						$productInfo['QUANTITY'] = $basketItem["QUANTITY"] - $fields["QUANTITY"];
						self::_saveSessionDelete($basketItem['ID'], $productInfo, 'update');
					}
				}
			}
		}
	}
		static function getBasketPositionD7($basketID){
			if(is_array($basketID) && $basketID["ID"]){
				$basketID = $basketID["ID"];
			}
			
			$basketID = IntVal($basketID);
			if($basketID){
				$dbItems = Sale\Internals\BasketTable::getList([
					'filter' => [
						'=ID' => $basketID
					],
					'select' => ['*'],
				]);
				if($basketItem = $dbItems->fetch()){
					if(!$basketItem["ORDER_ID"] && $basketItem["DELAY"] != 'Y'){
						return $basketItem;
					}
				}
			}
			
			return 0;
		}
	
	
	static function onFormResultAdd($formID, $resultID){
		if(!Tools::checkDisable()){
			$_SESSION[self::SESS]["FORMS"][$formID] = $formID;
			Tools::toDbLog('WEBFORM_CREATE', 'Add To List', $formID);
		}
	}
	
	// d
	static function getProductInfo($productId){
		if(Tools::checkDisable()) return false;
		if(!Loader::includeModule("iblock") || !$productId) return false;
		
		$item = [
			"ID" => $productId
		];
		
		$res = \CIBlockElement::GetList([], ["ID"=>$productId], false, ["nPageSize"=>1], ["ID", "NAME", "IBLOCK_ID", "SECTION_ID", "IBLOCK_SECTION_ID"]);
		while($ob = $res->GetNextElement())
		{
			$arFields = $ob->GetFields();
			$arFields["PROPERTIES"] = $ob->GetProperties();
			
			if($arFields["IBLOCK_SECTION_ID"])
				$intSectionID = $arFields["IBLOCK_SECTION_ID"];
			
			$item["NAME"] = $arFields["NAME"];
			$item["IBLOCK_ID"] = $arFields["IBLOCK_ID"];
			$item["IBLOCK_SECTION_ID"] =  $arFields["IBLOCK_SECTION_ID"];
			
			$brandPropertyID = UTools::getSetting('BRAND_PROPERTY_'.$arFields["IBLOCK_ID"]);
			if($brandPropertyID){
				foreach($arFields["PROPERTIES"] as $arProp){
					if($arProp["ID"] == $brandPropertyID){
						$tmp = \CIBlockFormatProperties::GetDisplayValue($arFields, $arProp, 'evt1');
						if($tmp["DISPLAY_VALUE"] && is_array($tmp["DISPLAY_VALUE"])){
							$item["BRAND"] = strip_tags(implode('/',$tmp["DISPLAY_VALUE"]));
						}elseif($tmp["DISPLAY_VALUE"]){
							$item["BRAND"] = strip_tags($tmp["DISPLAY_VALUE"]);
						}
					}
				}
			}
			
			if($intSectionID)
			{
				$nav = \CIBlockSection::GetNavChain(false, $intSectionID);
				while($pathFields = $nav->Fetch()){
					$item["SECTION_NAME"] .= ($item["SECTION_NAME"] != '') ? '/' : '';
					$item["SECTION_NAME"] .= $pathFields["NAME"];
				}
			}
		}
		
		foreach($item as $k=>$v){
			$item[$k] = UTools::textSafeMode($v, 1);
		}
		
		return $item;
	}
	
	static function getBasketProductInfo($basketId, $arFields = []){
		if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog") || !Loader::includeModule("sale")) return false;
		if(!$basketId) return false;
		
		if(empty($arFields)){
			$dbItems = Sale\Internals\BasketTable::getList([
				'filter' => [
					'=ID' => $basketId
				],
				'select' => ['*'],
			]);
			if($basketItem = $dbItems->fetch()){
				$arFields = $basketItem;
			}
		}
		
		$mxResult = CCatalogSku::GetProductInfo($arFields["PRODUCT_ID"]);
		if($mxResult)
		{
			$productInfo = self::getProductInfo($mxResult['ID']);
			$skuInfo = self::getProductInfo($arFields["PRODUCT_ID"]);

			$productInfo["ID"] = $skuInfo["ID"];
			$productInfo["NAME"] = $skuInfo["NAME"];
			$productInfo["BRAND"] = ($skuInfo["BRAND"]) ? $skuInfo["BRAND"] : $productInfo['BRAND'];
		}
		else
		{
			$productInfo = self::getProductInfo($arFields["PRODUCT_ID"]);
		}
		
		$productInfo["DELAY"] = $arFields["DELAY"];
		$productInfo["ORDER_ID"] = $arFields["ORDER_ID"];
		
		if(!$productInfo["NAME"])
			$productInfo["NAME"] = $arFields["NAME"];
		
		if(!$arFields["ORDER_ID"]){
			$arOptimalPrice = CCatalogProduct::GetOptimalPrice($arFields["PRODUCT_ID"]);
			$arFields["CURRENCY"] = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
			$arFields["PRICE"] = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
		}
		
		$productInfo["PRICE"] = $arFields["PRICE"];
		$productInfo["CURRENCY"] = $arFields["CURRENCY"];
		$productInfo["QUANTITY"] = ($arFields["QUANTITY"]) ? $arFields["QUANTITY"] : '1';
		
		if(empty($arFields["PROPS"]))
		{
			$dbProps = CSaleBasket::GetPropsList([], ["BASKET_ID" => $basketId]);
			while ($arPropsFields = $dbProps->Fetch())
			{
				$arFields["PROPS"][] = $arPropsFields;
			}
		}
		
		if(!empty($arFields["PROPS"]))
		{
			foreach($arFields["PROPS"] as $arPropsFields)
			{
				if($arPropsFields["CODE"] != 'CATALOG.XML_ID' && $arPropsFields["CODE"] != 'PRODUCT.XML_ID'){
					$productInfo["PROPS_VALUE"][] = UTools::textSafeMode($arPropsFields["VALUE"], 1);
				}
			}
		}
		
		return $productInfo;
	}
	
	// c
	static function checkReadyEvents(){
		if(Tools::checkDisable()) return false;
		if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog") || !Loader::includeModule("sale")) return false;
		
		$netmap = [
			"ADD_TO_BASKET" => "EC_ADD_FOR_",
			"DELETE_FROM_BASKET" => "EC_RM_FOR_",
			"ORDERS_TO_SEND" => "EC_SHOW_FOR_",
		];
		foreach($netmap as $sBase=>$cBase){
			foreach($_COOKIE as $cK=>$cv){
				if(strpos($cK, $cBase) !== false){
					$key = str_replace($cBase, '', $cK);
					if(is_array($_SESSION[self::SESS][$sBase]) && $_SESSION[self::SESS][$sBase][$key]){
						unset($_SESSION[self::SESS][$sBase][$key]);
					}
					setcookie($cK, "", time()-1000, "/");
				}
			}
		}
		
		$cacheScripts = UTools::getStorage("scripts", "move_footer");
		
		$actionScript = '';
		if(!$actionScript && isset($_SESSION[self::SESS]["ORDERS_TO_SEND"])) $actionScript .= self::getScriptForNewOrder($_SESSION[self::SESS]["ORDERS_TO_SEND"]);
		if(!$actionScript && isset($_SESSION[self::SESS]["ADD_TO_BASKET"])) $actionScript .= self::getScriptForAddProducts($_SESSION[self::SESS]["ADD_TO_BASKET"], 'add');
		if(!$actionScript && isset($_SESSION[self::SESS]["DELETE_FROM_BASKET"])) $actionScript .= self::getScriptForAddProducts($_SESSION[self::SESS]["DELETE_FROM_BASKET"], 'remove');
		
		$actionScript .= self::getScriptForms();
		
		return $cacheScripts.$actionScript;
	}
	
	// g
	static function getScriptForms(){
		$r = '';
		
		if(isset($_SESSION[self::SESS]["FORMS"])){
			$arForms = $_SESSION[self::SESS]["FORMS"];
			
			if(is_array($arForms) && count($arForms)){
				$yaCounter = IntVal(UTools::getSiteSetting("yandex_goal_counter_id"));

				foreach($arForms as $k=>$v){
					if(UTools::getSiteSetting('forms_ym')){
						$r .= 'agec_ym_goal("'.$yaCounter.'", "SEND_FORM_'.$k.'", "'.UTools::getBoolSetting('debug').'");';					
					}
					
					if(UTools::getSiteSetting('forms_ga')){
						$r .= 'gtag("event", "send_form_'.$k.'", {"event_category": "forms"});';
						$r .= Tools::addDebug('console.log("GA send form '.$k.'");');
					}
				}
				
				unset($_SESSION[self::SESS]["FORMS"][$k]);
			}
		}
		
		return $r;
	}
	
	static function getScriptForNewOrder($arOrders){
		if(empty($arOrders)) return false;
		
		Loader::includeModule('sale');

		foreach($arOrders as $id){
			$orderId = $id;
			break;
		}
		
		$cookieName = "EC_SHOW_FOR_".$orderId;
		
		$cookie = ''; $yandex = ''; $google = ''; $fb = '';
		
		$settings = Tools::getMainSettings();

		$order = [
			"ID" => $orderId,
		];
		
		$orderLoad = Sale\Order::load($order["ID"]);
		if($orderLoad){
			$order["FIELDS"] = $orderLoad->getFields()->getValues();
			$order["FIELDS"]['DATE_INSERT'] = $order["FIELDS"]['DATE_INSERT']->toString();
			$order["FIELDS"]['DATE_UPDATE'] = $order["FIELDS"]['DATE_UPDATE']->toString();
			$order["FIELDS"]['DATE_STATUS'] = $order["FIELDS"]['DATE_STATUS']->toString();
			$order["FIELDS"]['BASKET_DISCOUNT_COUPON'] = '';
			
			$couponList = \Bitrix\Sale\Internals\OrderCouponsTable::getList(array(
				'select' => ['COUPON'],
				'filter' => [
					'ORDER_ID' => $order["ID"]
				]
			));
			if ($coupon = $couponList->fetch()){
				$order["FIELDS"]['BASKET_DISCOUNT_COUPON'] = $coupon['COUPON'];
			}
			
			$shipmentCollection = $orderLoad->getShipmentCollection();
			foreach($shipmentCollection as $shipment){
				if($shipment->isSystem()) continue;
				$order["FIELDS"]['DELIVERY_NAME'] = $shipment->getDeliveryName();
			}

			$collection = $orderLoad->getPaymentCollection();
			foreach ($collection as $payment){				
				$order["FIELDS"]['PAY_SYSTEM_NAME'] = $payment->getPaySystem()->getField('NAME');
			}
		}

		/*
		$rsSales = CSaleOrder::GetList([], ["ID" => $order["ID"]], false, false, ["BASKET_DISCOUNT_COUPON", "*"]);
		if($arSales = $rsSales->Fetch()){
			$order["FIELDS"] = $arSales;
		}
		*/
		
		if($order["FIELDS"]){
			$orderNum = UTools::textSafeMode($order["FIELDS"][$settings['main']['order_id_field']], 1);
			$dbItems = Sale\Internals\BasketTable::getList([
				'filter' => [
					'=ORDER_ID' => $order["FIELDS"]["ID"]
				],
				'select' => ['*'],
			]);
			while($basketItem = $dbItems->fetch()){
				$productInfo = self::getBasketProductInfo($basketItem["ID"], $basketItem); 
				$order["ORDER_BASKET"][] = $productInfo;
			}
			
			$order["ORDER_BASKET"] = self::convertCurrencyBasket($order["ORDER_BASKET"]);
			foreach($order["ORDER_BASKET"] as $basket){
				$currency = $basket["CURRENCY"]; break;
			}
			
			if($order["FIELDS"]["CURRENCY"] != $currency)
			{
				$order["FIELDS"]["PRICE"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["PRICE"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				$order["FIELDS"]["TAX_VALUE"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["TAX_VALUE"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				$order["FIELDS"]["PRICE_DELIVERY"] = round(CCurrencyRates::ConvertCurrency($order["FIELDS"]["PRICE_DELIVERY"], $order["FIELDS"]["CURRENCY"], $currency), 2);
				
				$order["FIELDS"]["CURRENCY"] = $currency;
			}

			$productsJsString = self::_makeJsBasketString($order["ORDER_BASKET"]);
			
			$cookie .= '
				document.cookie = "'.$cookieName.'=Y; path=/";
				var xhr = new XMLHttpRequest();
				xhr.open("GET", "/bitrix/tools/arturgolubev.ecommerce/ajax.php?action=orderview&id='.$orderId.'");
				xhr.send();
			';
			$cookie .= Tools::addDebug('console.log("setCookie: " + "'.$cookieName.'=Y; path=/");');
			
				
			$baseLayer = 'var agec_order_base = {';
				$baseLayer .= '"ecommerce": {';
					$baseLayer .= '"currencyCode": "'.$order["FIELDS"]["CURRENCY"].'",';
					$baseLayer .= '"purchase": {';
						$baseLayer .= '"actionField": {';
							$baseLayer .= '"id" : "'.$orderNum.'",';
							$baseLayer .= '"revenue" : "'.($order["FIELDS"]["PRICE"]*1).'",';
							$baseLayer .= '"coupon" : "'.($order["FIELDS"]["BASKET_DISCOUNT_COUPON"]).'",'; 
							
							$baseLayer .= "'tax' : '".($order["FIELDS"]["TAX_VALUE"]*1)."',";
							$baseLayer .= "'shipping' : '".($order["FIELDS"]["PRICE_DELIVERY"]*1)."',";
							
							if($settings['yandex']['params']['target_order'])
								$baseLayer .= '"goal_id" : "'.$settings['yandex']['params']['target_order'].'",'; 
						
						$baseLayer .= '},';
						$baseLayer .= '"products": ['.$productsJsString.']';
					$baseLayer .= '}';
				$baseLayer .= '}';
			$baseLayer .= '};';
			
			Tools::toDbLog('ORDER_SEND', $baseLayer, $orderId);
			// Tools::toDbLog('ORDER_SEND_SESS', \Bitrix\Main\Web\Json::encode($_SESSION), $orderId);
			// Tools::toDbLog('ORDER_SEND_COOK', \Bitrix\Main\Web\Json::encode($_COOKIE), $orderId);
			
			if($settings['yandex']['enable']){
				$yandex .= 'window.'.$settings['main']['container'].'.push(agec_order_base);';
				$yandex .= Tools::addDebug('console.log("EC order: yandex - purchase", agec_order_base.ecommerce);');

				if($settings['yandex']['params']['goal_purchase']){					
					$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_PURCHASE", "'.UTools::getBoolSetting('debug').'");';
				}
			}
			
			if($settings['google']['enable']){
				if($settings['main']['container'] != 'dataLayer' || !$settings['yandex']['enable']){
					$google .= 'window.dataLayer.push(agec_order_base);';
				}

				if($settings['google']['params']['payship_type'] == 'order'){
					$google .= 'var agec_add_shipping_info = {"shipping_tier": "'.$order["FIELDS"]["DELIVERY_NAME"].'", "items": ['.$productsJsString.']};';
					$google .= 'gtag("event", "add_shipping_info", agec_add_shipping_info);';
					$google .= Tools::addDebug('console.log("EC order: google - add_shipping_info", agec_add_shipping_info);');

					$google .= 'var agec_add_payment_info = {"payment_type": "'.$order["FIELDS"]["PAY_SYSTEM_NAME"].'", "items": ['.$productsJsString.']};';
					$google .= 'gtag("event", "add_payment_info", agec_add_payment_info);';
					$google .= Tools::addDebug('console.log("EC order: google - add_payment_info", agec_add_payment_info);');
				}
				
				$google .= 'var agec_order_gtag = {';
					$google .= "'id' : '".$orderNum."',";
					$google .= "'transaction_id' : '".$orderNum."',";
					$google .= "'affiliation' : '".SITE_SERVER_NAME."',";
					$google .= "'value' : '".($order["FIELDS"]["PRICE"]*1)."',";
					$google .= "'tax' : '".($order["FIELDS"]["TAX_VALUE"]*1)."',";
					$google .= "'shipping' : '".($order["FIELDS"]["PRICE_DELIVERY"]*1)."',";
					$google .= "'coupon' : '".($order["FIELDS"]["BASKET_DISCOUNT_COUPON"])."',"; 
					$google .= "'currency' : '".$order["FIELDS"]["CURRENCY"]."',"; 
					$google .= '"items": ['.$productsJsString.']';
				$google .= '};';
				
				$google .= 'gtag("event", "purchase", agec_order_gtag);';
				$google .= Tools::addDebug('console.log("EC order: google - purchase", agec_order_gtag);');
			}
			
			if($settings['facebook']['enable']){
				$ids = [];
				$value = 0;
				$productsFb = '';
				
				foreach($order["ORDER_BASKET"] as $item){
					$ids[] = $item["ID"];
					$currency = $item["CURRENCY"];
					$value += $item["PRICE"]*$item["QUANTITY"];
					
					if($productsFb) $productsFb .= ', ';
					$productsFb .= '{';
						$productsFb .= '"id": "'.$item["ID"].'",';
						$productsFb .= '"quantity": "'.$item["QUANTITY"].'",';
					$productsFb .= '}';
				}
				
				$fb .= 'var agec_order_fb = {';
					$fb .= '"content_ids": '.'['.'"'.implode('","', $ids).'"'.']'.',';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$currency.'",';
					$fb .= '"value": "'.$value.'",';
					$fb .= '"contents": ['.$productsFb.'],';
					$fb .= '"num_items": "'.count($ids).'",';
				$fb .= '};';
				
				$fb .= 'fbqChecker("Purchase", agec_order_fb);';
				$fb .= Tools::addDebug('console.log("EC order: facebook - Purchase", agec_order_fb);');
			}
			
			// $fullScript = UTools::textOneLine($baseLayer.$yandex.$google.$fb);
			$fullScript = UTools::textOneLine($cookie.$baseLayer.$yandex.$google.$fb);
			
			return $fullScript;
		}
	}
	
	static function getDetailCode($productId, $offersProps = [], $params = []){
		if(Tools::checkDisable() || !$productId) return false;
		if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog")) return false;

		UTools::setSetting('work_product', time());
		
		$yandex = ''; $google = ''; $fb = '';

		$settings = Tools::getMainSettings();

		$arData = [
			'PRODUCT' => self::getProductInfo($productId)
		];		
		
		$res = CCatalogSKU::getOffersList($productId);
		$arOfferIDs = $res[$productId];
		if(!empty($arOfferIDs))
		{
			global $agecSkuFilter;
			if(is_array($agecSkuFilter) && count($agecSkuFilter)){
				$res = CCatalogSKU::getOffersList($productId, $arData["PRODUCT"]["IBLOCK_ID"], $agecSkuFilter);
				$arOfferIDs = $res[$productId];
			}
			
			if($params['SELECTED_OFFER'] && isset($arOfferIDs[$params['SELECTED_OFFER']])){
				$arOfferIDs = [$params['SELECTED_OFFER'] => $arOfferIDs[$params['SELECTED_OFFER']]];
			}
			
			if(!empty($arOfferIDs))
			{
				$firstElement = current($arOfferIDs);
				
				$arSelect = ["ID", "NAME"];
				
				foreach($offersProps as $prop)
					$arSelect[] = "PROPERTY_".$prop;;
				
				$arFilter = ["ID"=>array_keys($arOfferIDs), "IBLOCK_ID"=>$firstElement["IBLOCK_ID"], "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y"];
				$res = CIBlockElement::GetList([], $arFilter, false, ["nPageSize"=>25], $arSelect);
				while($ob = $res->GetNextElement()){
					$arFields = $ob->GetFields();
					$offerProductInfo = self::getProductInfo($arFields['ID']);

					$arFields["PROPS_VALUE"] = [];
					foreach($offersProps as $prop){
						if($arFields["PROPERTY_".$prop."_VALUE"]){
							$arFields["PROPS_VALUE"][] = UTools::textSafeMode($arFields["PROPERTY_".$prop."_VALUE"], 1);
						}
					}
					
					$arOptimalPrice = CCatalogProduct::GetOptimalPrice($arFields["ID"]);
					if(!empty($arOptimalPrice))
					{
						$tp = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
						$tc = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
						
						$arData["ITEMS"][$arFields["ID"]] = [
							"ID" => $arFields["ID"],
							"NAME" => UTools::textSafeMode($arFields["NAME"], 1),
							"SECTION_NAME" => $arData["PRODUCT"]["SECTION_NAME"],
							"BRAND" => ($offerProductInfo["BRAND"]) ? $offerProductInfo["BRAND"] : $arData["PRODUCT"]["BRAND"],
							"CURRENCY" => $tc,
							"PRICE" => $tp,
							"PROPS_VALUE" => $arFields["PROPS_VALUE"],
						];
						
						if($params['OFFER_SELECT_FIRST']){
							break;
						}
					}
				}
			}
		}
		else
		{
			$arOptimalPrice = CCatalogProduct::GetOptimalPrice($productId);
			if(!empty($arOptimalPrice))
			{
				$tp = ($arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"]) ? $arOptimalPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] : $arOptimalPrice["PRICE"]["PRICE"];
				$tc = ($arOptimalPrice["RESULT_PRICE"]["CURRENCY"]) ? $arOptimalPrice["RESULT_PRICE"]["CURRENCY"] : $arOptimalPrice["PRICE"]["CURRENCY"];
				
				$arData["ITEMS"][$productId] = [
					"ID" => $productId,
					"NAME" => $arData["PRODUCT"]["NAME"],
					"SECTION_NAME" => $arData["PRODUCT"]["SECTION_NAME"],
					"BRAND" => $arData["PRODUCT"]["BRAND"],
					"CURRENCY" => $tc,
					"PRICE" => $tp,
				];
			}
		}
		
		if(!empty($arData["ITEMS"]))
		{
			$arData["ITEMS"] = self::convertCurrencyBasket($arData["ITEMS"]);
			
			foreach($arData["ITEMS"] as $basket){
				$currency = $basket["CURRENCY"]; break;
			}
			
			$productsJsString = self::_makeJsBasketString($arData["ITEMS"]);
			
			$baseLayer = 'var agec_detail_base = {';
				$baseLayer .= '"ecommerce": {';
					$baseLayer .= '"currencyCode": "'.$currency.'",';
					$baseLayer .= '"detail": {"products": ['.$productsJsString.']}';
				$baseLayer .= '}';
			$baseLayer .= '};'."\n";
			
			if($settings['yandex']['enable']){
				$yandex .= 'window.'.$settings['main']['container'].'.push(agec_detail_base);';
				$yandex .= Tools::addDebug('console.log("EC detail: yandex - detail", agec_detail_base.ecommerce);');
				
				if($settings['yandex']['params']['goal_product']){				
					$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_PRODUCT", "'.UTools::getBoolSetting('debug').'");';
				}
			}
			
			if($settings['google']['enable']){
				if($settings['main']['container'] != 'dataLayer' || !$settings['yandex']['enable']){
					$google .= 'window.dataLayer.push(agec_detail_base);';
				}
				
				$google .= "var agec_detail_gtag = {'items': [".$productsJsString."]};";
				$google .= "gtag('event', 'view_item', agec_detail_gtag);";
				$google .= Tools::addDebug('console.log("EC detail: google - view_item", agec_detail_gtag);');
			}
			
			if($settings['facebook']['enable']){
				$cItem = reset($arData["ITEMS"]);
				
				$fb .= 'var agec_detail_fb = {';
					$fb .= '"content_ids": ["'.implode('","', array_keys($arData["ITEMS"])).'"],';
					$fb .= '"content_category": "'.$cItem["SECTION_NAME"].'",';
					$fb .= '"content_name": "'.$cItem["NAME"].'",';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$cItem["CURRENCY"].'",';
					$fb .= '"value": "'.$cItem["PRICE"].'",';
				$fb .= '};'."\n";
				
				$fb .= 'fbqChecker("ViewContent", agec_detail_fb);';
				$fb .= Tools::addDebug('console.log("EC detail: facebook - ViewContent", agec_detail_fb);');
			}

			$result = "\n".$baseLayer.$yandex.$google.$fb."\n";
		}
		
		return $result;
	}
	
	static function getScriptForAddProducts($proudctsArray, $type){
		if(empty($proudctsArray)) return false;
		
		foreach($proudctsArray as $k=>$v){
			$reGetInfo = self::getBasketProductInfo($k);
			if($reGetInfo["ORDER_ID"]){
				unset($proudctsArray[$k]);
			}
		}
		
		if(empty($proudctsArray)) return false;
		
		$cookie = ''; $yandex = ''; $google = ''; $fb = '';

		$settings = Tools::getMainSettings();
		
		foreach($proudctsArray as $key=>$val){
			$cookieName = ($type == 'add') ? "EC_ADD_FOR_".$key : "EC_RM_FOR_".$key;

			$cookie .= 'document.cookie = "'.$cookieName.'=Y; path=/"; ';
			$cookie .= Tools::addDebug('console.log("setCookie: " + "'.$cookieName.'=Y; path=/");');
		}
		
		$proudctsArray = self::convertCurrencyBasket($proudctsArray);
		
		foreach($proudctsArray as $basket){
			$currency = $basket["CURRENCY"]; break;
		}
		
		$productsJsString = self::_makeJsBasketString($proudctsArray);
		
		$baseLayer = 'var agec_add_base = {';
			$baseLayer .= '"ecommerce": {';
				$baseLayer .= '"currencyCode": "'.$currency.'",';
				$baseLayer .= '"'.$type.'": {"products": ['.$productsJsString.']}';
			$baseLayer .= '}';
		$baseLayer .= '}; ';
		
		if($settings['yandex']['enable']){
			$yandex .= 'window.'.$settings['main']['container'].'.push(agec_add_base);';
			$yandex .= Tools::addDebug('console.log("EC basket: yandex - '.$type.'", agec_add_base.ecommerce);');
			
			if($type == 'add'){
				if($settings['yandex']['params']['goal_add']){
					$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_ADD", "'.UTools::getBoolSetting('debug').'");';
				}
			}else{
				if($settings['yandex']['params']['goal_remove']){
					$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_REMOVE", "'.UTools::getBoolSetting('debug').'");';
				}
			}
		}
		
		if($settings['google']['enable']){
			if($settings['main']['container'] != 'dataLayer' || !$settings['yandex']['enable']){
				$google .= 'window.dataLayer.push(agec_add_base);';
			}
			
			$tmp = ($type == 'add') ? 'add_to_cart' : 'remove_from_cart';
			
			$google .= "var agec_add_gtag = {'items': [".$productsJsString."]};";
			$google .= "gtag('event', '".$tmp."', agec_add_gtag);";
			$google .= Tools::addDebug('console.log("EC basket: google - '.$tmp.'", agec_add_gtag);');
		}
		
		if($settings['facebook']['enable'] && $type == 'add'){
			foreach($proudctsArray as $arItem){
				$fb .= 'var agec_add_fb = {';
					$fb .= '"content_name": "'.$arItem["NAME"].'",';
					$fb .= '"content_ids": ["'.$arItem["ID"].'"],';
					$fb .= '"content_type": "product",';
					$fb .= '"currency": "'.$arItem["CURRENCY"].'",';
					$fb .= '"value": "'.$arItem["PRICE"].'",'; 
					$fb .= '"contents": [{"id": "'.$arItem["ID"].'", "quantity": "'.$arItem["QUANTITY"].'"}],';
				$fb .= '};';
				
				$fb .= 'fbqChecker("AddToCart", agec_add_fb);';
				$fb .= Tools::addDebug('console.log("EC basket: facebook - AddToCart", agec_add_fb);');
			}
		}
		
		$fullScript = UTools::textOneLine($cookie.$baseLayer.$yandex.$google.$fb);
		
		return $fullScript;
	}
	
	static function getScriptBeginingCheckout($page = ''){
		$yandex = ''; $google = ''; $fb = '';
		
		$settings = Tools::getMainSettings();

		// order page
		if(Tools::isOrderPage($page)){
			if(!Loader::includeModule("iblock") || !Loader::includeModule("catalog") || !Loader::includeModule("sale")) return false;
			
			UTools::setSetting('work_order', time());
			
			$basket = self::_getActualBasket();
			if(count($basket)){
				$currency = $basket[0]["CURRENCY"];
				$productsJsString = self::_makeJsBasketString($basket);
				
				if($settings['yandex']['enable']){
					if($settings['yandex']['params']['goal_order']){
						$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_ORDER", "'.UTools::getBoolSetting('debug').'");';
					}
				}
				
				if($settings['google']['enable']){
					$google .= 'var agec_checkout_gtm = {';
						$google .= '"ecommerce": {';
							$google .= '"event": "begin_checkout",';
							$google .= '"currencyCode": "'.$currency.'",';
							$google .= '"checkout": {"products": ['.$productsJsString.']}';
						$google .= '}';
					$google .= '}; ';
					$google .= 'window.dataLayer.push(agec_checkout_gtm); ';
					
					$google .= 'var agec_checkout_gtag = {"items": ['.$productsJsString.']}; ';
					
					$google .= 'gtag("event", "begin_checkout", agec_checkout_gtag);';
					$google .= Tools::addDebug('console.log("EC cart: google - begin_checkout", agec_checkout_gtag);');
				}
				
				if($settings['facebook']['enable']){
					$fb_ids = [];
					$fb_value = 0;
					$fb_contents = '';
					
					foreach($basket as $item){
						$fb_ids[] = $item["ID"];
						$fb_value += $item["PRICE"]*$item["QUANTITY"];
						
						if($fb_contents) $fb_contents .= ',';
						$fb_contents .= '{"id": "'.$item["ID"].'", "quantity": "'.$item["QUANTITY"].'"}';
					}
					
					$fb .= 'var agec_checkout_fb = {';
						$fb .= '"content_ids": ["'.implode('","', $fb_ids).'"],';
						$fb .= '"currency": "'.$currency.'",';
						$fb .= '"content_type": "product",';
						$fb .= '"value": "'.$fb_value.'",';
						$fb .= '"num_items": "'.count($fb_ids).'",';
						$fb .= '"contents": ['.$fb_contents.'],';
					$fb .= '};';
					
					$fb .= Tools::addDebug('console.log("EC cart: facebook - InitiateCheckout", agec_checkout_fb);');				
					$fb .= 'fbqChecker("InitiateCheckout", agec_checkout_fb);';
				}
				
				if($settings['google']['enable'] && !$settings['google']['params']['payship_type']){
					$google .= 'BX.ready(function(){
						agec_init_ordersteps(['.$productsJsString.']);
					});';

					$google .= 'BX.addCustomEvent("onAjaxSuccess", function(){
						agec_init_ordersteps(['.$productsJsString.']);
					});';
				}
			}
		}
		
		// cart page
		if(Tools::isCartPage($page)){
			if($settings['google']['enable']){
				$basket = self::_getActualBasket();
				if(count($basket)){
					$currency = $basket[0]["CURRENCY"];
					$productsJsString = self::_makeJsBasketString($basket);
					
					$google .= 'var agec_cart_gtm = {"ecommerce": {';
						$google .= '"event": "view_cart",';
						$google .= '"currencyCode": "'.$currency.'",';
						$google .= '"view_cart": {"products": ['.$productsJsString.']}';
					$google .= '}}; ';
					$google .= 'window.dataLayer.push(agec_cart_gtm); ';
					
					$google .= 'var agec_cart_gtag = {"items": ['.$productsJsString.']}; ';
					$google .= 'gtag("event", "view_cart", agec_cart_gtag);';
					$google .= Tools::addDebug('console.log("EC cart: google - view_cart", agec_cart_gtag);');
				}
			}

			if($settings['yandex']['enable']){
				if($settings['yandex']['params']['goal_cart']){
					$yandex .= 'agec_ym_goal("'.$settings['yandex']['params']['counter_id'].'", "AGE_CART", "'.UTools::getBoolSetting('debug').'");';
				}
			}
		}
		
		// search query
		if($settings['google']['enable']){
			$parse = parse_url($page);
			if($parse['query']){
				parse_str($parse['query'], $arQuery);
				if($arQuery['q']){
					$google .= 'var agec_search_gtm = {"ecommerce": {';
						$google .= '"event": "search",';
						$google .= '"search": {"search_term": "'.$arQuery['q'].'"}';
					$google .= '}}; ';
					$google .= 'window.dataLayer.push(agec_search_gtm); ';
					
					$google .= 'var agec_search_gtag = {"search_term": "'.$arQuery['q'].'"}; ';
					$google .= 'gtag("event", "search", agec_search_gtag);';
					$google .= Tools::addDebug('console.log("EC search: google - search", agec_search_gtag);');
				}
			}
		}
		
		$full = $yandex.$google.$fb;
		
		if($full){
			Tools::addActionScript($full);
		}
	}
		static function _getActualBasket(){
			$result = [];
			
			if(Loader::includeModule("sale")){
				$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
				foreach($basket as $basketItem){
					$arItems = $basketItem->getFieldValues();
					$productInfo = self::getBasketProductInfo($arItems["ID"], $arItems); 
					$result[] = $productInfo;
				}
			}
			
			if(count($result)){
				$result = self::convertCurrencyBasket($result);
			}
			
			return $result;
		}
	
	
	// old
	static function onBasketAdd($basketID, $arFields){
		if(!Tools::checkDisable() && !$arFields["ORDER_ID"] && $arFields["DELAY"] != 'Y'){
			$productInfo = self::getBasketProductInfo($basketID, $arFields); 
			self::_saveSessionAdd($basketID, $productInfo, 'old api add');
		}
	}
	static function onOrderAdd($orderId, $arFields){
		if(!Tools::checkDisable()){
			self::_saveSessionOrder($orderId);
		}
	}
	static function onBasketDelete($ID){
		if(!Tools::checkDisable()){
			$productInfo = self::getBasketProductInfo($ID); 
			if($productInfo["DELAY"] != 'Y'){
				$_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$ID] = $productInfo;
			}
		}
	}
	static function onBasketDeleteAfter($ID){
		if(!Tools::checkDisable()){
			if(is_array($_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$ID])){
				self::_saveSessionDelete($ID, $_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$ID], 'old api delete');
				unset($_SESSION[self::SESS]["PRE_DELETE_FROM_BASKET"][$ID]);
			}
		}
	}
}
?>