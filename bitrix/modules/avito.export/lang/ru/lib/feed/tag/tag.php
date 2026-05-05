<?php

$MESS['AVITO_EXPORT_FEED_TAG_TAG_CHECK_ERROR_REQUIRED'] = 'пустое значение';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_CHECK_ERROR_REQUIRED_SIBLING'] = 'заполните #SELF# или #SIBLING#';

$MESS['AVITO_EXPORT_FEED_TAG_TAG_CHARACTERISTIC_TITLE'] = 'Характеристики';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_PARAM_TITLE'] = 'Произвольный';

$MESS['AVITO_EXPORT_FEED_TAG_TAG_AD_STATUS_HINT'] = '
<p><strong>Услуга продвижения</strong></p>
<p>Чтобы услуга подключилась, в&nbsp;кошельке на&nbsp;Авито должно быть достаточно рублей или бонусов.</p>
<p>За&nbsp;загрузку к&nbsp;объявлению можно подключить только одну услугу. Пока она действует, добавить ещё одну такую&nbsp;же не&nbsp;получится. Если вы&nbsp;попробуете это сделать, они подключатся по&nbsp;очереди. Разные услуги, которые увеличивают просмотры, тоже не&nbsp;сочетаются друг с&nbsp;другом.</p>
<p>Одно из&nbsp;значений</p>
<ul>
<li>Free&nbsp;&mdash; обычное объявление, услуги не&nbsp;подключаются</li>
<li>Highlight&nbsp;&mdash; &laquo;Выделение цветом&raquo; (действует 7&nbsp;дней)</li>
<li>XL&nbsp;&mdash; &laquo;XL-объявление&raquo; (действует 7&nbsp;дней)</li>
<li>x2_1&nbsp;&mdash; &laquo;До&nbsp;2&nbsp;раз больше просмотров на&nbsp;1&nbsp;день&raquo;</li>
<li>x2_7&nbsp;&mdash; &laquo;До&nbsp;2&nbsp;раз больше просмотров на&nbsp;7&nbsp;дней&raquo;</li>
<li>x5_1&nbsp;&mdash; &laquo;До&nbsp;5&nbsp;раз больше просмотров на&nbsp;1&nbsp;день&raquo;</li>
<li>x5_7&nbsp;&mdash; &laquo;До&nbsp;5&nbsp;раз больше просмотров на&nbsp;7&nbsp;дней&raquo;</li>
<li>x10_1&nbsp;&mdash; &laquo;До&nbsp;10&nbsp;раз больше просмотров на&nbsp;1&nbsp;день&raquo;</li>
<li>x10_7&nbsp;&mdash; &laquo;До&nbsp;10&nbsp;раз больше просмотров на&nbsp;7&nbsp;дней&raquo;.</li>
<li>x15_1&nbsp;&mdash; &laquo;До&nbsp;15&nbsp;раз больше просмотров на&nbsp;1&nbsp;день&raquo;. Доступно в&nbsp;некоторых регионах и&nbsp;категориях.</li>
<li>x15_7&nbsp;&mdash; &laquo;До&nbsp;15&nbsp;раз больше просмотров на&nbsp;7&nbsp;дней&raquo;. Доступно в&nbsp;некоторых регионах и&nbsp;категориях.</li>
<li>x20_1&nbsp;&mdash; &laquo;До&nbsp;20&nbsp;раз больше просмотров на&nbsp;1&nbsp;день&raquo;. Доступно в&nbsp;некоторых регионах и&nbsp;категориях.</li>
<li>x20_7&nbsp;&mdash; &laquo;До&nbsp;20&nbsp;раз больше просмотров на&nbsp;7&nbsp;дней&raquo;. Доступно в&nbsp;некоторых регионах и&nbsp;категориях.</li>
</ul>
<p>Значение по-умолчанию: Free</p>
';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_LISTING_FEE_HINT'] = '
<p><strong>Вариант платного размещения</strong></p>
<p>Одно из&nbsp;значений</p>
<ul>
<li>Package&nbsp;&mdash; размещение объявления осуществляется только при наличии подходящего пакета размещения</li>
<li>PackageSingle&nbsp;&mdash; при наличии подходящего пакета оплата размещения объявления произойдет с&nbsp;него; если нет подходящего пакета, но&nbsp;достаточно денег на&nbsp;кошельке Авито, то&nbsp;произойдет разовое размещение</li>
<li>Single&nbsp;&mdash; только разовое размещение, произойдет при наличии достаточной суммы на&nbsp;кошельке Авито; если есть подходящий пакет размещения, он&nbsp;будет проигнорирован</li>
</ul>
<p>Значение по-умолчанию: Package</p>
';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_AVITO_ID_HINT'] = '
<p><strong>Номер объявления на&nbsp;Авито</strong>&nbsp;&mdash; целое число.<p>
</p>Если вы&nbsp;размещали объявления вручную, а&nbsp;теперь хотите управлять ими с&nbsp;помощью Автозагрузки, то&nbsp;возможны 2&nbsp;основных варианта.</p>
<ol>
<li>воспользоваться включаемым по&nbsp;умолчанию режимом автоматической связки объявлений (подробнее смотрите в&nbsp;разделе &laquo;Вопросы и&nbsp;ответы&raquo;). К&nbsp;сожалению, в&nbsp;этом варианте неизбежен определенный процент ошибок.</li>
<li>чтобы избежать ошибок автоматической связки, можно указать в&nbsp;XML-файле в&nbsp;элементах AvitoId номера ранее размещенных объявлений. При корректных данных с&nbsp;вашей стороны, функционал позволит полностью избежать проблем с&nbsp;блокировкой объявлений за&nbsp;дубли и&nbsp;повторной оплаты размещения.</li>
</ol>
';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_CONTACT_PHONE_HINT'] = '
<p><strong>Контактный телефон</strong>&nbsp;&mdash; строка, содержащая только один российский номер телефона; должен быть обязательно указан код города или мобильного оператора.</p>
<p>Корректные примеры:</p>
<ul>
<li>+7 (495) 777-10-66</li>
<li>(81374) 4-55-75</li>
<li>8&nbsp;905&nbsp;207 04 90</li>
<li>+7&nbsp;905 2070490</li>
<li>88123855085</li>
<li>9052070490</li>
</ul>
';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_CONDITION_HINT'] = '
<p><strong>Состояние товара</strong></p>
<p>Одно из&nbsp;значений</p>
<ul>
<li>Новое</li>
<li>Б/у</li>
</ul>
';
$MESS['AVITO_EXPORT_FEED_TAG_TAG_CONTACT_METHOD_HINT'] = '
<p><strong>Способ связи</strong></p>
<p>Одно из&nbsp;значений</p>
<ul>
<li>По&nbsp;телефону и&nbsp;в&nbsp;сообщениях</li>
<li>По&nbsp;телефону</li>
<li>В&nbsp;сообщениях</li>
</ul>
<p>Значение по-умолчанию: По&nbsp;телефону и&nbsp;в&nbsp;сообщениях</p>
';

