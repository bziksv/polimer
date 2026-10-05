<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog") && isset($_GET["id"]) && !empty($_GET["id"]) && isset($_GET["quant"]) && !empty($_GET["quant"]))
{
    $basketId = (int)$_GET["id"];
    $quant = (float)$_GET["quant"];
    if ($basketId <= 0 || $quant <= 0)
    {
        echo "error";
        return;
    }

    $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), SITE_ID);
    $item = $basket->getItemById($basketId);
    if (!$item)
    {
        echo "error";
        return;
    }

    $stock = polimerGetCatalogStock((int)$item->getProductId());
    if ($quant > $stock)
        $quant = $stock;
    if ($quant < 1)
    {
        echo "error";
        return;
    }

    $arFields = array(
        "QUANTITY" => $quant
    );
    if(CSaleBasket::Update($basketId, $arFields))
    {
        echo "success";
    }
    else
    {
        echo "error";
    }
}
else
{
    echo "error";
}?>
