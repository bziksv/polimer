<?php
namespace Wbs24\Sbermmexport;

abstract class Price
{
    use PackagingRatio;

    protected $param;

    function __construct($param = [])
    {
        $this->setParam($param);

        $this->wrappers = new \stdClass();
        $this->wrappers->CIBlockElement = $objects['CIBlockElement'] ?? new CIBlockElement();
    }

    public function getPriceFromProps(array $product, array $commonFields): array
    {
        $productType = $product['TYPE'] ?: false;
        if (!$productType) return [];

        $productPriceProp = $commonFields['PRODUCT_PRICE_PROPERTY'] ?: false;
        $offerPriceProp = $commonFields['OFFER_PRICE_PROPERTY'] ?: false;

        $pricePropId = ($productType == 4 ? $offerPriceProp : $productPriceProp);
        if (!$pricePropId) return [];

        $priceValue = $product['PROPERTIES'][$pricePropId]['VALUE'] ?: false;
        $priceValue = intval($priceValue);
        if (!$priceValue) return [];

        $priceArray = [
            'RESULT_PRICE' => [
                'DISCOUNT_PRICE' => $priceValue,
                'BASE_PRICE' => $priceValue,
                'CURRENCY' => 'RUB',
            ],
        ];

        return $priceArray;
    }

    public function setParam($param)
    {
        foreach ($param as $name => $value) {
            $this->param[$name] = $value;
        }
    }

    public function getParam()
    {
        return $this->param;
    }

    public function getCustomOldPrice(
        array $product,
        int $oldPriceType,
        array $commonFields,
        array $prices
    ): ?float {
        $priceValue = null;
        $price = $prices['price'] ?? 0;
        $fullPrice = $prices['fullPrice'] ?? 0;

        if ($oldPriceType > 0) {
            $productId = $product['ID'] ?? false;
            if ($productId) $priceValue = $this->getProductPriceByPriceId($productId, $oldPriceType);
        } elseif ($oldPriceType == -1) {
            $priceValue = $this->getOldPriceFromProps($product, $commonFields);
        } elseif ($oldPriceType == -2) {
            $priceValue = $fullPrice;
        }

        if ($priceValue !== null) {
            $priceValue = $this->getPriceWithPackagingRatio($priceValue);
            $priceValue = (float) $this->getVerifiedOldPrice($price, $priceValue);
        }

        return $priceValue;
    }

    protected function getProductPriceByPriceId(int $productId, int $priceId): float
    {
        $price = 0;
        $result = $this->wrappers->CIBlockElement->GetList(
            [],
            ['ID' => $productId],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'PRICE_'.$priceId]
        );
        if ($fields = $result->Fetch()) {
            $price = $fields['PRICE_'.$priceId] ?? 0;
        }

        return (float) $price;
    }

    protected function getOldPriceFromProps(array $product, array $commonFields): float
    {
        $productType = $product['TYPE'] ?: false;
        if (!$productType) return 0;

        $productPriceProp = $commonFields['PRODUCT_OLD_PRICE_PROPERTY'] ?: false;
        $offerPriceProp = $commonFields['OFFER_OLD_PRICE_PROPERTY'] ?: false;

        $pricePropId = ($productType == 4 ? $offerPriceProp : $productPriceProp);
        if (!$pricePropId) return 0;

        $priceValue = $product['PROPERTIES'][$pricePropId]['VALUE'] ?: false;
        $priceValue = intval($priceValue);
        if (!$priceValue) return 0;

        return (float) $priceValue;
    }

    protected function getVerifiedOldPrice($price, $oldPrice)
    {
        if ($price >= $oldPrice) $oldPrice = 0;

        return $oldPrice;
    }

    abstract public function getPrice($minPrice, $basePrice);

    abstract public function getOldPrice($minPrice, $basePrice);
}
