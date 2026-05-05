<?
$MESS["ARTURGOLUBEV_ECOMMERCE_CHECKDEMO_EXPIRED"] = "Тестовый период работы решения закончился. Для продолжения использования решения Вы можете приобрести полную версию решения в <a href=\"https://marketplace.1c-bitrix.ru/solutions/#module_id#/\" target=\"_blank\">официальном маркетплейсе 1c-Битрикс</a>";
$MESS["ARTURGOLUBEV_ECOMMERCE_CHECKDEMO_DEMO"] = "Решение установлено в демонстрационном режиме. До конца демонстрационного режима осталось #date_diff# дней. Приобрести полную версию решения можно в <a href=\"https://marketplace.1c-bitrix.ru/solutions/#module_id#/\" target=\"_blank\">официальном маркетплейсе 1c-Битрикс</a>";
$MESS["ARTURGOLUBEV_ECOMMERCE_CHECKDEMO_PROD"] = "Период технической поддержи и получения обновлений решения истёк #date_to#. Продлить техническую поддержку решения и период обновлений можно купив продление решения (за 50% от стоимости лицензии) в <a href=\"https://marketplace.1c-bitrix.ru/tobasket.php?ID=#module_id#&prolong_period=12\" target=\"_blank\">официальном маркетплейсе 1c-Битрикс</a> (информация в данном блоке обновляется один раз в 10 минут)";
$MESS["ARTURGOLUBEV_ECOMMERCE_CHECKDEMO_PROD_UPDATES"] = "Период технической поддержи и получения обновлений решения истёк #date_to#. Продлить техническую поддержку решения и период обновлений можно купив продление решения (за 50% от стоимости лицензии) в <a href=\"https://marketplace.1c-bitrix.ru/tobasket.php?ID=#module_id#&prolong_period=12\" target=\"_blank\">официальном маркетплейсе 1c-Битрикс</a>. У решения доступны новые обновления! (информация в данном блоке обновляется один раз в 10 минут)";
$MESS["ARTURGOLUBEV_ECOMMERCE_CHECKDEMO_HAVE_UPDATES"] = "У решения доступны новые обновления! (информация в данном блоке обновляется один раз в 10 минут)";

$MESS["ARTURGOLUBEV_EC_CLEAR_CACHE"] = 'Настройки модуля изменены. Очистите <a target="_blank" href="/bitrix/admin/cache.php?lang=ru&tabControl_active_tab=fedit2">весь кеш сайта</a> и закройте это уведомление';

$MESS["ARTURGOLUBEV_EC_SMALL_BITRIX"] = 'Найдены не все модули, необходимые для работы решения. Проверьте редакцию вашего Битрикса - для работы решения необходима редакция Малый бизнес, Бизнес или ИМ + CRM (Для корректной работы решения требуется наличие модулей iblock, catalog и sale с соответствующей реализацией интернет-магазина)';

/* effective notice */
$MESS["ARTURGOLUBEV_EC_ERROS_SETTING_TITLE"] = "Для эффективной работы решения:<br>";
$MESS["ARTURGOLUBEV_EC_MAIN_CLEAR_LOG_NOSET"] = 'Параметр "Сколько дней хранить события" <a target="_blank" href="/bitrix/admin/settings.php?lang='.LANG.'&mid=main&tabControl_active_tab=edit8">главного модуля</a> не заполнен. Рекомендуется ограничить хранение записей журнала 14, 30 или 60 днями';

// $MESS["ARTURGOLUBEV_EC_ERROR_NO_EXPIRATION_EVENTS"] = 'Для корректного сбора событий необходимо установить параметр "Включить обработку устаревших событий" в настройках модуля <a href="/bitrix/admin/settings.php?lang=ru&mid=sale" target="_blank">Интернет магазин</a>';

