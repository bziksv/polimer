<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
$module_id = 'yandex.market';

$arStatuses = array();
if (\Bitrix\Main\Loader::includeModule('sale')) {
    $statusResult = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
        'filter' => array('LID' => LANGUAGE_ID),
        'select' => array('STATUS_ID', 'NAME')
    ));
    while ($status = $statusResult->fetch()) {
        $arStatuses[$status['STATUS_ID']] = '[' . $status['STATUS_ID'] . '] ' . $status['NAME'];
    }
}

// Получаем список инфоблоков каталога: все каталоги (с ТП и без)
$arSkuIBlocks = array();
$arProductIBlocks = array(); // инфоблоки товаров и ТП для выбора "по какому искать"
if (\Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Main\Loader::includeModule('iblock')) {
    $catalogIterator = \Bitrix\Catalog\CatalogIblockTable::getList(array(
        'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID')
    ));
    while ($catalog = $catalogIterator->fetch()) {
        $iblockId = (int)$catalog['IBLOCK_ID'];
        $productIblockId = (int)$catalog['PRODUCT_IBLOCK_ID'];
        $iblock = \CIBlock::GetByID($iblockId)->Fetch();
        if ($iblock) {
            $label = '[' . $iblock['IBLOCK_TYPE_ID'] . '] ' . $iblock['NAME'];
            if ($productIblockId > 0) {
                // Каталог с ТП: инфоблок — ТП, отдельно инфоблок товаров
                $arSkuIBlocks[$iblockId] = $label;
                $arProductIBlocks[$iblockId] = $label;
            } else {
                // Каталог без ТП: один инфоблок — и каталог, и товары
                $arProductIBlocks[$iblockId] = $label;
            }
        }
        if ($productIblockId > 0 && !isset($arProductIBlocks[$productIblockId])) {
            $pIblock = \CIBlock::GetByID($productIblockId)->Fetch();
            if ($pIblock) {
                $arProductIBlocks[$productIblockId] = '[' . $pIblock['IBLOCK_TYPE_ID'] . '] ' . $pIblock['NAME'];
            }
        }
    }
}
$arProductIdFields = array(
    'ID' => 'ID',
    'XML_ID' => 'XML_ID',
    'CODE' => 'CODE',
    'PROPERTY' => 'Свойство инфоблока',
);

$arPriceTypes = array('' => '— Базовая (первая доступная) —');
if (\Bitrix\Main\Loader::includeModule('catalog')) {
    $priceTypeResult = \CCatalogGroup::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], []);
    while ($priceType = $priceTypeResult->Fetch()) {
        $arPriceTypes[(string)$priceType['ID']] = '[' . $priceType['ID'] . '] ' . $priceType['NAME'];
    }
}

$productsPricesOAuthUrl = 'https://oauth.yandex.ru/authorize?response_type=token&client_id=e32ecb9cad1046acab082dd45b2e276c';
$productsPricesPendingCount = 0;
if (\Bitrix\Main\Loader::includeModule('yandex.market') && class_exists('\Yandex\Market\Products\Prices\Repository')) {
    try {
        $productsPricesPendingCount = (int)\Yandex\Market\Products\Prices\Repository::countPending();
    } catch (\Throwable $e) {
        $productsPricesPendingCount = 0;
    }
}

// Список свойств выбранного инфоблока товаров для поля «Код свойства»
$arProductIdProperties = array('' => '— Не выбрано —');
$productIblockIdForProps = Option::get($module_id, 'YAKIT_PRODUCT_IBLOCK_ID', '');
if ($request->isPost()) {
    $postIblock = $request->getPost('YAKIT_PRODUCT_IBLOCK_ID');
    if ($postIblock !== null && (string)$postIblock !== '') {
        $productIblockIdForProps = $postIblock;
    }
}
if ((int)$productIblockIdForProps > 0 && \Bitrix\Main\Loader::includeModule('iblock')) {
    $propRes = \CIBlockProperty::GetList(
        array('SORT' => 'ASC', 'NAME' => 'ASC'),
        array('IBLOCK_ID' => $productIblockIdForProps, 'ACTIVE' => 'Y')
    );
    while ($p = $propRes->GetNext()) {
        $code = !empty($p['CODE']) ? $p['CODE'] : 'PROPERTY_' . $p['ID'];
        $arProductIdProperties[$code] = $p['NAME'] . ' [' . $code . ']';
    }
}

function getYastoreCheckoutButtonCssDefault()
{
    return "#yastore-checkout-button {\n"
        . "    border: none;\n"
        . "    outline: none;\n"
        . "    border-radius: 12px;\n"
        . "    background-color: rgb(255, 99, 41);\n"
        . "    color: #ffffff;\n"
        . "    padding: 0;\n"
        . "    margin: 12px 0 0 10px;\n"
        . "    width: 100%;\n"
        . "    font-family: 'YS Text', 'Helvetica Neue', Arial, sans-serif;\n"
        . "    font-weight: 600;\n"
        . "    line-height: 16px;\n"
        . "    display: flex;\n"
        . "    flex-direction: column;\n"
        . "    align-items: center;\n"
        . "    justify-content: center;\n"
        . "    text-decoration: none;\n"
        . "    transition: transform .12s ease-out, filter .12s ease-out;\n"
        . "    cursor: pointer;\n"
        . "    box-sizing: border-box;\n"
        . "}\n"
        . "#yastore-checkout-button:hover,\n"
        . "#yastore-checkout-button:focus,\n"
        . "#yastore-checkout-button:visited {\n"
        . "    color: #ffffff;\n"
        . "    text-decoration: none;\n"
        . "}\n"
        . "#yastore-checkout-button *,\n"
        . "#yastore-checkout-button *:hover {\n"
        . "    text-decoration: none;\n"
        . "    color: inherit;\n"
        . "}\n"
        . ".yastore-checkout-button__main {\n"
        . "    display: flex;\n"
        . "    gap: 6px;\n"
        . "    align-items: center;\n"
        . "    justify-content: center;\n"
        . "    width: 100%;\n"
        . "    padding: 9px 12px 0;\n"
        . "    box-sizing: border-box;\n"
        . "}\n"
        . "#yastore-checkout-button:hover {\n"
        . "    background: linear-gradient(245deg, rgba(255, 99, 41, 0) 85%, #FFC002 109%), linear-gradient(77deg, rgb(255, 192, 2, .5) .5%, rgba(255, 99, 41, .18) 24%), radial-gradient(57% 134.79% at 0% 0%, #FF27F5 0%, #FF6329 100%);\n"
        . "}\n"
        . "#yastore-checkout-button:active {\n"
        . "    transform: scale(.97);\n"
        . "    filter: brightness(0.9);\n"
        . "}\n"
        . "\n"
        . "/* Промо: как .pay-button__additional в sdk-payment-method-2 */\n"
        . ".yastore-checkout-promo,\n"
        . ".yastore-checkout-promo .pay-button__additional {\n"
        . "    width: 100%;\n"
        . "    padding: 0 12px 8px;\n"
        . "    font-size: 12px;\n"
        . "    font-weight: 400;\n"
        . "    line-height: normal;\n"
        . "    opacity: 0.8;\n"
        . "    text-align: center;\n"
        . "    white-space: nowrap;\n"
        . "    text-decoration: none;\n"
        . "    font-variant-numeric: lining-nums proportional-nums;\n"
        . "}\n"
        . ".yastore-checkout-promo:empty {\n"
        . "    display: none;\n"
        . "}\n"
        . ".yastore-checkout-promo .pay-button__cashback-amount {\n"
        . "    font-size: 10px;\n"
        . "    font-weight: 400;\n"
        . "}\n"
        . ".yastore-checkout-promo .pay-button__discount-amount {\n"
        . "    font-size: 10px;\n"
        . "    font-style: italic;\n"
        . "    font-weight: 400;\n"
        . "}\n"
        . ".yastore-checkout-promo svg {\n"
        . "    display: inline-block;\n"
        . "    margin-right: 1px;\n"
        . "    margin-bottom: -1px;\n"
        . "    vertical-align: baseline;\n"
        . "    flex-shrink: 0;\n"
        . "}\n"
        . ".yastore-checkout-promo svg.pay-label-monochrome {\n"
        . "    vertical-align: text-bottom;\n"
        . "}";
}

// Яндекс Пэй – Настройки бейджей

$arYaPayThemeFields = array(
    'light' => 'Light',
    'dark' => 'Dark',
);
$arYaPayBadgeSizeFields = array(
    'l' => 'l',
    's' => 's',
    'm' => 'm',
);
$arYaPayBadgeColorFields = array(
    'primary' => 'primary',
    'green' => 'green',
    'grey' => 'grey',
    'transparent' => 'transparent',
);
$arYaPayBadgeAlignFields = array(
    'left' => 'left',
    'center' => 'center',
    'right' => 'right',
);
$arYaPayBadgeVariantFields = array(
    'detailed' => 'detailed',
    'simple' => 'simple',
);
$arYaPayWidgetSizeFields = array(
    'Medium' => 'Medium',
    'Small' => 'Small',
);
$arYaPayWidgetOutlineFields = array(
    '0' => 'Без обводки',
    '1' => 'С обводкой',
);
$arYaPayWidgetPaddingFields = array(
    'Default' => 'Default',
    'None' => 'None',
);
$arYaPayWidgetBackgroundFields = array(
    'Default' => 'Default',
    'Saturated' => 'Saturated',
    'Transparent' => 'Transparent',
);
$arYaPayWidgetThemeFields = array(
    'Light' => 'Light',
    'Dark' => 'Dark',
);

function getYastoreCheckoutProductButtonCssDefault()
{
    return "#yastore-checkout-product-button {\n"
        . "    border: none;\n"
        . "    outline: none;\n"
        . "    border-radius: 12px;\n"
        . "    background-color: rgb(255, 99, 41);\n"
        . "    color: #ffffff;\n"
        . "    padding: 0 12px;\n"
        . "    margin: 12px 0 0 10px;\n"
        . "    height: 42px;\n"
        . "    width: 100%;\n"
        . "    font-weight: 600;\n"
        . "    font-family: inherit;\n"
        . "    line-height: 16px;\n"
        . "    display: flex;\n"
        . "    gap: 6px;\n"
        . "    align-items: center;\n"
        . "    justify-content: center;\n"
        . "    transition: transform .12s ease-out, filter .12s ease-out;\n"
        . "}\n"
        . "#yastore-checkout-product-button:hover {\n"
        . "    background: linear-gradient(245deg, rgba(255, 99, 41, 0) 85%, #FFC002 109%), linear-gradient(77deg, rgb(255, 192, 2, .5) .5%, rgba(255, 99, 41, .18) 24%), radial-gradient(57% 134.79% at 0% 0%, #FF27F5 0%, #FF6329 100%);\n"
        . "}\n"
        . "#yastore-checkout-product-button:active {\n"
        . "    transform: scale(.97);\n"
        . "    filter: brightness(0.9);\n"
        . "}";
}

