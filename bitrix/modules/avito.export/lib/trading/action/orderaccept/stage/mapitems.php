<?php
namespace Avito\Export\Trading\Action\OrderAccept\Stage;

use Avito\Export\Api;
use Avito\Export\Assert;
use Avito\Export\Config;
use Avito\Export\Trading;
use Avito\Export\Trading\Action\OrderAccept\Data;
use Bitrix\Main;

class MapItems
{
	protected $environment;
	protected $trading;

	public function __construct(
		Trading\Entity\Sale\Container $environment,
		Trading\Setup\Model $trading
	)
	{
		$this->environment = $environment;
		$this->trading = $trading;
	}

	public function execute(Api\OrderManagement\Model\Order\Items $apiItems) : Data\ItemsMap
	{
		$items = iterator_to_array($apiItems->getIterator());

		$itemsMap = $this->searchById($items);
		$itemsMap->append($this->searchByUser($itemsMap->diff($items)));

		return $itemsMap;
	}

	/**
	 * @param Api\OrderManagement\Model\Order\Item[] $items
	 * @return Data\ItemsMap
	 */
	protected function searchById(array $items) : Data\ItemsMap
	{
		$feed = $this->trading->getExchange()->fillFeed();

		Assert::notNull($feed, 'feed');

		$found = $this->environment->product()->find($feed, $this->itemsIds($items));
		$itemsMap = new Data\ItemsMap();

		foreach ($items as $item)
		{
			$id = $item->id();

			if (isset($id, $found[$id]))
			{
				$itemsMap->set($item, $found[$id]);
			}
		}

		return $itemsMap;
	}

	/**
	 * @param Api\OrderManagement\Model\Order\Item[] $items
	 * @return string[]
	 */
	protected function itemsIds(array $items) : array
	{
		$result = [];

		foreach ($items as $item)
		{
			$id = $item->id();

			if ($id === null) { continue; }

			$result[] = $id;
		}

		return $result;
	}

	/**
	 * @param Api\OrderManagement\Model\Order\Item[] $items
	 * @return Data\ItemsMap
	 */
	protected function searchByUser(array $items) : Data\ItemsMap
	{
        $result = new Data\ItemsMap();

        if (empty($items)) { return $result; }

		$event = new Main\Event(Config::getModuleName(), Trading\EventActions::ORDER_UNKNOWN_ITEMS, [
			'ITEMS' => $items,
			'ENVIRONMENT' => $this->environment,
			'TRADING' => $this->trading,
		]);

		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() !== Main\EventResult::SUCCESS) { continue; }

			/** @var Data\ItemsMap $parameters */
			$parameters = $eventResult->getParameters();

			if ($parameters instanceof \SplObjectStorage)
			{
				foreach ($parameters as $item)
				{
					if (!in_array($item, $items, true))
					{
						throw new Main\ArgumentException('unknown item in event results');
					}

					$result->set($item, $parameters->offsetGet($item));
				}
			}
			else if ($parameters instanceof Data\ItemsMap)
			{
				foreach ($parameters as $item)
				{
					if (!in_array($item, $items, true))
					{
						throw new Main\ArgumentException('unknown item in event results');
					}

					$result->set($item, $parameters->get($item));
				}
			}
			else if (is_array($parameters))
			{
				foreach ($parameters as $key => $value)
				{
					if (!isset($items[$key]))
					{
						throw new Main\ArgumentException('unknown item key in event results');
					}

					$result->set($items[$key], $value);
				}
			}
			else
			{
				throw new Main\ArgumentException(sprintf(
					'eventResult parameters must be array or instance of %s or %s',
					\SplObjectStorage::class,
					Data\ItemsMap::class
				));
			}
		}

		return $result;
	}
}