$MESS["ARTURGOLUBEV_EC_OPTIONS_MAINTAB"] = "Основные настройки";
$MESS["ARTURGOLUBEV_EC_OFF_MODE"] = "Отключить на всех сайтах:";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM"] = "Номер заказа передавать из поля:";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ID"] = "Идентификатор заказа (ID)";
$MESS["ARTURGOLUBEV_EC_GET_ORDER_ID_FROM_ACCOUNT_NUMBER"] = "Номер заказа (ACCOUNT_NUMBER)";

$MESS["ARTURGOLUBEV_EC_BRAND_NO_SELECT"] = "Не выбрано";
$MESS["ARTURGOLUBEV_EC_BRAND_SKU_NO_SELECT"] = "Из настроек основного каталога";
$MESS["ARTURGOLUBEV_EC_OPTIONS_BRAND"] = "Настройка данных";
$MESS["ARTURGOLUBEV_EC_DATA_SECTION"] = "Настройка передаваемых данных";
$MESS["ARTURGOLUBEV_EC_OPTIONS_NO_SELECT"] = "Не выбрано";
$MESS["ARTURGOLUBEV_EC_OPTIONS_BRAND_PROP"] = "<span data-hint=\"Не обязательное поле. Если у вас нет поля бренд в каталоге - можно ничего не выбирать\"></span>Свойство бренд в инфоблоке ";

$MESS["ARTURGOLUBEV_EC_COLLECT_DATA"] = "Сбор дополнительных данных";
$MESS["ARTURGOLUBEV_EC_COLLECT_UTM"] = "<span data-hint=\"Не влияет на сбор данных. Решение будет сохранять utm-метки в свойства заказа\"></span>Сохранять utm-метки в заказ:";
$MESS["ARTURGOLUBEV_EC_COLLECT_CLIENT_ID"] = "<span data-hint=\"Не влияет на сбор данных. Решение будет сохранять clientID яндекс.метрики и google analytics в свойства заказа\"></span>Сохранять сохранять clientID в заказ:";

$MESS["ARTURGOLUBEV_EC_SYSTEM"] = "Системные настройки";
$MESS["ARTURGOLUBEV_EC_REQUEST_MODE"] = "<span data-hint=\"Запросы по событиям - новый режим, более производительный и точный. Запрос с интервалом - устаревший, стабильный, менее производительный. Без фоновых запросов - режим без фоновых запросов, заметно менее точный чем предыдущие два. \"></span> Режим работы:";
$MESS["ARTURGOLUBEV_EC_REQUEST_MODE_INTERVALS"] = "Запросы с интервалом";
$MESS["ARTURGOLUBEV_EC_REQUEST_MODE_EVENTS"] = "Запросы по событиям (рекомендуется)";
$MESS["ARTURGOLUBEV_EC_REQUEST_MODE_NOAJAX"] = "Без фоновых запросов";
$MESS["ARTURGOLUBEV_EC_DEBUG_MODE"] = "<span data-hint=\"Опция для разработчиков. Режим отладки позволяет отображать отдаваемые на отправку данные в Javascript-консоли\"></span> Режим отладки:";

$MESS["ARTURGOLUBEV_EC_PRODUCT_CARD_MODE"] = "Режим определения карточки товара:";
$MESS["ARTURGOLUBEV_EC_PRODUCT_CARD_MODE_COMPONENT"] = "Компонент в карточке товара";
$MESS["ARTURGOLUBEV_EC_PRODUCT_CARD_MODE_GLOBAL"] = "Из глобальной переменной CATALOG_CURRENT_ELEMENT_ID";



$MESS["ARTURGOLUBEV_EC_SITE_SETTING"] = 'Настройки для сайта';
$MESS["ARTURGOLUBEV_EC_DISABLED_SITE"] = "Отключить для сайта";

$MESS["ARTURGOLUBEV_EC_OPTIONS_ALL_SETTING"] = "Общие настройки";
$MESS["ARTURGOLUBEV_EC_CATALOGS"] = "Инфоблоки являющиеся каталогами:";

