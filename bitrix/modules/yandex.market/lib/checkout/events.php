<?php
namespace Yandex\Market\Checkout;

/**
 * Имена событий чекаута YCP.
 *
 * Подписка (например, в /bitrix/php_interface/init.php):
 *
 * use Bitrix\Main\EventManager;
 * use Bitrix\Main\EventResult;
 * use Bitrix\Main\Event;
 * use Yandex\Market\Checkout\CheckoutEvents;
 *
 * EventManager::getInstance()->addEventHandler(
 *     'yandex.market',
 *     CheckoutEvents::ON_CHECK_BASKET_RESPONSE,
 *     static function (Event $event) {
 *         $response = $event->getParameter('response');
 *         $items = $response['items'];
 *         return new EventResult(EventResult::SUCCESS, [
 *             'items' => $items,
 *         ]);
 *     }
 * );
 */
final class CheckoutEvents
{
    /**
     * После сборки успешного ответа checkBasket (до отправки JSON).
     * Параметры события: response (массив с ключом items), request (тело запроса), warehouse_id.
     */
    public const ON_CHECK_BASKET_RESPONSE = 'OnCheckBasketResponse';
}
