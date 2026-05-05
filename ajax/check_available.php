<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule( 'catalog' );
CModule::IncludeModule( 'sale' );

use Bitrix\Catalog\StoreProductTable;

$storeId = $_POST["ID"] ?? 0;

$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
    \Bitrix\Sale\Fuser::getId(),
    SITE_ID
);

$warning = "";
foreach ($basket as $basketItem) {
    $productId = $basketItem->getProductId();
    $quantityInBasket = $basketItem->getQuantity();

    // Получаем остатки товара на складе
    $storeData = StoreProductTable::getList([
        'filter' => ['=STORE_ID' => $storeId, '=PRODUCT_ID' => $productId],
        'select' => ['AMOUNT']
    ])->fetch();

    if (!$storeData || $storeData['AMOUNT'] < $quantityInBasket) {
        $warning .= "На складе недостаточно товара для \"{$basketItem->getField('NAME')}\"!<br>";
    }
}

echo json_encode(['warning' => $warning]);