$MESS["ARTURGOLUBEV_EC_OPTIONS_ALL_SCHEME"] = "Разметка типов страниц";
$MESS["ARTURGOLUBEV_EC_CART_PAGE"] = '<span data-hint="Используется для Google Analitycs, Facebook и целей Метрики"></span> Страница корзины:';
$MESS["ARTURGOLUBEV_EC_CART_PAGE_NOTE"] = "В формате <b>/personal/cart/</b> без get параметров и домена";
$MESS["ARTURGOLUBEV_EC_ORDER_PAGE"] = '<span data-hint="Используется для Google Analitycs, Facebook и целей Метрики"></span> Страница оформления заказа:';
$MESS["ARTURGOLUBEV_EC_ORDER_PAGE_NOTE"] = "В формате <b>/personal/order/make/</b> без <b>?ORDER_ID</b>, get параметров и домена";
$MESS["ARTURGOLUBEV_EC_PRODUCT_PAGE"] = '<span data-hint="Необязательная настройка. Данное поле можно использовать вместо компонента устанавливаемого в вручную в карточку товара"></span> ЧПУ каталога:';
$MESS["ARTURGOLUBEV_EC_PRODUCT_PAGE_NOTE"] = "В формате <b>/catalog/</b>, стартовый раздел url каталога";

$MESS["ARTURGOLUBEV_EC_OPTIONS_YANDEX_SETTING"] = "Настройки Яндекс.Метрики";
$MESS["ARTURGOLUBEV_EC_YA_OFF"] = "Не отправлять данные в Яндекс.Метрику:";
$MESS["ARTURGOLUBEV_EC_CONTAINER_NAME"] = "<span data-hint=\"Имя контейнера данных должно совпадать с указанным в настройках метрики. Рекоммендуется использовать нестандартное - dataLayerCustom\"></span> Имя контейнера данных яндекс:";
$MESS["ARTURGOLUBEV_EC_YA_TARGET_ORDER"] = "<span data-hint=\"Цель встроенная в электронную коммерцию. Тип должен быть Javascript-событие. В настройку нужно вписать цифровой ID цели.\"></span> Номер цели срабатывающей при оформлении заказа:";

$MESS["ARTURGOLUBEV_EC_YA_COUNTER_ID"] = "<span data-hint=\"Используется для отправки целей в метрику. Если используются разные счётчики в зависимости от доменов и т.п. - оставьте поле пустым\"></span> ID счётчика метрики:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_PRODUCT"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_PRODUCT, Тип условия: JavaScript-событие\"></span> Активировать цель при просмотре карточки товара:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_ADD"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_ADD, Тип условия: JavaScript-событие\"></span> Активировать цель при добавлении в корзину:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_REMOVE"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_REMOVE, Тип условия: JavaScript-событие\"></span> Активировать цель при удалении из корзины:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_CART"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_CART, Тип условия: JavaScript-событие\"></span> Активировать цель при посещении страницы корзины:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_ORDER"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_ORDER, Тип условия: JavaScript-событие\"></span> Активировать цель при начале оформления заказа:";
$MESS["ARTURGOLUBEV_EC_YA_GOAL_PURCHASE"] = "<span data-hint=\"В метрике должна быть создана цель с параметрами: Идентификатор цели совпадает: AGE_PURCHASE, Тип условия: JavaScript-событие\"></span> Активировать цель при оформлении заказа:";

