<?
use \Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
// echo '<pre>'; print_r($_REQUEST); echo '</pre>';

$action = $_REQUEST['action'];

if($action == 'orderview'){
	$orderID = intval($_REQUEST['id']);
	
	if($orderID){
		if(is_array($_SESSION["AG_ECOMMERCE"]["ORDERS_TO_SEND"]) && $_SESSION["AG_ECOMMERCE"]["ORDERS_TO_SEND"][$orderID]){
			unset($_SESSION["AG_ECOMMERCE"]["ORDERS_TO_SEND"][$orderID]);
		}
	}
}

if($action == 'get_delivery_name'){
	$result = [
		'delivery_name' => ''
	];
	
	$deliveryID = IntVal($_POST['deliveryID']);
	if($deliveryID && Loader::includeModule('sale')){
		$tmp = CSaleDelivery::GetByID($deliveryID);
		if(!$tmp){
			$tmp = \Bitrix\Sale\Delivery\Services\Manager::getById($deliveryID);
			if($tmp["PARENT_ID"]){
				$tmpParent = \Bitrix\Sale\Delivery\Services\Manager::getById($tmp["PARENT_ID"]);
				$result['delivery_name'] = $tmpParent["NAME"].' ('.$tmp["NAME"].')';
			}else{
				$result['delivery_name'] = $tmp["NAME"];
			}
		}else{
			$result['delivery_name'] = $tmp["NAME"];
		}
	}
		
	echo \Bitrix\Main\Web\Json::encode($result);
	die();
}

if($action == 'get_payment_name'){
	$result = [
		'payment_name' => ''
	];
	
	$paymentID = IntVal($_POST['paymentID']);
	if($paymentID && Loader::includeModule('sale')){
		$tmp = CSalePaySystem::GetByID($paymentID);
		$result["payment_name"] = $tmp["NAME"];
	}
		
	echo \Bitrix\Main\Web\Json::encode($result);
	die();
}