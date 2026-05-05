<?php
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (($_GET["key"] ?? "") !== "ym_stock_key_p0l1mer") { http_response_code(403); die("Forbidden"); }

Bitrix\Main\Loader::includeModule("catalog");
$res = Bitrix\Catalog\StoreProductTable::getList([
    "filter" => ["STORE_ID" => [6, 8]],
    "select" => ["PRODUCT_ID", "AMOUNT"],
])->fetchAll();
$totals = [];
foreach ($res as $r) {
    $id = (string)$r["PRODUCT_ID"];
    $totals[$id] = ($totals[$id] ?? 0) + (float)$r["AMOUNT"];
}
header("Content-Type: application/json");
echo json_encode($totals);
