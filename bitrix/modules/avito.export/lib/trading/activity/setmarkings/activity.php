<?php
namespace Avito\Export\Trading\Activity\SetMarkings;

use Avito\Export\Api\OrderManagement\Model\Order;
use Avito\Export\Concerns;
use Avito\Export\Api;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity as TradingActivity;

class Activity extends TradingActivity\Reference\FormActivity
{
	use Concerns\HasLocale;

	public function title(Order $order) : string
	{
		return self::getLocale('TITLE', null, $this->name);
	}

	public function order() : int
	{
		return 600;
	}

	public function path() : string
	{
		return 'send/marking';
	}

	public function payload(array $values) : array
	{
		return $values + [
			'itemsMapped' => true,
		];
	}

	public function fields(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		$result = [];
		$items = $this->mapItems($saleOrder, $externalOrder->items());
		$markingItems = $this->onlyMarkingItems($saleOrder, $items);

		if (empty($markingItems)) { $markingItems = $externalOrder->items(); }

		/** @var Api\OrderManagement\Model\Order\Item $item */
		foreach ($markingItems as $item)
		{
			$result['codes[' . $item->avitoId() . ']'] = [
				'TYPE' => 'string',
				'NAME' => $item->title(),
				'MULTIPLE' => 'Y',
				'MANDATORY' => 'Y',
				'SETTINGS' => [
					'SIZE' => 30,
					'MULTIPLE_CNT' => $item->count(),
					'MULTIPLE_FIXED' => 'Y',
				],
			];
		}

		return $result;
	}

	public function values(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order $externalOrder) : array
	{
		$items = $this->mapItems($saleOrder, $externalOrder->items());
		$markingItems = $this->onlyMarkingItems($saleOrder, $items);
		$result = [
			'codes' => [],
		];

		/** @var Api\OrderManagement\Model\Order\Item $item */
		foreach ($markingItems as $basketCode => $item)
		{
			$result['codes'][$item->avitoId()] = $saleOrder->itemMarkingCodes($basketCode);
		}

		return $result;
	}

	protected function onlyMarkingItems(Trading\Entity\Sale\Order $saleOrder, array $items) : array
	{
		$result = [];

		/** @var Api\OrderManagement\Model\Order\Item $item */
		foreach ($items as $basketCode => $item)
		{
			$itemData = $saleOrder->itemData($basketCode);

			if (empty($itemData['MARKING_CODE_GROUP'])) { continue; }

			$result[$basketCode] = $item;
		}

		return $result;
	}

	protected function mapItems(Trading\Entity\Sale\Order $saleOrder, Api\OrderManagement\Model\Order\Items $itemCollection) : array
	{
		$itemsMap = array_flip($saleOrder->itemsExternalMap());
		$result = [];

		/** @var Api\OrderManagement\Model\Order\Item $item */
		foreach ($itemCollection as $item)
		{
			$avitoId = $item->avitoId();
			$basketCode = $itemsMap[$avitoId] ?? null;

			if ($basketCode === null) { continue; }

			$result[$basketCode] = $item;
		}

		return $result;
	}
}