$aTabs = array(
    array(
        'DIV' => 'edit1',
        'TAB' => 'Основные настройки',
        'OPTIONS' => array(
            array('__group_dostup', 'Доступ к YCP'),
            array('JWT_TOKEN', 'Токен доступа', '', array('text', 100)),
            array('YANDEX_KIT_CREDENTIALS', 'Токен API', '', array('text', 100)),
            array('__group_product_id', 'Идентификатор товара'),
            array('YAKIT_PRODUCT_ID_FIELD', 'Поле идентификатора товара', 'ID', array('select', $arProductIdFields)),
            array('YAKIT_PRODUCT_IBLOCK_ID', 'Инфоблок товаров', '', array('select', $arProductIBlocks)),
            array('YAKIT_PRODUCT_ID_PROPERTY', 'Код свойства (если поле = «Свойство инфоблока»)', '', array('select', $arProductIdProperties)),
            array('YAKIT_PRODUCT_IMAGE_PROPERTY', 'Код свойства фото', '', array('text', 40)),
            array('__group_sell', 'Продажи'),
            array('SEND_ORDER_EMAILS', 'Отправлять письма о заказах YCP', 'N', array('checkbox')),
            array('USE_GENERAL_STOCK_ONLY', 'Использовать общий остаток (игнорировать склады)', 'N', array('checkbox')),
            array('SELL_WITHOUT_STOCK_CHECK', 'Продавать все активные товары (не проверять наличие остатков)', 'N', array('checkbox')),
            array('DEFAULT_PRODUCT_QUANTITY', 'Количество товара по умолчанию (шт)', '1', array('text', 5)),
        )
    ),
    array(
        'DIV' => 'edit2',
        'TAB' => 'Статусы заказов',
        'OPTIONS' => array(
            array('STATUS_ON_PLACED', 'Статус при оплате заказа (placed)', 'P', array('select', $arStatuses)),
            array('STATUS_ON_CANCEL', 'Статус при отмене заказа (cancel)', 'C', array('select', $arStatuses)),
            array('STATUS_ON_DELIVERED', 'Статус при доставке заказа (delivered)', 'F', array('select', $arStatuses)),
        )
    ),
    array(
        'DIV' => 'edit3',
        'TAB' => 'Автоматизация',
        'OPTIONS' => array(
            array('AUTO_CANCEL_ON_STATUS_CHANGE', 'Отменять заказ в YCP при смене статуса', 'N', array('checkbox')),
            array('AUTO_CANCEL_STATUS', 'Статус для автоматической отмены', 'C', array('select', $arStatuses)),
            array('AUTO_COMPLETE_ON_STATUS_CHANGE', 'Завершать доставку в YCP при смене статуса', 'N', array('checkbox')),
            array('AUTO_COMPLETE_STATUS', 'Статус для автоматического завершения доставки', 'F', array('select', $arStatuses)),
        )
    ),
    array(
        'DIV' => 'edit4',
        'TAB' => 'Купить в 1 клик',
        'OPTIONS' => array(
            array('__group_basket_button', 'Кнопка в корзине'),
            array('SHOW_BUTTON', 'Показывать кнопку на странице корзины', 'N', array('checkbox')),
            array('YAKIT_BUTTON_TEXT', 'Текст кнопки купить', 'Быстрое оформление', array('text', 80)),
            array('YAKIT_BASKET_PAGE_PATH', 'Путь к странице корзины (фрагмент URL, без домена)', '/personal/cart/', array('text', 80)),
            array('BUTTON_ANCHOR', 'CSS-селектор контейнера для кнопки', '.basket-checkout-section-inner', array('text', 100)),
            array('YAKIT_BUTTON_INSERT_AFTER', 'CSS-селектор элемента, после которого вставлять кнопку (необязательно)', '', array('text', 100)),
            array('YAKIT_BUTTON_CSS', 'CSS-стили кнопки и промо', getYastoreCheckoutButtonCssDefault(), array('textarea', 24, 80)),
            array('__group_original_basket_button', 'Скрытие основной кнопки "оформления заказа"'),
            array('YAKIT_HIDE_ORIGINAL_BASKET_BUTTON', 'Скрывать основную кнопку на странице корзины', 'N', array('checkbox')),
            array('YAKIT_ORIGINAL_BASKET_BUTTON_SELECTOR', 'CSS-селектор основной кнопки', '.basket-btn-checkout', array('text', 100)),
            // Временно отключено: настройки кнопки в карточке товара.
            // array('__group_product_button', 'Кнопка в карточке товара'),
            // array('SHOW_PRODUCT_BUTTON', 'Показывать кнопку на карточке товара', 'N', array('checkbox')),
            // array('PRODUCT_BUTTON_TEXT', 'Текст кнопки в карточке', 'Быстрое оформление', array('text', 80)),
            // array('PRODUCT_BUTTON_ANCHOR', 'CSS-селектор контейнера кнопки в карточке', '', array('text', 100)),
            // array('PRODUCT_BUTTON_INSERT_AFTER', 'CSS-селектор элемента в карточке, после которого вставлять кнопку (необязательно)', '', array('text', 100)),
            // array('PRODUCT_ID_SELECTOR', 'Селекторы для поиска ID товара (через запятую)', '[data-product-id], [name=PRODUCT_ID], [name=PRODUCT_ID_INPUT]', array('text', 120)),
            // array('PRODUCT_BUTTON_CSS', 'CSS-стили кнопки в карточке', getYastoreCheckoutProductButtonCssDefault(), array('textarea', 18, 80)),
        )
    ),
    array(
        'DIV' => 'edit5',
        'TAB' => 'Торговые предложения',
        'OPTIONS' => array(
            array('USE_SKU', 'Использовать торговые предложения', 'N', array('checkbox')),
            array('SKU_IBLOCK_ID', 'Инфоблок торговых предложений', '', array('select', $arSkuIBlocks)),
            array('SKU_PROPERTIES', 'Свойства торговых предложений', '', array('multiselect', array())),
            array('SKU_COLOR_PROPERTY', 'Свойство «Цвет»', '', array('sku_color_select')),
        )
    ),
    array(
        'DIV' => 'edit6',
        'TAB' => 'Бейджи и виджеты Я.Пэй',
        'OPTIONS' => array(
            array('YA_PAY_MERCHANT_ID', 'Merchant ID', '', array('text', 80)),
            array('__group_yapay_badges', 'Настройки бейджей'),
            array('YA_PAY_BADGE_USE_SPLIT', 'Показывать сплит', 'N', array('checkbox')),
            array('YA_PAY_BADGE_USE_CASHBACK', 'Показывать баллы плюса', 'N', array('checkbox')),
            array('YA_PAY_BADGE_THEME', 'Тема', 'light', array('select', $arYaPayThemeFields)),
            array('YA_PAY_BADGE_SIZE', 'Размер', 'm', array('select', $arYaPayBadgeSizeFields)),
            array('YA_PAY_BADGE_COLOR', 'Цвет', 'primary', array('select', $arYaPayBadgeColorFields)),
            array('YA_PAY_BADGE_ALIGN', 'Выравнивание', 'left', array('select', $arYaPayBadgeAlignFields)),
            array('YA_PAY_BADGE_VARIANT', 'Вариант', 'detailed', array('select', $arYaPayBadgeVariantFields)),
            array('__group_yapay_widget', 'Настройка виджета'),
            array('YA_PAY_WIDGET_SPLIT', 'Только Сплит', '0', array('checkbox')),
            array('YA_PAY_WIDGET_HIDE_HEADER', 'Спрятать шапку', '0', array('checkbox')),
            array('YA_PAY_WIDGET_OUTLINE', 'Обводка', '0', array('select', $arYaPayWidgetOutlineFields)),
            array('YA_PAY_WIDGET_RADIUS', 'Скругление', '8', array('text', 5)),
            array('YA_PAY_WIDGET_WIDTH', 'Ширина виджета', '360', array('text', 5)),
            array('YA_PAY_WIDGET_SIZE', 'Размер виджета', 'Medium', array('select', $arYaPayWidgetSizeFields)),
            array('YA_PAY_WIDGET_PADDING', 'Padding виджета', 'Default', array('select', $arYaPayWidgetPaddingFields)),
            array('YA_PAY_WIDGET_BACKGROUND', 'Background виджета', 'Default', array('select', $arYaPayWidgetBackgroundFields)),
            array('YA_PAY_WIDGET_THEME', 'Тема виджета', 'Light', array('select', $arYaPayWidgetThemeFields)),
        )
    ),
    array(
        'DIV' => 'edit7',
        'TAB' => 'Отслеживание цен',
        'OPTIONS' => array(
            array('__group_products_prices', 'Отправка цен в Яндекс Товары'),
            array('PRODUCTS_PRICES_ENABLED', 'Включить отслеживание и отправку цен', 'N', array('checkbox')),
            array('__group_products_prices_oauth', 'Шаг 1. OAuth-токен'),
            array('PRODUCTS_PRICES_OAUTH_TOKEN', 'OAuth-токен', '', array('text', 100)),
            array('__group_products_prices_feed', 'Шаг 2. ID фида'),
            array('PRODUCTS_PRICES_FEED_ID', 'ID фида (feedId)', '', array('text', 20)),
            array('__group_products_prices_run', 'Параметры отправки'),
            array('PRODUCTS_PRICES_INTERVAL', 'Интервал отправки (мин)', '5', array('text', 5)),
            array('PRODUCTS_PRICES_PRICE_TYPE', 'Тип цены каталога', '', array('select', $arPriceTypes)),
        )
    ),
);

// Генерация токена (обрабатываем отдельно, до сохранения других полей)
$tokenGenerated = false;
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('generate_token')) {
    // Генерируем токен: 64 символа в hex формате
    $newToken = bin2hex(random_bytes(32)); // 32 байта = 64 hex символа
    Option::set($module_id, 'JWT_TOKEN', $newToken);
    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . '&token_generated=Y');
    $tokenGenerated = true;
}

// Получение свойств инфоблока товаров для поля «Код свойства» (AJAX)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('get_product_id_properties')) {
    header('Content-Type: application/json');
    $iblockId = (int)$request->getPost('iblock_id');
    if ($iblockId <= 0) {
        echo json_encode(array('success' => false, 'error' => 'Не указан ID инфоблока'));
        exit;
    }
    $properties = array();
    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $propRes = \CIBlockProperty::GetList(
            array('SORT' => 'ASC', 'NAME' => 'ASC'),
            array('IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y')
        );
        while ($p = $propRes->GetNext()) {
            $code = !empty($p['CODE']) ? $p['CODE'] : 'PROPERTY_' . $p['ID'];
            $properties[$code] = $p['NAME'] . ' [' . $code . ']';
        }
    }
    echo json_encode(array('success' => true, 'properties' => $properties));
    exit;
}

// Получение свойств инфоблока торговых предложений (AJAX запрос)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('get_sku_properties')) {
    header('Content-Type: application/json');
    
    $iblockId = (int)$request->getPost('iblock_id');
    
    if ($iblockId <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Не указан ID инфоблока'
        ]);
        exit;
    }
    
    $properties = array();
    
    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $propertyIterator = \CIBlockProperty::GetList(
            array('SORT' => 'ASC', 'ID' => 'ASC'),
            array('IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y')
        );
        
        while ($property = $propertyIterator->Fetch()) {
            // Исключаем служебные свойства
            if (stripos($property['CODE'], 'PHOTO') !== false) {
                continue;
            }
            if (strpos($property['CODE'], 'CML2_') === 0) {
                continue;
            }
            if ($property['PROPERTY_TYPE'] == 'F') { // Файл
                continue;
            }
            
            $properties[$property['ID']] = $property['NAME'] . ' [' . $property['CODE'] . ']';
        }
    }
    
    echo json_encode([
        'success' => true,
        'properties' => $properties
    ]);
    exit;
}