$MESS["ARTURGOLUBEV_EC_OPTIONS_GOOGLE_SETTING"] = "Настройки Google Analitycs";
$MESS["ARTURGOLUBEV_EC_GA_OFF"] = "Не отправлять данные в Google Analitycs:";
$MESS["ARTURGOLUBEV_EC_USE_GBV"] = "<span data-hint=\"Для товаров будет определен параметр google_business_vertical = retail. Необязательная, вспомогательная опция.\"></span>Передавать google_business_vertical:";
$MESS["ARTURGOLUBEV_EC_GA_TYPE"] = "Сбор данных через:";
$MESS["ARTURGOLUBEV_EC_GA_TYPE_MP"] = "Google Measure Protocol";
$MESS["ARTURGOLUBEV_EC_GA_TYPE_COUNTERS"] = "Счётчик GTAG / GTM";

$MESS["ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE"] = "Отправка данных о выборе доставки / оплаты:";
$MESS["ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE_STANDART"] = "При выборе способа доставки / оплаты";
$MESS["ARTURGOLUBEV_EC_GA_PAYSHIP_TYPE_ORDER"] = "При создании заказа";

$MESS["ARTURGOLUBEV_EC_OPTIONS_FACEBOOK_SETTING"] = "Facebook";
$MESS["ARTURGOLUBEV_EC_FB_OFF"] = "Не отправлять данные в Facebook:";

$MESS["ARTURGOLUBEV_EC_OPTIONS_FORMS_SETTING"] = "Автоматический сбор заполнений веб-форм";
$MESS["ARTURGOLUBEV_EC_FORMS_YM"] = "Отправлять события заполнения форм в Яндекс.Метрику:";
$MESS["ARTURGOLUBEV_EC_FORMS_GA"] = "Отправлять события заполнения форм в Google Analitycs:";

$MESS["ARTURGOLUBEV_EC_CONVERT_CURRENCY"] = "Конвертация валюты:";
$MESS["ARTURGOLUBEV_EC_CONVERT_CURRENCY_EMPTY"] = "Не конвертировать";
$MESS["ARTURGOLUBEV_EC_OPTIONS_CONVERT_CURRENCY_NOTE"] = "Конвертация происходит по курсу валют <a href='/bitrix/admin/currencies_rates.php?lang=ru' target='_blank'>из настроек</a>";

$MESS["ARTURGOLUBEV_EC_DEBUG_INFORMATION"] = 'Информация для отладки';
$MESS["ARTURGOLUBEV_EC_DEBUG_INFORMATION_TEXT"] = 'Последняя отработка событий постранично';
$MESS["ARTURGOLUBEV_EC_DEBUG_EVENT_PRODUCT"] = 'Просмотр карточки товара';
$MESS["ARTURGOLUBEV_EC_DEBUG_EVENT_ADDCART"] = 'Добавление товаров в корзину';
$MESS["ARTURGOLUBEV_EC_DEBUG_EVENT_REMOVECART"] = 'Удаление товаров из корзины';
$MESS["ARTURGOLUBEV_EC_DEBUG_EVENT_ORDER"] = 'Начало оформления заказа';
$MESS["ARTURGOLUBEV_EC_DEBUG_EVENT_PURCHASE"] = 'Оформление заказа';

/* help tab */
$MESS["ARTURGOLUBEV_ECOMMERCE_HELP_TAB_TITLE"] = "Полезная информация";
$MESS["ARTURGOLUBEV_ECOMMERCE_HELP_TAB_VALUE"] = "
Карточка решения на Marketplace - <a href='https://marketplace.1c-bitrix.ru/solutions/arturgolubev.ecommerce/#tab-about-link' target='_blank'>открыть</a><br/>
Инструкция по установке и настройке - <a href='https://arturgolubev.ru/instructions/metrics/' target='_blank'>открыть</a><br/>
Часто задаваемые вопросы - <a href='https://arturgolubev.ru/knowledge/course3/' target='_blank'>ссылка</a><br/>
Вопросы по покупке, оплате, активации модуля и т.п. - <a href='https://arturgolubev.ru/knowledge/course1/' target='_blank'>ссылка</a><br/>
Техническая поддержка - <a href='https://arturgolubev.ru/knowledge/course1/' target='_blank'>ссылка</a><br/>
";
?>