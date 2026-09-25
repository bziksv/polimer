<?php
namespace Yandex\Market\Products\Availability;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Yandex\Market\Api\Products\Glossary;
use Yandex\Market\Api\Products\HiddenOffers\Delete;
use Yandex\Market\Api\Products\HiddenOffers\Post;
use Yandex\Market\Products\Prices\Config;
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
		$hideOffers = [];
		$showOffers = [];
		$queue = [];
		$skippedIds = [];

		foreach ($pendingRows as $row)
		{
			$productId = (int)$row['PRODUCT_ID'];
			$offerId = StockResolver::resolveOfferId($productId);
			$hiddenOffer = $offerId !== null ? StockResolver::buildHiddenOffer($offerId, $feedId) : null;

			if ($hiddenOffer === null)
			{
				$skippedIds[] = (int)$row['ID'];
				continue;
			}

			$queueItem = [
				'id' => (int)$row['ID'],
				'product_id' => $productId,
				'hidden_offer' => $hiddenOffer,
			];

			if (StockResolver::isAvailable($productId))
			{
				$showOffers[] = $hiddenOffer;
				$queue[] = $queueItem + [ 'action' => 'show' ];
			}
			else
			{
				$hideOffers[] = $hiddenOffer;
				$queue[] = $queueItem + [ 'action' => 'hide' ];
			}
		}

		if (!empty($skippedIds))
		{
			Repository::removeByIds($skippedIds);
		}

		if (empty($queue))
		{
			return [ 'sent' => 0, 'removed' => $skippedIds ];
		}

		list($hideOffers, $showOffers, $queue) = self::applyHiddenOffersEvent($hideOffers, $showOffers, $queue, $feedId);

		$sent = 0;
		$processedIds = [];

		foreach (array_column($pendingRows, 'ID') as $rowId)
		{
			$rowId = (int)$rowId;

			if ($rowId > 0 && !in_array($rowId, $skippedIds, true))
			{
				$processedIds[] = $rowId;
			}
		}

		foreach (array_chunk($hideOffers, self::getChunkSize()) as $chunk)
		{
			self::sendHideChunk($chunk);
			$sent += count($chunk);
		}

		foreach (array_chunk($showOffers, self::getChunkSize()) as $chunk)
		{
			self::sendShowChunk($chunk);
			$sent += count($chunk);
		}

		Repository::removeByIds($processedIds);

		return [
			'sent' => $sent,
			'removed' => array_merge($skippedIds, $processedIds),
		];
	}

	protected static function applyHiddenOffersEvent(array $hideOffers, array $showOffers, array $queue, $feedId)
	{
		$event = new Event('yandex.market', ProductsEvents::ON_HIDDEN_OFFERS_REQUEST, [
			'hide_offers' => $hideOffers,
			'show_offers' => $showOffers,
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

			if (isset($params['hide_offers']) && is_array($params['hide_offers']))
			{
				$hideOffers = $params['hide_offers'];
			}

			if (isset($params['show_offers']) && is_array($params['show_offers']))
			{
				$showOffers = $params['show_offers'];
			}

			if (isset($params['queue']) && is_array($params['queue']))
			{
				$queue = $params['queue'];
			}
		}

		return [ $hideOffers, $showOffers, $queue ];
	}

	protected static function sendHideChunk(array $chunk)
	{
		if (empty($chunk)) { return; }

		$request = new Post\Request(Config::getOAuthToken());
		$request->setHiddenOffers($chunk);
		$response = $request->execute();

		if (!$response->isSuccess())
		{
			throw new \RuntimeException(self::formatErrors($response->getErrors(), 'hide hidden offers failed'));
		}
	}

	protected static function sendShowChunk(array $chunk)
	{
		if (empty($chunk)) { return; }

		$request = new Delete\Request(Config::getOAuthToken());
		$request->setHiddenOffers($chunk);
		$response = $request->execute();

		if (!$response->isSuccess())
		{
			throw new \RuntimeException(self::formatErrors($response->getErrors(), 'show hidden offers failed'));
		}
	}

	protected static function formatErrors(array $errors, $fallback)
	{
		$messages = [];

		foreach ($errors as $error)
		{
			if (!is_array($error)) { continue; }

			$code = isset($error['code']) ? (string)$error['code'] : '';
			$message = isset($error['message']) ? (string)$error['message'] : '';
			$messages[] = trim($code . ($message !== '' ? ': ' . $message : ''));
		}

		$message = implode('; ', array_filter($messages));

		return $message !== '' ? $message : $fallback;
	}

	public static function getChunkSize()
	{
		return Glossary::HIDDEN_OFFERS_CHUNK_SIZE;
	}
}
