<?
define("STOP_STATISTICS", true);
define("NO_AGENT_CHECK", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json; charset=utf-8');

$polimerBasketReply = static function (bool $ok, string $error = ''): void {
    echo json_encode(['status' => $ok ? 'success' : 'error', 'error' => $error], JSON_UNESCAPED_UNICODE);
    die();
};

if (!CModule::IncludeModule('sale') || !check_bitrix_sessid()) {
    $polimerBasketReply(false, 'bad_request');
}

$action = (string)($_REQUEST['action'] ?? '');
$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_REQUEST['ids'] ?? [])))));
if (!$ids || !in_array($action, ['check', 'uncheck', 'delete'], true)) {
    $polimerBasketReply(false, 'bad_request');
}

$fuserId = \Bitrix\Sale\Fuser::getId(true);
if (!$fuserId) {
    $polimerBasketReply(false, 'no_basket');
}

$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);
foreach ($ids as $id) {
    $item = $basket->getItemById($id);
    if (!$item) {
        continue;
    }
    if ($action === 'delete') {
        $item->delete();
    } else {
        $item->setField('DELAY', $action === 'uncheck' ? 'Y' : 'N');
    }
}

$result = $basket->save();
if (!$result->isSuccess()) {
    $polimerBasketReply(false, implode('; ', $result->getErrorMessages()));
}

\Bitrix\Sale\BasketComponentHelper::updateFUserBasket($fuserId, SITE_ID);
$polimerBasketReply(true);