// Получение списка значений свойства (для свойства «Цвет» — автозаполнение соответствий)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('get_sku_property_values')) {
    header('Content-Type: application/json');
    
    $propertyId = (int)$request->getPost('property_id');
    $iblockId = (int)$request->getPost('iblock_id');
    
    if ($propertyId <= 0 || $iblockId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID свойства или инфоблока', 'values' => []]);
        exit;
    }
    
    $values = array();
    
    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $property = \CIBlockProperty::GetByID($propertyId)->Fetch();
        if (!$property || (int)$property['IBLOCK_ID'] !== $iblockId) {
            echo json_encode(['success' => true, 'values' => []]);
            exit;
        }
        
        if ($property['PROPERTY_TYPE'] === 'L') {
            $enumRes = \CIBlockPropertyEnum::GetList(
                array('SORT' => 'ASC', 'VALUE' => 'ASC'),
                array('PROPERTY_ID' => $propertyId)
            );
            while ($enum = $enumRes->GetNext()) {
                $v = trim((string)$enum['VALUE']);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        } elseif ($property['PROPERTY_TYPE'] === 'S' || $property['PROPERTY_TYPE'] === 'N') {
            $propCode = $property['CODE'];
            $res = \CIBlockElement::GetList(
                array('ID' => 'ASC'),
                array('IBLOCK_ID' => $iblockId, '!PROPERTY_' . $propCode => false),
                array('PROPERTY_' . $propCode),
                array('nTopCount' => 1000),
                array('ID', 'PROPERTY_' . $propCode)
            );
            $seen = array();
            while ($el = $res->GetNext()) {
                $pv = $el['PROPERTY_' . $propCode . '_VALUE'] ?? $el['PROPERTY_' . $propCode] ?? '';
                if (is_array($pv)) {
                    foreach ($pv as $one) {
                        $one = trim((string)$one);
                        if ($one !== '' && !isset($seen[$one])) {
                            $seen[$one] = true;
                            $values[] = $one;
                        }
                    }
                } else {
                    $pv = trim((string)$pv);
                    if ($pv !== '' && !isset($seen[$pv])) {
                        $seen[$pv] = true;
                        $values[] = $pv;
                    }
                }
            }
            sort($values);
        }
    }
    
    echo json_encode(['success' => true, 'values' => array_values($values)]);
    exit;
}

/**
 * Хост для self-test из настроек Bitrix (не HTTP_HOST запроса — защита от SSRF).
 */
function yamarketKitResolveSelfTestHost()
{
    if (defined('SITE_SERVER_NAME') && trim((string)SITE_SERVER_NAME) !== '') {
        return preg_replace('/:\d+$/', '', trim((string)SITE_SERVER_NAME));
    }

    if (class_exists(\Bitrix\Main\SiteTable::class)) {
        // В админке SITE_ID часто совпадает с языком (ru), а не с LID сайта (s1)
        $siteId = defined('SITE_ID') ? (string)SITE_ID : '';
        if ($siteId !== '') {
            $site = \Bitrix\Main\SiteTable::getList([
                'filter' => ['=LID' => $siteId, '=ACTIVE' => 'Y'],
                'select' => ['SERVER_NAME'],
                'limit' => 1,
            ])->fetch();

            if (is_array($site)) {
                $host = trim((string)($site['SERVER_NAME'] ?? ''));
                if ($host !== '') {
                    return preg_replace('/:\d+$/', '', $host);
                }
            }
        }

        $sites = \Bitrix\Main\SiteTable::getList([
            'filter' => ['=ACTIVE' => 'Y'],
            'select' => ['SERVER_NAME'],
            'order' => ['DEF' => 'DESC', 'SORT' => 'ASC'],
        ]);
        while ($site = $sites->fetch()) {
            $host = trim((string)($site['SERVER_NAME'] ?? ''));
            if ($host !== '') {
                return preg_replace('/:\d+$/', '', $host);
            }
        }
    }

    $host = trim((string)Option::get('main', 'server_name', ''));
    if ($host !== '') {
        return preg_replace('/:\d+$/', '', $host);
    }

    return '';
}

// Проверка подключения к API (AJAX запрос)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('test_connection')) {
    header('Content-Type: application/json');
    
    // Используем дефолтный адрес API, если не указан иной в опциях
    $apiUrl = Option::get($module_id, 'YANDEX_KIT_API_URL', 'https://integration.yastore.yandex.net/');
    
    // Получаем токен в формате STORE_ID#TOKEN
    $credentials = $request->getPost('YANDEX_KIT_CREDENTIALS') ?: Option::get($module_id, 'YANDEX_KIT_CREDENTIALS', '');
    
    if (empty($credentials)) {
        echo json_encode([
            'success' => false,
            'error' => 'Заполните поле: Токен АПИ YCP (STORE_ID#TOKEN)'
        ]);
        exit;
    }
    
    // Разбиваем на STORE_ID и TOKEN
    $parts = explode('#', $credentials, 2);
    if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
        echo json_encode([
            'success' => false,
            'error' => 'Неверный формат токена. Используйте формат: STORE_ID#TOKEN'
        ]);
        exit;
    }
    
    $storeId = trim($parts[0]);
    $apiToken = trim($parts[1]);
    
    // Убираем завершающий слэш, если есть
    $apiUrl = rtrim($apiUrl, '/');
    $url = "{$apiUrl}/api/public/v1/store";
    
    // Выполняем curl запрос
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'authorization: Bearer ' . $apiToken,
            'yandex-kit-store-id: ' . $storeId,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка подключения'
        ]);
        exit;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        if (isset($data['store_slug'])) {
            $storeSlug = $data['store_slug'];
            $result = [
                'success' => true,
                'message' => 'Успешно',
                'store_slug' => $storeSlug
            ];

            // Self-test: при успешном получении slug проверяем метод warehouses с токеном из админки
            $jwtToken = Option::get($module_id, 'JWT_TOKEN', '');
            if (!empty($jwtToken)) {
                $allowedHost = yamarketKitResolveSelfTestHost();
                if ($allowedHost === '') {
                    $result['selftest_ok'] = false;
                    $result['selftest_error'] = 'Укажите URL сервера в настройках сайта (Настройки → Сайты → ваш сайт)';
                } else {
                    $scheme = $request->isHttps() ? 'https' : 'http';
                    $selfTestUrl = $scheme . '://' . $allowedHost . '/yastore.checkout/?method=warehouses';
                    $chSelf = curl_init();
                    curl_setopt_array($chSelf, [
                        CURLOPT_URL => $selfTestUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $jwtToken,
                            'Content-Type: application/json'
                        ],
                        CURLOPT_TIMEOUT => 15,
                        CURLOPT_CONNECTTIMEOUT => 5,
                    ]);
                    $selfResponse = curl_exec($chSelf);
                    $selfHttpCode = curl_getinfo($chSelf, CURLINFO_HTTP_CODE);
                    $selfError = curl_error($chSelf);
                    curl_close($chSelf);

                    if ($selfError) {
                        $result['selftest_ok'] = false;
                        $result['selftest_error'] = 'Ошибка запроса: ' . $selfError;
                    } elseif ($selfHttpCode === 200) {
                        $selfData = json_decode($selfResponse, true);
                        if (is_array($selfData) && array_key_exists('warehouses', $selfData)) {
                            $result['selftest_ok'] = true;
                        } else {
                            $result['selftest_ok'] = false;
                            $result['selftest_error'] = 'Неверный формат ответа warehouses';
                        }
                    } else {
                        $result['selftest_ok'] = false;
                        $errBody = is_string($selfResponse) ? $selfResponse : '';
                        $result['selftest_error'] = 'HTTP ' . $selfHttpCode . ($errBody ? ': ' . mb_substr($errBody, 0, 200) : '');
                    }
                    $result['selftest_debug'] = [
                        'curl' => "curl -X GET '" . $selfTestUrl . "' -H 'Authorization: Bearer ***' -H 'Content-Type: application/json'",
                        'http_code' => $selfHttpCode,
                    ];
                }
            } else {
                $result['selftest_ok'] = false;
                $result['selftest_error'] = 'Токен доступа не настроен';
            }

            echo json_encode($result);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Неверный формат ответа от API'
            ]);
        }
    } else {
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error']) ? $errorData['error'] : 'Ошибка подключения';
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    }
    exit;
}

