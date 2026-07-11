<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");?>
<?if(CModule::IncludeModule("sale") && isset($_GET["id"]) && $_GET["id"] !== "" && isset($_GET["delay"]))
{
    $id = (int)$_GET["id"];
    $delay = $_GET["delay"] === "Y" ? "Y" : "N";

    if($id > 0 && CSaleBasket::Update($id, array("DELAY" => $delay)))
    {
        if (class_exists(\Bitrix\Sale\BasketComponentHelper::class)) {
            $fuserId = CSaleBasket::GetBasketUserID(true);
            if ($fuserId) {
                \Bitrix\Sale\BasketComponentHelper::updateFUserBasket($fuserId);
            }
        }
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
