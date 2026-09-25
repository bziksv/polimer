<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

Loader::includeModule('catalog');
Loader::includeModule("sale");

class YaPayWidgetAjaxController extends \Bitrix\Main\Engine\Controller
{
    protected $siteId;
    protected $order;

    protected function init()
    {
        parent::init();
        // Guest storefront widget: keep public access, CSRF still applied by Engine defaults.
        $this->setActionConfig('getProductPrice', [
            '-prefilters' => [
                \Bitrix\Main\Engine\ActionFilter\Authentication::class,
            ],
        ]);
    }

    public function getProductPriceAction($product)
    {
        $productId = (int)$this->request->get('product');

        // Действие намеренно доступно гостю (витринный сценарий), поэтому цену отдаём
        // только по элементу, который гость и так может видеть: активному, в пределах
        // дат активности и доступному ему по правам инфоблока. Иначе анонимный перебор
        // ID раскрывает цены скрытых и неопубликованных товаров.
        if ($productId <= 0 || !$this->isProductVisible($productId)) {
            $this->addError(new \Bitrix\Main\Error('Product not found', 404));

            return null;
        }

        $quantity = 1;
        $groups = \Bitrix\Main\Engine\CurrentUser::get()->getUserGroups();
        $arPrice = \CCatalogProduct::GetOptimalPrice($productId, $quantity, $groups, 'N');
        if (!$arPrice || count($arPrice) <= 0) {
            if ($nearestQuantity = \CCatalogProduct::GetNearestQuantityPrice($productId, $quantity, $groups)) {
                $quantity = $nearestQuantity;
                $arPrice = \CCatalogProduct::GetOptimalPrice($productId, $quantity, $groups, 'N');
            }
        }

        $resultPrice = [];
        if (is_array($arPrice) && isset($arPrice['RESULT_PRICE']) && is_array($arPrice['RESULT_PRICE'])) {
            $resultPrice = [
                'DISCOUNT_PRICE' => isset($arPrice['RESULT_PRICE']['DISCOUNT_PRICE'])
                    ? (float)$arPrice['RESULT_PRICE']['DISCOUNT_PRICE']
                    : null,
                'BASE_PRICE' => isset($arPrice['RESULT_PRICE']['BASE_PRICE'])
                    ? (float)$arPrice['RESULT_PRICE']['BASE_PRICE']
                    : null,
            ];
        }

        return ['price' => $resultPrice];
    }

    /**
     * Товар виден текущему (в т.ч. анонимному) пользователю на витрине.
     *
     * CHECK_PERMISSIONS/MIN_PERMISSION заставляют ядро применить права инфоблока
     * к текущему пользователю, ACTIVE/ACTIVE_DATE отсекают скрытые и снятые с публикации.
     */
    protected function isProductVisible($productId)
    {
        if (!Loader::includeModule('iblock')) {
            return false;
        }

        $element = \CIBlockElement::GetList(
            [],
            [
                'ID' => (int)$productId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                'CHECK_PERMISSIONS' => 'Y',
                'MIN_PERMISSION' => 'R',
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        )->Fetch();

        return !empty($element);
    }
}