// Список фидов Products API по OAuth-токену (AJAX)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('load_products_prices_feeds')) {
    header('Content-Type: application/json');

    if (!\Bitrix\Main\Loader::includeModule('yandex.market')) {
        echo json_encode(['success' => false, 'error' => 'Модуль yandex.market не загружен']);
        exit;
    }

    $token = trim((string)($request->getPost('PRODUCTS_PRICES_OAUTH_TOKEN') ?: Option::get($module_id, 'PRODUCTS_PRICES_OAUTH_TOKEN', '')));

    if ($token === '') {
        echo json_encode(['success' => false, 'error' => 'Сначала укажите OAuth-токен (шаг 1)']);
        exit;
    }

    try {
        $apiRequest = new \Yandex\Market\Api\Products\FeedsInfo\Request($token);
        $response = $apiRequest->execute();

        if (!$response->isSuccess()) {
            echo json_encode(['success' => false, 'error' => 'API вернул ошибку. Проверьте OAuth-токен.']);
            exit;
        }

        $feeds = $response->getFeeds();

        if (empty($feeds)) {
            echo json_encode([
                'success' => false,
                'error' => 'Фиды не найдены. Загрузите YML-фид в Яндекс Вебмастер под тем же аккаунтом, что использовали для получения токена.',
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'feeds' => $feeds]);
    } catch (\Throwable $e) {
        $message = $e->getMessage();
        if (stripos($message, '401') !== false || stripos($message, '403') !== false || stripos($message, 'Unauthorized') !== false || stripos($message, 'Forbidden') !== false) {
            echo json_encode(['success' => false, 'error' => 'Неверный или отозванный OAuth-токен. Получите новый токен (шаг 1).']);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
    }
    exit;
}

// Проверка OAuth-токена Products API (AJAX)
if ($request->isPost() && check_bitrix_sessid() && $request->getPost('test_products_prices_token')) {
    header('Content-Type: application/json');

    if (!\Bitrix\Main\Loader::includeModule('yandex.market')) {
        echo json_encode(['success' => false, 'error' => 'Модуль yandex.market не загружен']);
        exit;
    }

    $token = trim((string)($request->getPost('PRODUCTS_PRICES_OAUTH_TOKEN') ?: Option::get($module_id, 'PRODUCTS_PRICES_OAUTH_TOKEN', '')));
    $feedId = (int)($request->getPost('PRODUCTS_PRICES_FEED_ID') ?: Option::get($module_id, 'PRODUCTS_PRICES_FEED_ID', 0));

    if ($token === '') {
        echo json_encode(['success' => false, 'error' => 'Укажите OAuth-токен']);
        exit;
    }

    if ($feedId <= 0) {
        try {
            $feedsRequest = new \Yandex\Market\Api\Products\FeedsInfo\Request($token);
            $feedsResponse = $feedsRequest->execute();

            if ($feedsResponse->isSuccess()) {
                $feedsCount = count($feedsResponse->getFeeds());
                echo json_encode([
                    'success' => true,
                    'message' => 'Токен действителен. Найдено фидов: ' . $feedsCount . '. Укажите ID фида (шаг 2) или загрузите список.',
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'API вернул ошибку. Проверьте OAuth-токен.']);
            }
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (stripos($message, '401') !== false || stripos($message, '403') !== false || stripos($message, 'Unauthorized') !== false || stripos($message, 'Forbidden') !== false) {
                echo json_encode(['success' => false, 'error' => 'Неверный или отозванный OAuth-токен']);
            } else {
                echo json_encode(['success' => false, 'error' => $message]);
            }
        }
        exit;
    }

    try {
        $apiRequest = new \Yandex\Market\Api\Products\OfferPrices\Updates\Request($token);
        $apiRequest->setOffers([
            [
                'feed' => ['id' => $feedId],
                'id' => '__connection_test__',
                'price' => [
                    'currencyId' => 'RUR',
                    'value' => 1,
                ],
            ],
        ]);
        $response = $apiRequest->execute();

        if ($response->isSuccess()) {
            echo json_encode(['success' => true, 'message' => 'Токен принят, API доступен']);
            exit;
        }

        $errors = $response->getErrors();
        $codes = [];
        foreach ($errors as $error) {
            if (is_array($error) && !empty($error['code'])) {
                $codes[] = (string)$error['code'];
            }
        }

        if (in_array('INVALID_OFFER_ID', $codes, true) || in_array('INVALID_FEED_ID', $codes, true)) {
            echo json_encode(['success' => true, 'message' => 'Авторизация успешна (тестовое предложение отклонено API)']);
            exit;
        }

        $message = 'Ошибка API';
        if (!empty($errors[0]['message'])) {
            $message = (string)$errors[0]['message'];
        }

        echo json_encode(['success' => false, 'error' => $message]);
    } catch (\Throwable $e) {
        $message = $e->getMessage();
        if (stripos($message, '401') !== false || stripos($message, '403') !== false || stripos($message, 'Unauthorized') !== false || stripos($message, 'Forbidden') !== false) {
            echo json_encode(['success' => false, 'error' => 'Неверный или отозванный OAuth-токен']);
        } else {
            echo json_encode(['success' => false, 'error' => $message]);
        }
    }
    exit;
}

if ($request->isPost() && check_bitrix_sessid() && !$tokenGenerated) {
    foreach ($aTabs as $aTab) {
        foreach ($aTab['OPTIONS'] as $arOption) {
            if (!is_array($arOption))
                continue;
            if (isset($arOption[0]) && strpos((string)$arOption[0], '__') === 0)
                continue;

            $optionName = $arOption[0];
            // Для checkbox: если не отмечен, значение не приходит в POST
            if ($arOption[3][0] == 'checkbox') {
                if (in_array($optionName, ['YA_PAY_WIDGET_SPLIT', 'YA_PAY_WIDGET_HIDE_HEADER'], true)) {
                    $value = $request->getPost($optionName) == '1' ? '1' : '0';
                } else {
                    $value = $request->getPost($optionName) == 'Y' ? 'Y' : 'N';
                }
            } elseif ($arOption[3][0] == 'multiselect') {
                // Для multiselect получаем массив значений
                $value = $request->getPost($optionName);
                if (is_array($value)) {
                    $value = serialize($value);
                } else {
                    $value = '';
                }
            } else {
                $value = $request->getPost($optionName);
            }
            if ($optionName === 'YAKIT_PRODUCT_ID_FIELD' && (string)$value === '') {
                $value = Option::get($module_id, 'YAKIT_PRODUCT_ID_FIELD', 'ID');
            }
            
            // Специальная обработка для YANDEX_KIT_CREDENTIALS - разбиваем и сохраняем в два поля
            if ($optionName == 'YANDEX_KIT_CREDENTIALS') {
                if (!empty($value)) {
                    $parts = explode('#', $value, 2);
                    if (count($parts) === 2) {
                        // Сохраняем в новое поле
                        Option::set($module_id, 'YANDEX_KIT_CREDENTIALS', (string) $value);
                        // Также сохраняем в старые поля для обратной совместимости
                        Option::set($module_id, 'YANDEX_KIT_STORE_ID', trim($parts[0]));
                        Option::set($module_id, 'YANDEX_KIT_API_TOKEN', trim($parts[1]));
                    } else {
                        Option::set($module_id, 'YANDEX_KIT_CREDENTIALS', '');
                    }
                } else {
                    Option::set($module_id, 'YANDEX_KIT_CREDENTIALS', '');
                }
            } else {
                if ($optionName == 'YAKIT_BUTTON_CSS' && trim((string) $value) === '') {
                    $value = getYastoreCheckoutButtonCssDefault();
                }
                if ($optionName == 'YAKIT_BUTTON_TEXT' && trim((string) $value) === '') {
                    $value = 'Быстрое оформление';
                }
                if ($optionName == 'PRODUCT_BUTTON_TEXT' && trim((string) $value) === '') {
                    $value = 'Быстрое оформление';
                }
                if ($optionName == 'PRODUCT_ID_SELECTOR' && trim((string) $value) === '') {
                    $value = '[data-product-id], [name=PRODUCT_ID], [name=PRODUCT_ID_INPUT]';
                }
                if ($optionName == 'PRODUCT_BUTTON_CSS' && trim((string) $value) === '') {
                    $value = getYastoreCheckoutProductButtonCssDefault();
                }
                if ($optionName == 'YA_PAY_WIDGET_WIDTH') {
                    $value = (string) min(500, max(250, (int) $value));
                }
                if ($optionName == 'YA_PAY_WIDGET_RADIUS') {
                    $value = (string) min(30, max(0, (int) $value));
                }
                if ($optionName == 'DEFAULT_PRODUCT_QUANTITY') {
                    $sellWithoutStock = $request->getPost('SELL_WITHOUT_STOCK_CHECK') === 'Y';
                    if (!$sellWithoutStock) {
                        continue; // поле неактивно — не перезаписываем
                    }
                    $q = max(1, (int) $value);
                    $value = (string) $q;
                }
                if ($optionName === 'PRODUCTS_PRICES_INTERVAL') {
                    $value = (string) max(1, min(1440, (int) $value));
                }
                Option::set($module_id, $optionName, (string) $value);
            }
        }
    }
    // Сохранение соответствия значение цвета → HEX только при явном выборе маппинга (не пустая карта)
    $colorMapJson = $request->getPost('SKU_COLOR_MAP');
    $colorMapDecoded = is_string($colorMapJson) ? json_decode($colorMapJson, true) : null;
    if (is_array($colorMapDecoded) && !empty($colorMapDecoded)) {
        Option::set($module_id, 'SKU_COLOR_MAP', $colorMapJson);
    } else {
        Option::set($module_id, 'SKU_COLOR_MAP', '');
    }
    // Принудительная очистка кэша опций, чтобы API сразу видел новые значения (в т.ч. YAKIT_PRODUCT_ID_FIELD)
    if (class_exists('\Bitrix\Main\Application', true)) {
        try {
            $cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
            $cache->clean('b_option:' . $module_id, 'b_option');
        } catch (\Exception $e) {
            // игнорируем ошибки кэша
        }
    }

    if (\Bitrix\Main\Loader::includeModule('yandex.market') && class_exists('\Yandex\Market\Products\Prices\Installer')) {
        try {
            \Yandex\Market\Products\Prices\Installer::install();
        } catch (\Throwable $e) {
        }
    }
}

// Получаем сохраненные свойства SKU для JavaScript
$savedSkuProperties = array();
$skuPropertiesValue = Option::get($module_id, 'SKU_PROPERTIES', '');
if (!empty($skuPropertiesValue)) {
    $unserialized = @unserialize($skuPropertiesValue);
    $savedSkuProperties = is_array($unserialized) ? $unserialized : array();
}

// Получаем сохраненные токены для автоматической проверки подключения
$savedJwtToken = Option::get($module_id, 'JWT_TOKEN', '');
$savedCredentials = Option::get($module_id, 'YANDEX_KIT_CREDENTIALS', '');
$autoCheckConnection = false;
if (!empty($savedJwtToken) && !empty($savedCredentials)) {
    // Проверяем формат credentials (должен содержать #)
    $parts = explode('#', $savedCredentials, 2);
    if (count($parts) === 2 && !empty(trim($parts[0])) && !empty(trim($parts[1]))) {
        $autoCheckConnection = true;
    }
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

$APPLICATION->SetTitle('Подключение к YCP');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
<style>
tbody.formgroup {
    border-top: 2px solid #e0e0e0;
    background: #fafafa;
}
tbody.formgroup:first-of-type {
    border-top: none;
}
tbody.formgroup tr:first-child td {
    padding-top: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e8e8e8;
    font-size: 13px;
}
/* Таблица соответствия цветов на вкладке «Торговые предложения» — выравнивание по колонке с селектами (40% = ширина колонки подписей) */
#sku_color_map_row td {
    padding-left: 40%;
    vertical-align: top;
}
#edit1 input.yastore-main-option-input,
#edit1 select.yastore-main-option-input,
#edit7 input.yastore-main-option-input,
#edit7 select.yastore-main-option-input,
#edit4 input.yastore-btn-option-input,
#edit4 textarea.yastore-btn-option-input {
    width: 400px;
    max-width: 100%;
    box-sizing: border-box;
}
.ycp-products-prices-help {
    font-size: 12px;
    line-height: 1.55;
    color: #333;
    max-width: 720px;
}
.ycp-products-prices-help ol {
    margin: 8px 0 0 0;
    padding-left: 20px;
}
.ycp-products-prices-help li {
    margin-bottom: 6px;
}
.ycp-products-prices-help-note {
    margin-top: 10px;
    padding: 10px 12px;
    background: #fff8e6;
    border: 1px solid #ffe082;
    border-radius: 4px;
}
.ycp-products-prices-feeds-table {
    margin-top: 8px;
    border-collapse: collapse;
    width: 100%;
    max-width: 640px;
    font-size: 12px;
}
.ycp-products-prices-feeds-table th,
.ycp-products-prices-feeds-table td {
    border: 1px solid #ddd;
    padding: 6px 8px;
    text-align: left;
    vertical-align: top;
}
.ycp-products-prices-feeds-table tr:hover {
    background: #f5f9ff;
    cursor: pointer;
}
.ycp-products-prices-feeds-table tr.selected {
    background: #e8f4e8;
}
</style>

