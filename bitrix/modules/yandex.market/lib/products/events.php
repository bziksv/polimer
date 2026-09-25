<?php
namespace Yandex\Market\Products;

/**
 * События Products API (отслеживание цен и др.).
 *
 * Подписка (например, в /bitrix/php_interface/init.php):
 *
 * use Bitrix\Main\Event;
 * use Bitrix\Main\EventManager;
 * use Bitrix\Main\EventResult;
 * use Yandex\Market\Products\ProductsEvents;
 *
 * EventManager::getInstance()->addEventHandler(
 *     'yandex.market',
 *     ProductsEvents::ON_OFFER_PRICES_UPDATE_REQUEST,
 *     static function (Event $event) {
 *         $offers = $event->getParameter('offers');
 *         foreach ($offers as $k => $offer) {
 *             // $offer['id'], $offer['price']['value'], ...
 *         }
 *         return new EventResult(EventResult::SUCCESS, [
 *             'offers' => $offers,
 *         ]);
 *     }
 * );
 */
final class ProductsEvents
{
	/**
	 * Перед POST offer-prices/updates (до HTTP-запроса в Yandex Products API).
	 *
	 * Параметры события:
	 * - offers — массив офферов для API (feed, id, price, …)
	 * - queue — параллельный массив метаданных очереди: [['id' => pendingRowId, 'product_id' => …], …]
	 * - feed_id — ID фида из настроек модуля
	 *
	 * EventResult::SUCCESS может вернуть:
	 * - offers — изменённый массив офферов
	 * - queue — изменённый массив queue (если меняется состав batch)
	 */
	public const ON_OFFER_PRICES_UPDATE_REQUEST = 'OnOfferPricesUpdateRequest';

	/**
	 * Перед POST/DELETE hidden-offers (до HTTP-запроса в Partner API поиска по товарам).
	 *
	 * Параметры события:
	 * - hide_offers — массив для POST hidden-offers: [['feedId' => …, 'offerId' => …], …]
	 * - show_offers — массив для DELETE hidden-offers
	 * - queue — метаданные очереди с action hide|show
	 * - feed_id — ID фида из настроек модуля
	 */
	public const ON_HIDDEN_OFFERS_REQUEST = 'OnHiddenOffersRequest';
}
