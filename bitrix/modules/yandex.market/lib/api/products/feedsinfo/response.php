<?php
namespace Yandex\Market\Api\Products\FeedsInfo;

use Yandex\Market\Api\Reference;

class Response extends Reference\Response
{
	public function getFeeds()
	{
		$feeds = $this->getField('feeds');

		if (!is_array($feeds))
		{
			return [];
		}

		$result = [];

		foreach ($feeds as $feed)
		{
			if (!is_array($feed)) { continue; }

			$feedId = isset($feed['feedId']) ? (int)$feed['feedId'] : 0;
			$feedUrl = isset($feed['feedUrl']) ? (string)$feed['feedUrl'] : '';

			if ($feedId <= 0) { continue; }

			$result[] = [
				'feedId' => $feedId,
				'feedUrl' => $feedUrl,
			];
		}

		return $result;
	}

	public function isSuccess()
	{
		$status = (string)$this->getField('status');

		if ($status !== '' && $status !== 'OK')
		{
			return false;
		}

		return true;
	}
}