<form method='POST' id='yastore_checkout_options_form'
    action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>'
    onsubmit='buildColorMapJson(); return true;'>
    <?= bitrix_sessid_post(); ?>

    <? $tabControl->Begin(); ?>

    <? foreach ($aTabs as $aTab): ?>
        <? $tabControl->BeginNextTab(); ?>
        <?php $tbodyOpen = false; ?>
        <? foreach ($aTab['OPTIONS'] as $arOption):
            if (!is_array($arOption))
                continue;
            if (isset($arOption[0]) && strpos((string)$arOption[0], '__group') === 0): ?>
            <?php
            if ($tbodyOpen) { ?></tbody><?php $tbodyOpen = false; }
            if ($aTab['DIV'] == 'edit1' || $aTab['DIV'] == 'edit7') {
                ?>
            <tr class="heading">
                <td colspan="2"><b><?= htmlspecialcharsbx($arOption[1]) ?></b></td>
            </tr>
                <?php if ($aTab['DIV'] == 'edit7' && $arOption[0] === '__group_products_prices') : ?>
            <tr>
                <td colspan="2" style="padding-bottom: 12px;">
                    <div class="ycp-products-prices-help">
                        <p style="margin: 0 0 8px 0;">
                            Модуль отслеживает изменения цен в каталоге и отправляет их в
                            <a href="https://yandex.ru/dev/products/doc/ru/reference/post-offer-prices-updates" target="_blank" rel="noopener">API поиска по товарам</a>
                            (Яндекс Товары). ID товара в запросе берётся из настройки «Идентификатор товара» на вкладке «Основные настройки».
                        </p>
                        <div class="ycp-products-prices-help-note">
                            <strong>Важно:</strong> используйте один и тот же аккаунт Яндекса — тот, под которым вы
                            <a href="https://webmaster.yandex.ru/" target="_blank" rel="noopener">загружали YML-фид в Вебмастер</a>.
                            Иначе API не увидит ваши фиды или отклонит обновления цен.
                        </div>
                    </div>
                </td>
            </tr>
                <?php endif; ?>
                <?php if ($aTab['DIV'] == 'edit7' && $arOption[0] === '__group_products_prices_oauth') : ?>
            <tr>
                <td colspan="2" style="padding-bottom: 8px;">
                    <div class="ycp-products-prices-help">
                        <ol>
                            <li>Нажмите <a href="<?= htmlspecialcharsbx($productsPricesOAuthUrl) ?>" target="_blank" rel="noopener"><strong>Получить OAuth-токен</strong></a> — откроется страница авторизации Яндекса.</li>
                            <li>Войдите под аккаунтом, к которому привязан фид в Вебмастере, и разрешите доступ приложению.</li>
                            <li>На странице подтверждения скопируйте значение <strong>oauth_token</strong> — длинную строку символов.</li>
                            <li>Вставьте токен в поле ниже и нажмите «Проверить токен».</li>
                        </ol>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Подробнее:
                            <a href="https://yandex.ru/dev/products/doc/ru/concepts/authorization" target="_blank" rel="noopener">инструкция по авторизации</a>.
                            Токен можно получить только через браузер; храните его как пароль.
                        </p>
                    </div>
                </td>
            </tr>
                <?php endif; ?>
                <?php if ($aTab['DIV'] == 'edit7' && $arOption[0] === '__group_products_prices_feed') : ?>
            <tr>
                <td colspan="2" style="padding-bottom: 8px;">
                    <div class="ycp-products-prices-help">
                        <ol>
                            <li>После успешной проверки токена нажмите «Загрузить список фидов» — модуль запросит фиды вашего аккаунта через API.</li>
                            <li>В таблице найдите строку с URL вашего YML-фида (тот же адрес, что указан в Вебмастере или в настройках экспорта модуля).</li>
                            <li>Кликните по строке — <strong>feedId</strong> подставится в поле «ID фида» автоматически.</li>
                        </ol>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Если фид ещё не добавлен: откройте
                            <a href="https://webmaster.yandex.ru/" target="_blank" rel="noopener">Яндекс Вебмастер</a>
                            → ваш сайт → раздел с YML-фидами (товары) → загрузите или проверьте URL фида, затем повторите загрузку списка здесь.
                            <a href="https://yandex.ru/dev/products/doc/ru/reference/get-feeds-info" target="_blank" rel="noopener">Справка API feeds-info</a>.
                        </p>
                    </div>
                </td>
            </tr>
                <?php endif; ?>
                <?php if ($aTab['DIV'] == 'edit7' && $arOption[0] === '__group_products_prices_run') : ?>
            <tr>
                <td colspan="2" style="padding-bottom: 8px;">
                    <div class="ycp-products-prices-help">
                        <p style="margin: 0;">
                            После сохранения модуль ставит изменения цен в очередь и отправляет их в Яндекс с указанным интервалом.
                            <? if ($productsPricesPendingCount > 0): ?>
                                Сейчас в очереди: <strong><?= (int)$productsPricesPendingCount ?></strong>
                                <?= $productsPricesPendingCount === 1 ? 'товар' : 'товар(ов)' ?>.
                            <? endif; ?>
                        </p>
                    </div>
                </td>
            </tr>
                <?php endif; ?>
            <?php
            } else {
                $tbodyOpen = true;
                ?>
            <tbody class="formgroup">
            <tr>
                <td width="40%" style="white-space: nowrap; padding-top: 16px; padding-bottom: 4px;"><strong><?= htmlspecialcharsbx($arOption[1]) ?></strong></td>
                <td width="60%" style="padding-top: 16px; padding-bottom: 4px;"></td>
            </tr>
                <?php if ($arOption[0] === '__group_yapay_badges') : ?>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <a target="_blank" href="https://yastatic.net/s3/pay-static/docs/v46.0.0/custom/badges--custom-element/index.html">Интерактивное демо</a>
                </td>
            </tr>
                <?php endif; ?>
                <?php if ($arOption[0] === '__group_yapay_widget') : ?>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <a target="_blank" href="https://yastatic.net/s3/pay-static/docs/v46.0.0/custom/ultimate-widget/index.html">Интерактивное демо</a>
                </td>
            </tr>
                <?php endif; ?>
            <?php
            }
            continue; endif;

            // Специальная обработка для YANDEX_KIT_CREDENTIALS - объединяем значения из старых полей если новое пустое
            if ($arOption[0] == 'YANDEX_KIT_CREDENTIALS') {
                $val = Option::get($module_id, 'YANDEX_KIT_CREDENTIALS', '');
                // Если новое поле пустое, но есть старые значения - объединяем их
                if (empty($val)) {
                    $storeId = Option::get($module_id, 'YANDEX_KIT_STORE_ID', '');
                    $apiToken = Option::get($module_id, 'YANDEX_KIT_API_TOKEN', '');
                    if (!empty($storeId) && !empty($apiToken)) {
                        $val = $storeId . '#' . $apiToken;
                    }
                }
            } elseif ($arOption[0] == 'SKU_PROPERTIES') {
                // Для multiselect десериализуем значение
                $val = Option::get($module_id, 'SKU_PROPERTIES', '');
                if (!empty($val)) {
                    $unserialized = @unserialize($val);
                    $val = is_array($unserialized) ? $unserialized : array();
                } else {
                    $val = array();
                }
            } elseif ($arOption[0] == 'SKU_COLOR_PROPERTY') {
                $val = Option::get($module_id, 'SKU_COLOR_PROPERTY', '');
            } elseif ($arOption[0] == 'YAKIT_BUTTON_CSS') {
                $val = Option::get($module_id, 'YAKIT_BUTTON_CSS', '');
                if (trim($val) === '') {
                    $val = getYastoreCheckoutButtonCssDefault();
                }
            } elseif ($arOption[0] == 'PRODUCT_BUTTON_CSS') {
                $val = Option::get($module_id, 'PRODUCT_BUTTON_CSS', '');
                if (trim($val) === '') {
                    $val = getYastoreCheckoutProductButtonCssDefault();
                }
            } else {
                $val = Option::get($module_id, $arOption[0], $arOption[2]);
            }
            ?>
            <tr <? if ($arOption[0] == 'AUTO_CANCEL_STATUS'): ?>id='auto_cancel_status_row' style='display: <?= (Option::get($module_id, 'AUTO_CANCEL_ON_STATUS_CHANGE', 'N') == 'Y' ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'AUTO_COMPLETE_STATUS'): ?>id='auto_complete_status_row' style='display: <?= (Option::get($module_id, 'AUTO_COMPLETE_ON_STATUS_CHANGE', 'N') == 'Y' ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'SKU_IBLOCK_ID'): ?>id='sku_iblock_row' style='display: <?= (Option::get($module_id, 'USE_SKU', 'N') == 'Y' ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'SKU_PROPERTIES'): ?>id='sku_properties_row' style='display: <?= (Option::get($module_id, 'USE_SKU', 'N') == 'Y' && !empty(Option::get($module_id, 'SKU_IBLOCK_ID', '')) ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'SKU_COLOR_PROPERTY'): ?>id='sku_color_property_row' style='display: <?= (Option::get($module_id, 'USE_SKU', 'N') == 'Y' && !empty(Option::get($module_id, 'SKU_IBLOCK_ID', '')) ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'YAKIT_PRODUCT_ID_PROPERTY'): ?>id='yakit_product_id_property_row' style='display: <?= (Option::get($module_id, 'YAKIT_PRODUCT_ID_FIELD', 'ID') === 'PROPERTY' ? '' : 'none') ?>;'<? endif; ?>
                <? if ($arOption[0] == 'DEFAULT_PRODUCT_QUANTITY'): ?>id='default_quantity_row'<? endif; ?>>
                <td width='40%' style='white-space: nowrap;'>
                    <?= htmlspecialcharsbx($arOption[1]); ?><? if ($arOption[0] == 'USE_SKU'): ?><span style='position: relative; display: inline-block; margin-left: 5px;'>
                            <span id='use_sku_hint_icon' style='cursor: pointer; display: inline-block; width: 16px; height: 16px; line-height: 16px; text-align: center; background-color: #0066cc; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: bold; vertical-align: middle;'
                                  onclick='toggleSkuHint(event)' onmouseenter='showSkuHint()' onmouseleave='hideSkuHintOnLeave(event)'>?</span>
                            <div id='use_sku_hint' style='display: none; text-align: left; position: absolute; left: 50%; transform: translateX(-50%); top: 22px; background-color: #fff3cd; border: 1px solid #ffc107; padding: 8px 12px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1000; width: 300px; white-space: normal;'
                                 onmouseenter='showSkuHint()' onmouseleave='hideSkuHintOnLeave(event)'>
                                Используется для вывода в оформлении заказа вариаций товаров (цвет, размер и т.д).
                            </div>
                        </span><? endif; ?><? if ($arOption[0] == 'YAKIT_PRODUCT_IMAGE_PROPERTY'): ?><span style='position: relative; display: inline-block; margin-left: 5px;'>
                            <span id='yakit_image_hint_icon' style='cursor: pointer; display: inline-block; width: 16px; height: 16px; line-height: 16px; text-align: center; background-color: #0066cc; color: #ffffff; border-radius: 50%; font-size: 11px; font-weight: bold; vertical-align: middle;'
                                  onclick='toggleYakitImageHint(event)' onmouseenter='showYakitImageHint()' onmouseleave='hideYakitImageHintOnLeave(event)'>?</span>
                            <div id='yakit_image_hint' style='display: none; text-align: left; position: absolute; left: 0; top: 22px; background-color: #fff3cd; border: 1px solid #ffc107; padding: 8px 12px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1000; width: 320px; max-width: 90vw; white-space: normal;'
                                 onmouseenter='showYakitImageHint()' onmouseleave='hideYakitImageHintOnLeave(event)'>
                                <strong>Код свойства с фотографиями</strong> — символьный код свойства инфоблока того же типа, что и карточка товара (SKU или основной товар). Если пусты поля «Картинка для анонса» и «Детальная картинка», подставляется это свойство: тип «файл» или «строка» (полный URL, путь с «/» в начале, относительный путь; строка из цифр — как ID файла). Для <strong>множественного</strong> свойства берётся первое непустое значение (порядок строк в свойстве — по сортировке вариантов в админке).
                            </div>
                        </span><? endif; ?>:
                </td>
                <td width='60%'>
                    <? if ($arOption[3][0] == 'checkbox'): ?>
                        <?php $isYaPayWidgetCheckbox = in_array($arOption[0], ['YA_PAY_WIDGET_SPLIT', 'YA_PAY_WIDGET_HIDE_HEADER'], true); ?>
                        <input type='checkbox' name='<?= htmlspecialcharsbx($arOption[0]) ?>' value='<?= $isYaPayWidgetCheckbox ? '1' : 'Y' ?>' <?= ($isYaPayWidgetCheckbox ? ((string)$val === '1' ? 'checked' : '') : ($val == 'Y' ? 'checked' : '')) ?>
                            <? if ($arOption[0] == 'AUTO_CANCEL_ON_STATUS_CHANGE'): ?>
                                id='auto_cancel_checkbox' onchange='toggleAutoCancelStatus()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'AUTO_COMPLETE_ON_STATUS_CHANGE'): ?>
                                id='auto_complete_checkbox' onchange='toggleAutoCompleteStatus()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'USE_SKU'): ?>
                                id='use_sku_checkbox' onchange='toggleSkuSettings()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'SELL_WITHOUT_STOCK_CHECK'): ?>
                                id='sell_without_stock_checkbox' onchange='toggleDefaultQuantityField()'
                            <? endif; ?>>
                    <? endif; ?>
                    <? if ($arOption[3][0] == 'text'): ?>
                        <input type='text' name='<?= htmlspecialcharsbx($arOption[0]) ?>' value='<?= htmlspecialcharsbx($val) ?>'
                            size='<?= htmlspecialcharsbx($arOption[3][1]) ?>' id='<?= htmlspecialcharsbx($arOption[0]) ?>'<? if ($aTab['DIV'] == 'edit4'): ?> class='yastore-btn-option-input'<? endif; ?><? if ($aTab['DIV'] == 'edit1' || $aTab['DIV'] == 'edit7'): ?> class='yastore-main-option-input'<? endif; ?>
                            <? if ($arOption[0] == 'YANDEX_KIT_CREDENTIALS'): ?>
                                onchange='checkConnectionButtonState()' onkeyup='checkConnectionButtonState()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'DEFAULT_PRODUCT_QUANTITY'): ?>
                                <? if (Option::get($module_id, 'SELL_WITHOUT_STOCK_CHECK', 'N') !== 'Y'): ?> disabled<? endif; ?>
                            <? endif; ?>>
                    <? endif; ?>
                    <? if ($arOption[3][0] == 'textarea'): ?>
                        <textarea name='<?= htmlspecialcharsbx($arOption[0]) ?>' id='<?= htmlspecialcharsbx($arOption[0]) ?>'
                            rows='<?= (int)($arOption[3][1] ?? 8) ?>' cols='<?= (int)($arOption[3][2] ?? 80) ?>'<? if ($aTab['DIV'] == 'edit4'): ?> class='yastore-btn-option-input'<? endif; ?>><?= htmlspecialcharsbx($val) ?></textarea>
                    <? endif; ?>
                    <? if ($arOption[3][0] == 'select'): ?>
                        <select name='<?= htmlspecialcharsbx($arOption[0]) ?>'
                            <? if ($aTab['DIV'] == 'edit1' || $aTab['DIV'] == 'edit7'): ?> class='yastore-main-option-input'<? endif; ?>
                            <? if ($arOption[0] == 'AUTO_CANCEL_STATUS'): ?>
                                id='auto_cancel_status_select'
                            <? endif; ?>
                            <? if ($arOption[0] == 'AUTO_COMPLETE_STATUS'): ?>
                                id='auto_complete_status_select'
                            <? endif; ?>
                            <? if ($arOption[0] == 'SKU_IBLOCK_ID'): ?>
                                id='sku_iblock_select' onchange='loadSkuProperties()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'YAKIT_PRODUCT_IBLOCK_ID'): ?>
                                id='yakit_product_iblock_select' onchange='loadProductIdProperties()'
                            <? endif; ?>
                            <? if ($arOption[0] == 'YAKIT_PRODUCT_ID_FIELD'): ?>
                                id='yakit_product_id_field_select' onchange='toggleProductIdPropertyRow(this)'
                            <? endif; ?>
                            <? if ($arOption[0] == 'YAKIT_PRODUCT_ID_PROPERTY'): ?>
                                id='yakit_product_id_property_select' style='min-width: 280px;'
                            <? endif; ?>>
                            <? if (!in_array($arOption[0], [
                                'YAKIT_PRODUCT_ID_FIELD',
                                'YAKIT_PRODUCT_ID_PROPERTY',
                                'YA_PAY_BADGE_THEME',
                                'YA_PAY_BADGE_SIZE',
                                'YA_PAY_BADGE_COLOR',
                                'YA_PAY_BADGE_ALIGN',
                                'YA_PAY_BADGE_VARIANT',
                                'YA_PAY_WIDGET_SIZE',
                                'YA_PAY_WIDGET_OUTLINE',
                                'YA_PAY_WIDGET_PADDING',
                                'YA_PAY_WIDGET_BACKGROUND',
                                'YA_PAY_WIDGET_THEME',
                            ])): ?>
                            <option value=''>-- Выберите --</option>
                            <? endif; ?>
                            <? foreach ($arOption[3][1] as $optValue => $optName): ?>
                                <option value='<?= htmlspecialcharsbx($optValue) ?>' <?= ($val == $optValue || ($arOption[0] == 'YAKIT_PRODUCT_ID_FIELD' && (string)$val === '' && $optValue === 'ID') ? 'selected' : '') ?>>
                                    <?= htmlspecialcharsbx($optName) ?>
                                </option>
                            <? endforeach; ?>
                        </select>
                    <? endif; ?>
                    <? if ($arOption[3][0] == 'multiselect'): ?>
                        <select name='<?= htmlspecialcharsbx($arOption[0]) ?>[]' id='sku_properties_select' multiple size='10' style='width: 300px;'>
                            <!-- Опции будут загружены через JavaScript -->
                        </select>
                        <div id='sku_properties_loading' style='display: none; color: #666;'>Загрузка свойств...</div>
                    <? endif; ?>
                    <? if ($arOption[3][0] == 'sku_color_select'): ?>
                         <select name='<?= htmlspecialcharsbx($arOption[0]) ?>' id='sku_color_property_select' onchange='toggleColorMapRow(); loadColorPropertyValues(this.value);' style='width: 300px;'>
                             <option value="">— Не выбрано —</option>
                         </select>
                     <? endif; ?>
                </td>
            </tr>
            <? if (isset($arOption[0]) && $arOption[0] == 'SKU_COLOR_PROPERTY'): ?>
            <?php
            $savedColorMap = Option::get($module_id, 'SKU_COLOR_MAP', '');
            $colorMapDecoded = array();
            if ($savedColorMap !== '') {
                $colorMapDecoded = @json_decode($savedColorMap, true);
                if (!is_array($colorMapDecoded)) {
                    $colorMapDecoded = array();
                }
            }
            if (empty($colorMapDecoded)) {
                $defaultColorsPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/yandex.market/lib/checkout/data/default_colors.json';
                if (is_file($defaultColorsPath)) {
                    $defaultJson = @file_get_contents($defaultColorsPath);
                    if ($defaultJson !== false) {
                        $defaultList = @json_decode($defaultJson, true);
                        if (is_array($defaultList)) {
                            foreach ($defaultList as $item) {
                                if (!is_array($item)) continue;
                                $hex = isset($item['hex']) ? trim((string)$item['hex']) : '';
                                if ($hex === '') $hex = '#000000';
                                elseif (strpos($hex, '#') !== 0) $hex = '#' . $hex;
                                if (isset($item['ru']) && (string)$item['ru'] !== '') {
                                    $ru = (string)$item['ru'];
                                    $colorMapDecoded[$ru] = $hex;
                                    $colorMapDecoded[mb_strtolower($ru, 'UTF-8')] = $hex;
                                }
                                if (isset($item['en']) && (string)$item['en'] !== '') {
                                    $en = (string)$item['en'];
                                    $colorMapDecoded[$en] = $hex;
                                    $colorMapDecoded[mb_strtolower($en, 'UTF-8')] = $hex;
                                }
                            }
                        }
                    }
                }
            }
            ?>
            <tr id='sku_color_map_row' style='display: <?= Option::get($module_id, 'SKU_COLOR_PROPERTY', '') ? '' : 'none' ?>;'>
                <td colspan='2'>
                    <div style='margin-top: 8px;'>
                        <strong>Соответствие значение → HEX</strong>
                        <input type='hidden' name='SKU_COLOR_MAP' id='sku_color_map_input' value=''>
                        <table id='sku_color_map_table' style='margin-top: 8px; border-collapse: collapse;'>
                            <thead>
                                <tr>
                                    <th style='text-align: left; padding: 4px 8px 4px 0;'>Значение</th>
                                    <th style='text-align: left; padding: 4px 8px;'>Цвет (HEX)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id='sku_color_map_tbody'>
                                <!-- Строки заполняются автоматически при выборе свойства «Цвет» -->
                            </tbody>
                        </table>
                        <input type='button' value='Добавить' class='adm-btn' style='margin-top: 6px;' onclick='addColorMapRow()'>
                    </div>
                </td>
            </tr>
            <? endif; ?>
            <? // Кнопка генерации токена сразу после поля JWT_TOKEN ?>
            <? if ($arOption[0] == 'JWT_TOKEN'): ?>
            <tr>
                <td width='40%'></td>
                <td width='60%'>
                    <input type='button' value='Сгенерировать токен' class='adm-btn' 
                        onclick="if(confirm('Сгенерировать новый токен? Текущий токен будет заменен.')) {
                            var form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>';
                            var sessid = document.createElement('input');
                            sessid.type = 'hidden';
                            sessid.name = 'sessid';
                            sessid.value = '<?= bitrix_sessid() ?>';
                            form.appendChild(sessid);
                            var tokenBtn = document.createElement('input');
                            tokenBtn.type = 'hidden';
                            tokenBtn.name = 'generate_token';
                            tokenBtn.value = '1';
                            form.appendChild(tokenBtn);
                            document.body.appendChild(form);
                            form.submit();
                        }">
                    <? if ($request->get('token_generated') == 'Y'): ?>
                        <span style='color: green; margin-left: 10px;'>Токен успешно сгенерирован</span>
                    <? endif; ?>
                </td>
            </tr>
            <? endif; ?>
            <? if ($arOption[0] == 'PRODUCTS_PRICES_OAUTH_TOKEN'): ?>
            <tr>
                <td width='40%'></td>
                <td width='60%'>
                    <input type='button' id='test_products_prices_btn' value='Проверить токен' class='adm-btn'
                        onclick='testProductsPricesToken()'>
                    <span id='products_prices_token_result' style='margin-left: 10px; display: inline-block; vertical-align: top;'></span>
                </td>
            </tr>
            <? endif; ?>
            <? if ($arOption[0] == 'PRODUCTS_PRICES_FEED_ID'): ?>
            <tr>
                <td width='40%'></td>
                <td width='60%'>
                    <input type='button' id='load_products_prices_feeds_btn' value='Загрузить список фидов' class='adm-btn'
                        onclick='loadProductsPricesFeeds()'>
                    <span id='products_prices_feeds_result' style='margin-left: 10px; display: inline-block; vertical-align: top;'></span>
                    <div id='products_prices_feeds_container'></div>
                </td>
            </tr>
            <? endif; ?>
            <? // Кнопка проверки подключения после поля YANDEX_KIT_CREDENTIALS ?>
            <? if ($arOption[0] == 'YANDEX_KIT_CREDENTIALS'): ?>
            <tr>
                <td width='40%'></td>
                <td width='60%'>
                    <input type='button' id='test_connection_btn' value='Проверить' class='adm-btn' 
                        onclick='testConnection()' disabled>
                    <span id='connection_result' style='margin-left: 10px; display: inline-block; vertical-align: top;'></span>
                </td>
            </tr>
            <? endif; ?>
        <? endforeach; ?>
        <?php if ($tbodyOpen) { ?></tbody><?php } ?>
    <? endforeach; ?>

    <? $tabControl->Buttons(); ?>
    <input type='submit' name='Update' value='Сохранить' class='adm-btn-save'>
    <? $tabControl->End(); ?>
