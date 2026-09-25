<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Yandex\Market\Api\Products\Glossary;
use Yandex\Market\Api\Products\OfferPrices\Updates;
use Yandex\Market\Products\ProductsEvents;

class Sender
{
	public static function sendBatch(array $pendingRows)
	{
		if (!Config::isConfigured() || empty($pendingRows))
		{
			return [ 'sent' => 0, 'removed' => [] ];
		}

		$feedId = Config::getFeedId();
		$offers = [];
		$queue = [];

		foreach ($pendingRows as $row)
		{
			$priceData = Pricer::getPriceData($row['PRODUCT_ID']);

			if ($priceData === null) { continue; }

			$offer = Pricer::buildOffer($priceData, $feedId);

			if ($offer === null) { continue; }

			$offers[] = $offer;
			$queue[] = [
				'id' => (int)$row['ID'],
				'product_id' => (int)$row['PRODUCT_ID'],
			];
		}

		if (empty($offers))
		{
			Repository::removeByIds(array_column($pendingRows, 'ID'));

			return [ 'sent' => 0, 'removed' => array_column($pendingRows, 'ID') ];
		}

		list($offers, $queue) = self::applyOfferPricesUpdateEvent($offers, $queue, $feedId);

		if (empty($offers))
		{
			Repository::removeByIds(array_column($pendingRows, 'ID'));

			return [ 'sent' => 0, 'removed' => array_column($pendingRows, 'ID') ];
		}

		$rowMap = array_column($queue, 'id');

		$request = new Updates\Request(Config::getOAuthToken());
		$request->setOffers($offers);
		$response = $request->execute();

		if (!$response->isSuccess())
		{
			$message = self::formatErrors($response->getErrors());
			throw new \RuntimeException($message !== '' ? $message : 'Products API price update failed');
		}

		Repository::removeByIds($rowMap);

		return [
			'sent' => count($offers),
			'removed' => $rowMap,
		];
	}

	protected static function applyOfferPricesUpdateEvent(array $offers, array $queue, $feedId)
	{
		$event = new Event('yandex.market', ProductsEvents::ON_OFFER_PRICES_UPDATE_REQUEST, [
			'offers' => $offers,
			'queue' => $queue,
			'feed_id' => (int)$feedId,
		]);
		$event->send();

		foreach ($event->getResults() as $result)
		{
			if ($result->getType() === EventResult::ERROR)
			{
				continue;
			}

			$params = $result->getParameters();
			if (!is_array($params))
			{
				continue;
			}

			if (isset($params['offers']) && is_array($params['offers']))
			{
				$offers = $params['offers'];
			}

			if (isset($params['queue']) && is_array($params['queue']))
			{
				$queue = $params['queue'];
			}
			elseif (count($offers) < count($queue))
			{
				$queue = array_slice($queue, 0, count($offers));
			}
		}

		return [ $offers, $queue ];
	}

	protected static function formatErrors(array $errors)
	{
		$messages = [];

		foreach ($errors as $error)
		{
			if (!is_array($error)) { continue; }

			$code = isset($error['code']) ? (string)$error['code'] : '';
			$message = isset($error['message']) ? (string)$error['message'] : '';
			$messages[] = trim($code . ($message !== '' ? ': ' . $message : ''));
		}

		return implode('; ', array_filter($messages));
	}

	public static function getChunkSize()
	{
		return Glossary::OFFERS_CHUNK_SIZE;
	}
}
