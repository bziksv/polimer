<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if( !empty( $_GET["id"] ) )
    $id = (int)$_GET["id"];

if( !empty( $_GET["quantity"] ) )
    $quantity = (int)$_GET["quantity"];
else
    $quantity = 1;

if( !$id )
    die( 'Ошибка добавления товара в корзину' );

if ($quantity < 1)
    $quantity = 1;

CModule::IncludeModule( 'catalog' );
CModule::IncludeModule( 'sale' );

$stock = polimerGetCatalogStock($id);
$inBasket = polimerGetBasketProductQuantity($id);
$remaining = $stock - $inBasket;

if ($remaining < 1)
{
    if ($stock < 1)
        print 'Товара нет в наличии, принимаем заказ на товар по телефону';
    else
        print polimerStockShortageMessage('', $stock);
    return;
}

$quantity = min($quantity, (int)floor($remaining));
if ($quantity < 1)
{
    print polimerStockShortageMessage('', $stock);
    return;
}

if( Add2BasketByProductID( $id, $quantity ) )
    print 'Товар успешно добавлен в корзину';
else
    print 'Товара нет в наличии, принимаем заказ на товар по телефону';
