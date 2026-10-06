<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/json; charset=UTF-8');

CModule::IncludeModule('catalog');
CModule::IncludeModule('sale');

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;

$storeId = (int)($_POST['ID'] ?? 0);
$message = 'Часть товара приедет с другого склада, о сроке готовности сообщит менеджер';

$pickupStoreIds = [];
if (class_exists(StoreTable::class))
{
	$storeRes = StoreTable::getList([
		'filter' => [
			'=ACTIVE' => 'Y',
			'=ISSUING_CENTER' => 'Y',
			'!ID' => 21,
		],
		'select' => ['ID'],
	]);
	while ($row = $storeRes->fetch())
	{
		$pickupStoreIds[] = (int)$row['ID'];
	}
}
$pickupStoreIds = array_values(array_unique(array_filter($pickupStoreIds)));

$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
	\Bitrix\Sale\Fuser::getId(),
	SITE_ID
);

$productIds = [];
$basketRows = [];
foreach ($basket as $basketItem)
{
	if ($basketItem->isDelay())
	{
		continue;
	}
	$productId = (int)$basketItem->getProductId();
	if ($productId <= 0)
	{
		continue;
	}
	$productIds[$productId] = $productId;
	$basketRows[] = [
		'PRODUCT_ID' => $productId,
		'QTY' => (float)$basketItem->getQuantity(),
		'NAME' => (string)$basketItem->getField('NAME'),
	];
}

$amounts = [];
if ($productIds && $pickupStoreIds)
{
	$amountRes = StoreProductTable::getList([
		'filter' => [
			'=PRODUCT_ID' => array_values($productIds),
			'=STORE_ID' => $pickupStoreIds,
		],
		'select' => ['PRODUCT_ID', 'STORE_ID', 'AMOUNT'],
	]);
	while ($row = $amountRes->fetch())
	{
		$pid = (int)$row['PRODUCT_ID'];
		$sid = (int)$row['STORE_ID'];
		$amounts[$pid][$sid] = (float)$row['AMOUNT'];
	}
}

$warning = '';
$productIdsTransfer = [];
$needConfirm = false;

foreach ($basketRows as $row)
{
	$pid = $row['PRODUCT_ID'];
	$qty = $row['QTY'];
	$name = $row['NAME'];

	$catalogStock = polimerGetCatalogStock($pid);
	if ($qty > $catalogStock)
	{
		$warning .= htmlspecialchars(polimerStockShortageMessage($name, $catalogStock), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '<br>';
		continue;
	}

	if ($storeId <= 0)
	{
		continue;
	}

	$selectedAmount = (float)($amounts[$pid][$storeId] ?? 0);
	if ($selectedAmount + 0.0001 >= $qty)
	{
		continue;
	}

	$otherHas = false;
	foreach ($pickupStoreIds as $otherId)
	{
		if ($otherId === $storeId)
		{
			continue;
		}
		if ((float)($amounts[$pid][$otherId] ?? 0) + 0.0001 >= $qty)
		{
			$otherHas = true;
			break;
		}
	}

	if ($otherHas)
	{
		$needConfirm = true;
		$productIdsTransfer[] = $pid;
		continue;
	}

	$warning .= 'На складе недостаточно товара для "'.htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8').'"!<br>';
}

if ($needConfirm && $warning === '')
{
	$warning = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

echo json_encode([
	'needConfirm' => $needConfirm,
	'message' => $message,
	'productIds' => array_values(array_unique($productIdsTransfer)),
	'warning' => $warning,
], JSON_UNESCAPED_UNICODE);