</form>

<script>
    function toggleDefaultQuantityField() {
        var checkbox = document.getElementById('sell_without_stock_checkbox');
        var input = document.getElementById('DEFAULT_PRODUCT_QUANTITY');
        if (checkbox && input) {
            input.disabled = !checkbox.checked;
        }
    }

    function toggleAutoCancelStatus() {
        var checkbox = document.getElementById('auto_cancel_checkbox');
        var statusRow = document.getElementById('auto_cancel_status_row');
        if (checkbox && statusRow) {
            statusRow.style.display = checkbox.checked ? '' : 'none';
        }
    }
    
    function toggleAutoCompleteStatus() {
        var checkbox = document.getElementById('auto_complete_checkbox');
        var statusRow = document.getElementById('auto_complete_status_row');
        if (checkbox && statusRow) {
            statusRow.style.display = checkbox.checked ? '' : 'none';
        }
    }
    
    function showSkuHint() {
        var hint = document.getElementById('use_sku_hint');
        if (hint) {
            hint.style.display = 'block';
        }
    }
    
    function hideSkuHintOnLeave(event) {
        // Небольшая задержка, чтобы можно было перейти на подсказку
        setTimeout(function() {
            var hint = document.getElementById('use_sku_hint');
            var icon = document.getElementById('use_sku_hint_icon');
            if (hint && icon) {
                var relatedTarget = event.relatedTarget;
                // Проверяем, не перешли ли мы на подсказку или иконку
                if (relatedTarget && (hint.contains(relatedTarget) || icon.contains(relatedTarget))) {
                    return;
                }
                hint.style.display = 'none';
            }
        }, 100);
    }
    
    function toggleSkuHint(event) {
        if (event) {
            event.stopPropagation();
        }
        var hint = document.getElementById('use_sku_hint');
        if (hint) {
            hint.style.display = hint.style.display === 'block' ? 'none' : 'block';
        }
    }

    function showYakitImageHint() {
        var hint = document.getElementById('yakit_image_hint');
        if (hint) {
            hint.style.display = 'block';
        }
    }

    function hideYakitImageHintOnLeave(event) {
        setTimeout(function() {
            var hint = document.getElementById('yakit_image_hint');
            var icon = document.getElementById('yakit_image_hint_icon');
            if (hint && icon) {
                var relatedTarget = event.relatedTarget;
                if (relatedTarget && (hint.contains(relatedTarget) || icon.contains(relatedTarget))) {
                    return;
                }
                hint.style.display = 'none';
            }
        }, 100);
    }

    function toggleYakitImageHint(event) {
        if (event) {
            event.stopPropagation();
        }
        var hint = document.getElementById('yakit_image_hint');
        if (hint) {
            hint.style.display = hint.style.display === 'block' ? 'none' : 'block';
        }
    }
    
    // Закрываем подсказку при клике вне её
    document.addEventListener('click', function(event) {
        var hint = document.getElementById('use_sku_hint');
        var icon = document.getElementById('use_sku_hint_icon');
        if (hint && icon && !hint.contains(event.target) && !icon.contains(event.target)) {
            hint.style.display = 'none';
        }
        var hImg = document.getElementById('yakit_image_hint');
        var iImg = document.getElementById('yakit_image_hint_icon');
        if (hImg && iImg && !hImg.contains(event.target) && !iImg.contains(event.target)) {
            hImg.style.display = 'none';
        }
    });
    
    var savedSkuColorProperty = '<?= CUtil::JSEscape(Option::get($module_id, 'SKU_COLOR_PROPERTY', '')) ?>';
    var initialColorMap = <?= json_encode(isset($colorMapDecoded) ? $colorMapDecoded : array()) ?>;
    
    function toggleSkuSettings() {
        var checkbox = document.getElementById('use_sku_checkbox');
        var iblockRow = document.getElementById('sku_iblock_row');
        var propertiesRow = document.getElementById('sku_properties_row');
        var colorPropertyRow = document.getElementById('sku_color_property_row');
        
        if (checkbox && iblockRow) {
            var isVisible = checkbox.checked;
            iblockRow.style.display = isVisible ? '' : 'none';
            
            if (!isVisible) {
                if (propertiesRow) propertiesRow.style.display = 'none';
                if (colorPropertyRow) colorPropertyRow.style.display = 'none';
                document.getElementById('sku_color_map_row').style.display = 'none';
                var iblockSelect = document.getElementById('sku_iblock_select');
                if (iblockSelect) iblockSelect.value = '';
            } else {
                var iblockSelect = document.getElementById('sku_iblock_select');
                if (propertiesRow && iblockSelect && iblockSelect.value) {
                    propertiesRow.style.display = '';
                    if (colorPropertyRow) colorPropertyRow.style.display = '';
                    loadSkuProperties();
                    toggleColorMapRow();
                } else {
                    if (colorPropertyRow) colorPropertyRow.style.display = 'none';
                    document.getElementById('sku_color_map_row').style.display = 'none';
                }
            }
        }
    }
    
    function toggleColorMapRow() {
        var select = document.getElementById('sku_color_property_select');
        var row = document.getElementById('sku_color_map_row');
        if (select && row) {
            row.style.display = select.value ? '' : 'none';
        }
    }
    
    function toggleProductIdPropertyRow(selectEl) {
        var row = document.getElementById('yakit_product_id_property_row');
        if (row && selectEl) {
            row.style.display = (selectEl.value === 'PROPERTY') ? '' : 'none';
            if (selectEl.value === 'PROPERTY') loadProductIdProperties();
        }
    }
    
    function loadProductIdProperties() {
        var iblockSelect = document.getElementById('yakit_product_iblock_select');
        var propertySelect = document.getElementById('yakit_product_id_property_select');
        if (!iblockSelect || !propertySelect) return;
        var iblockId = iblockSelect.value;
        var savedValue = '<?= CUtil::JSEscape(Option::get($module_id, 'YAKIT_PRODUCT_ID_PROPERTY', '')) ?>';
        var valueToRestore = propertySelect.value || savedValue;
        propertySelect.innerHTML = '<option value="">— Не выбрано —</option>';
        if (!iblockId) {
            propertySelect.disabled = false;
            return;
        }
        propertySelect.disabled = true;
        var formData = new FormData();
        formData.append('get_product_id_properties', 'Y');
        formData.append('iblock_id', iblockId);
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        fetch('<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            propertySelect.innerHTML = '<option value="">— Не выбрано —</option>';
            if (data.success && data.properties) {
                for (var code in data.properties) {
                    var opt = document.createElement('option');
                    opt.value = code;
                    opt.textContent = data.properties[code];
                    if (valueToRestore && code === valueToRestore) opt.selected = true;
                    propertySelect.appendChild(opt);
                }
            }
            propertySelect.disabled = false;
        })
        .catch(function() {
            propertySelect.innerHTML = '<option value="">Ошибка загрузки</option>';
            propertySelect.disabled = false;
        });
    }
    
    function loadColorPropertyValues(propertyId) {
        var tbody = document.getElementById('sku_color_map_tbody');
        var iblockSelect = document.getElementById('sku_iblock_select');
        if (!tbody) return;
        if (!propertyId) {
            tbody.innerHTML = '';
            return;
        }
        var iblockId = iblockSelect ? iblockSelect.value : '';
        if (!iblockId) {
            tbody.innerHTML = '';
            return;
        }
        var formData = new FormData();
        formData.append('get_sku_property_values', 'Y');
        formData.append('property_id', propertyId);
        formData.append('iblock_id', iblockId);
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        fetch('<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            tbody.innerHTML = '';
            var values = (data.success && data.values) ? data.values : [];
            var map = typeof initialColorMap === 'object' && initialColorMap !== null ? initialColorMap : {};
            function getHexForValue(m, v) {
                if (!v) return '#000000';
                if (m[v]) return m[v].indexOf('#') === 0 ? m[v] : '#' + m[v];
                var lower = (v + '').toLowerCase();
                for (var k in m) if (Object.prototype.hasOwnProperty.call(m, k) && (k + '').toLowerCase() === lower)
                    return m[k].indexOf('#') === 0 ? m[k] : '#' + m[k];
                return '#000000';
            }
            for (var i = 0; i < values.length; i++) {
                var val = values[i];
                var hex = getHexForValue(map, val);
                if (hex.indexOf('#') !== 0) hex = '#' + hex;
                var tr = document.createElement('tr');
                tr.className = 'sku-color-map-row';
                tr.innerHTML = "<td style='padding: 4px 8px 4px 0;'><input type='text' class='sku-color-map-value' readonly style='width: 200px; background: #f5f5f5;'></td>" +
                    "<td style='padding: 4px 8px;'><input type='color' class='sku-color-map-hex' style='width: 60px; height: 28px; padding: 0; border: 1px solid #ccc;'></td>" +
                    "<td><input type='button' value='Удалить' class='adm-btn' onclick='removeColorMapRow(this)'></td>";
                tr.querySelector('.sku-color-map-value').value = val;
                tr.querySelector('.sku-color-map-hex').value = hex;
                tbody.appendChild(tr);
            }
        })
        .catch(function() {
            tbody.innerHTML = '';
        });
    }
    
    function addColorMapRow() {
        var tbody = document.getElementById('sku_color_map_tbody');
        if (!tbody) return;
        var tr = document.createElement('tr');
        tr.className = 'sku-color-map-row';
        tr.innerHTML = "<td style='padding: 4px 8px 4px 0;'><input type='text' class='sku-color-map-value' value='' style='width: 200px;'></td>" +
            "<td style='padding: 4px 8px;'><input type='color' class='sku-color-map-hex' value='#000000' style='width: 60px; height: 28px; padding: 0; border: 1px solid #ccc;'></td>" +
            "<td><input type='button' value='Удалить' class='adm-btn' onclick='removeColorMapRow(this)'></td>";
        tbody.appendChild(tr);
    }
    
    function removeColorMapRow(btn) {
        var row = btn && btn.closest ? btn.closest('tr') : null;
        if (row) row.remove();
    }
    
    function buildColorMapJson() {
        var tbody = document.getElementById('sku_color_map_tbody');
        var input = document.getElementById('sku_color_map_input');
        if (!tbody || !input) return;
        var obj = {};
        var rows = tbody.querySelectorAll('tr.sku-color-map-row');
        for (var i = 0; i < rows.length; i++) {
            var valInp = rows[i].querySelector('.sku-color-map-value');
            var hexInp = rows[i].querySelector('.sku-color-map-hex');
            if (valInp && hexInp) {
                var v = (valInp.value || '').trim();
                if (v) obj[v] = hexInp.value || '#000000';
            }
        }
        input.value = JSON.stringify(obj);
    }
    
    function loadSkuProperties() {
        var iblockSelect = document.getElementById('sku_iblock_select');
        var propertiesSelect = document.getElementById('sku_properties_select');
        var loadingDiv = document.getElementById('sku_properties_loading');
        var propertiesRow = document.getElementById('sku_properties_row');
        
        if (!iblockSelect || !propertiesSelect) {
            return;
        }
        
        var iblockId = iblockSelect.value;
        
        if (!iblockId) {
            if (propertiesRow) propertiesRow.style.display = 'none';
            propertiesSelect.innerHTML = '';
            var colorSelect = document.getElementById('sku_color_property_select');
            if (colorSelect) { colorSelect.innerHTML = '<option value="">— Не выбрано —</option>'; toggleColorMapRow(); }
            return;
        }
        
        // Показываем список свойств
        if (propertiesRow) {
            propertiesRow.style.display = '';
        }
        
        // Показываем индикатор загрузки
        if (loadingDiv) {
            loadingDiv.style.display = '';
        }
        propertiesSelect.innerHTML = '';
        propertiesSelect.disabled = true;
        
        // Отправляем AJAX запрос
        var formData = new FormData();
        formData.append('get_sku_properties', 'Y');
        formData.append('iblock_id', iblockId);
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        
        fetch('<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
            
            if (data.success && data.properties) {
                propertiesSelect.innerHTML = '';
                
                var savedValues = <?= json_encode(isset($savedSkuProperties) ? $savedSkuProperties : array()) ?>;
                
                for (var propId in data.properties) {
                    var option = document.createElement('option');
                    option.value = propId;
                    option.textContent = data.properties[propId];
                    if (savedValues.indexOf(propId.toString()) !== -1 || savedValues.indexOf(parseInt(propId)) !== -1) {
                        option.selected = true;
                    }
                    propertiesSelect.appendChild(option);
                }
                
                // Заполняем селект «Свойство Цвет»
                var colorSelect = document.getElementById('sku_color_property_select');
                if (colorSelect) {
                    var firstOpt = colorSelect.querySelector('option[value=""]');
                    colorSelect.innerHTML = '';
                    if (firstOpt) colorSelect.appendChild(firstOpt);
                    else {
                        var emptyOpt = document.createElement('option');
                        emptyOpt.value = '';
                        emptyOpt.textContent = '— Не выбрано —';
                        colorSelect.appendChild(emptyOpt);
                    }
                    for (var propId in data.properties) {
                        var opt = document.createElement('option');
                        opt.value = propId;
                        opt.textContent = data.properties[propId];
                        if (String(propId) === String(savedSkuColorProperty)) opt.selected = true;
                        colorSelect.appendChild(opt);
                    }
                    toggleColorMapRow();
                    if (colorSelect.value) loadColorPropertyValues(colorSelect.value);
                }
            } else {
                propertiesSelect.innerHTML = '<option value="">Свойства не найдены</option>';
                var colorSelect = document.getElementById('sku_color_property_select');
                if (colorSelect) {
                    colorSelect.innerHTML = '<option value="">— Не выбрано —</option>';
                    toggleColorMapRow();
                }
            }
            
            propertiesSelect.disabled = false;
        })
        .catch(function(error) {
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
            propertiesSelect.innerHTML = '<option value="">Ошибка загрузки свойств</option>';
            propertiesSelect.disabled = false;
            console.error('Error loading SKU properties:', error);
        });
    }
    
    // Инициализация при загрузке страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            toggleAutoCancelStatus();
            toggleAutoCompleteStatus();
            toggleSkuSettings();
            toggleDefaultQuantityField();
            var productIdFieldSelect = document.getElementById('yakit_product_id_field_select');
            if (productIdFieldSelect) toggleProductIdPropertyRow(productIdFieldSelect);
            // Загружаем свойства, если инфоблок уже выбран
            var iblockSelect = document.getElementById('sku_iblock_select');
            if (iblockSelect && iblockSelect.value) {
                loadSkuProperties();
            }
        });
    } else {
        toggleAutoCancelStatus();
        toggleAutoCompleteStatus();
        toggleSkuSettings();
        toggleDefaultQuantityField();
        var productIdFieldSelect = document.getElementById('yakit_product_id_field_select');
        if (productIdFieldSelect) toggleProductIdPropertyRow(productIdFieldSelect);
        // Загружаем свойства, если инфоблок уже выбран
        var iblockSelect = document.getElementById('sku_iblock_select');
        if (iblockSelect && iblockSelect.value) {
            loadSkuProperties();
        }
    }
    
    // Проверка состояния кнопки "Проверить"
    function checkConnectionButtonState() {
        var credentials = document.getElementById('YANDEX_KIT_CREDENTIALS');
        var btn = document.getElementById('test_connection_btn');
        
        if (credentials && btn) {
            var credentialsValue = credentials.value.trim();
            // Проверяем, что есть символ # и обе части не пустые
            var parts = credentialsValue.split('#');
            var isValid = parts.length === 2 && parts[0].trim() && parts[1].trim();
            
            btn.disabled = !isValid;
        }
    }
    
    // Проверка подключения к API
    function testConnection() {
        var btn = document.getElementById('test_connection_btn');
        var resultSpan = document.getElementById('connection_result');
        
        if (!btn || !resultSpan) {
            return;
        }
        
        var credentials = document.getElementById('YANDEX_KIT_CREDENTIALS').value.trim();
        
        if (!credentials) {
            resultSpan.innerHTML = '<span style="color: red;">Заполните поле токена</span>';
            return;
        }
        
        var parts = credentials.split('#');
        if (parts.length !== 2 || !parts[0].trim() || !parts[1].trim()) {
            resultSpan.innerHTML = '<span style="color: red;">Неверный формат. Используйте: STORE_ID#TOKEN</span>';
            return;
        }
        
        // Показываем индикатор загрузки
        btn.disabled = true;
        btn.value = 'Проверка...';
        resultSpan.innerHTML = '<span style="color: blue; font-size: 11px; font-family: monospace;">Проверка подключения...</span>';
        
        // Создаем форму для отправки данных
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>';
        
        var sessid = document.createElement('input');
        sessid.type = 'hidden';
        sessid.name = 'sessid';
        sessid.value = '<?= bitrix_sessid() ?>';
        form.appendChild(sessid);
        
        var testBtn = document.createElement('input');
        testBtn.type = 'hidden';
        testBtn.name = 'test_connection';
        testBtn.value = '1';
        form.appendChild(testBtn);
        
        var credentialsInput = document.createElement('input');
        credentialsInput.type = 'hidden';
        credentialsInput.name = 'YANDEX_KIT_CREDENTIALS';
        credentialsInput.value = credentials;
        form.appendChild(credentialsInput);
        
        // Отправляем запрос через fetch для получения JSON ответа
        var formData = new FormData();
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        formData.append('test_connection', '1');
        formData.append('YANDEX_KIT_CREDENTIALS', credentials);
        
        fetch('<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            btn.disabled = false;
            btn.value = 'Проверить';
            
            var labelStyle = 'font-size: 11px; font-family: monospace;';
            if (data.success) {
                var line1 = '<span style="' + labelStyle + '">Проверка подключения к YCP:</span> <span style="' + labelStyle + ' color: green;">' + data.message;
                if (data.store_slug) {
                    line1 += '. Идентификатор сайта: ' + data.store_slug;
                }
                line1 += '</span>';
                var line2 = '';
                if (data.selftest_ok === true) {
                    line2 = '<span style="' + labelStyle + '">Проверка доступности API сайта:</span> <span style="' + labelStyle + ' color: green;">Успешно.</span>';
                } else if (data.selftest_ok === false && data.selftest_error) {
                    line2 = '<span style="' + labelStyle + '">Проверка доступности API сайта:</span> <span style="' + labelStyle + ' color: red;" title="' + (data.selftest_error || '').replace(/"/g, '&quot;') + '">' + (data.selftest_error || 'ошибка') + '</span>';
                }
                var debugBlock = '';
                if (data.selftest_debug) {
                    debugBlock = '<span style="display: block; margin-top: 4px;"><span id="selftest_debug_btn" style="cursor: pointer; font-size: 11px; font-family: monospace; color: #666; border: 1px solid #999; border-radius: 2px; padding: 0 3px; line-height: 1.2;" title="Показать curl и ответ">?</span><pre id="selftest_debug_pre" style="display: none; margin-top: 6px; padding: 8px; background: #f5f5f5; border: 1px solid #ccc; font-size: 11px; font-family: monospace; white-space: pre-wrap; max-width: 560px; max-height: 200px; overflow: auto;"></pre></span>';
                }
                resultSpan.innerHTML = '<span style="display: block;">' + line1 + '</span>' + (line2 ? '<span style="display: block; margin-top: 4px;">' + line2 + '</span>' : '') + debugBlock;
                if (data.selftest_debug) {
                    var debugBtn = document.getElementById('selftest_debug_btn');
                    var debugPre = document.getElementById('selftest_debug_pre');
                    if (debugBtn && debugPre) {
                        debugBtn.onclick = function() {
                            if (debugPre.style.display === 'none') {
                                if (debugPre.textContent === '') {
                                    debugPre.textContent = data.selftest_debug.curl + '\n\n--- Ответ (HTTP ' + data.selftest_debug.http_code + ') ---\n\n' + (data.selftest_debug.response || '');
                                }
                                debugPre.style.display = 'block';
                            } else {
                                debugPre.style.display = 'none';
                            }
                        };
                    }
                }
            } else {
                resultSpan.innerHTML = '<span style="' + labelStyle + '">Проверка подключения к YCP:</span> <span style="' + labelStyle + ' color: red;">' + (data.error || 'Ошибка подключения') + '</span>';
            }
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.value = 'Проверить';
            resultSpan.innerHTML = '<span style="font-size: 11px; font-family: monospace; color: red;">Ошибка: ' + error.message + '</span>';
        });
    }
    
    // Инициализация состояния кнопки при загрузке страницы
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            checkConnectionButtonState();
            // Автоматическая проверка подключения, если токены заполнены
            <? if ($autoCheckConnection): ?>
            setTimeout(function() {
                testConnection();
            }, 500);
            <? endif; ?>
        });
    } else {
        checkConnectionButtonState();
        // Автоматическая проверка подключения, если токены заполнены
        <? if ($autoCheckConnection): ?>
        setTimeout(function() {
            testConnection();
        }, 500);
        <? endif; ?>
    }

    function testProductsPricesToken() {
        var btn = document.getElementById('test_products_prices_btn');
        var resultSpan = document.getElementById('products_prices_token_result');
        var tokenInput = document.getElementById('PRODUCTS_PRICES_OAUTH_TOKEN');
        var feedInput = document.getElementById('PRODUCTS_PRICES_FEED_ID');

        if (!btn || !resultSpan || !tokenInput) {
            return;
        }

        var token = tokenInput.value.trim();
        var feedId = feedInput ? feedInput.value.trim() : '';

        if (!token) {
            resultSpan.innerHTML = '<span style="color: red;">Укажите OAuth-токен</span>';
            return;
        }

        btn.disabled = true;
        btn.value = 'Проверка...';
        resultSpan.innerHTML = '<span style="color: #666;">Проверка...</span>';

        var formData = new FormData();
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        formData.append('test_products_prices_token', '1');
        formData.append('PRODUCTS_PRICES_OAUTH_TOKEN', token);
        if (feedInput && feedInput.value.trim()) {
            formData.append('PRODUCTS_PRICES_FEED_ID', feedInput.value.trim());
        }

        fetch('<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.value = 'Проверить токен';
            if (data.success) {
                resultSpan.innerHTML = '<span style="color: green;">' + (data.message || 'OK') + '</span>';
            } else {
                resultSpan.innerHTML = '<span style="color: red;">' + (data.error || 'Ошибка') + '</span>';
            }
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.value = 'Проверить токен';
            resultSpan.innerHTML = '<span style="color: red;">' + error.message + '</span>';
        });
    }

    function loadProductsPricesFeeds() {
        var btn = document.getElementById('load_products_prices_feeds_btn');
        var resultSpan = document.getElementById('products_prices_feeds_result');
        var container = document.getElementById('products_prices_feeds_container');
        var tokenInput = document.getElementById('PRODUCTS_PRICES_OAUTH_TOKEN');
        var feedInput = document.getElementById('PRODUCTS_PRICES_FEED_ID');

        if (!btn || !resultSpan || !container || !tokenInput) {
            return;
        }

        var token = tokenInput.value.trim();
        if (!token) {
            resultSpan.innerHTML = '<span style="color: red;">Сначала укажите OAuth-токен (шаг 1)</span>';
            return;
        }

        btn.disabled = true;
        btn.value = 'Загрузка...';
        resultSpan.innerHTML = '<span style="color: #666;">Загрузка...</span>';
        container.innerHTML = '';

        var formData = new FormData();
        formData.append('sessid', '<?= bitrix_sessid() ?>');
        formData.append('load_products_prices_feeds', '1');
        formData.append('PRODUCTS_PRICES_OAUTH_TOKEN', token);

        fetch('<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.value = 'Загрузить список фидов';

            if (!data.success) {
                resultSpan.innerHTML = '<span style="color: red;">' + (data.error || 'Ошибка') + '</span>';
                return;
            }

            var feeds = data.feeds || [];
            resultSpan.innerHTML = '<span style="color: green;">Найдено фидов: ' + feeds.length + '. Кликните по строке, чтобы выбрать.</span>';

            if (feeds.length === 0) {
                return;
            }

            var table = document.createElement('table');
            table.className = 'ycp-products-prices-feeds-table';
            table.innerHTML = '<thead><tr><th>feedId</th><th>URL фида</th></tr></thead>';
            var tbody = document.createElement('tbody');
            var currentFeedId = feedInput ? feedInput.value.trim() : '';

            feeds.forEach(function(feed) {
                var tr = document.createElement('tr');
                if (String(feed.feedId) === currentFeedId) {
                    tr.className = 'selected';
                }
                tr.innerHTML = '<td><strong>' + feed.feedId + '</strong></td><td>' + escapeHtml(feed.feedUrl || '') + '</td>';
                tr.onclick = function() {
                    if (!feedInput) { return; }
                    feedInput.value = String(feed.feedId);
                    var rows = tbody.querySelectorAll('tr');
                    rows.forEach(function(row) { row.classList.remove('selected'); });
                    tr.classList.add('selected');
                    resultSpan.innerHTML = '<span style="color: green;">Выбран feedId: ' + feed.feedId + '</span>';
                };
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            container.appendChild(table);
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.value = 'Загрузить список фидов';
            resultSpan.innerHTML = '<span style="color: red;">' + error.message + '</span>';
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_after.php'; ?>