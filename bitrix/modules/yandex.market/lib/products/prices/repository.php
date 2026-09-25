<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Main;

class Repository
{
	public static function enqueue($productId)
	{
		$productId = (int)$productId;

		if ($productId <= 0) { return; }

		$existing = ChangeTable::getList([
			'filter' => [ '=PRODUCT_ID' => $productId ],
			'select' => [ 'ID' ],
			'limit' => 1,
		])->fetch();

		$timestamp = new Main\Type\DateTime();

		if ($existing)
		{
			ChangeTable::update($existing['ID'], [
				'TIMESTAMP_X' => $timestamp,
			]);
		}
		else
		{
			ChangeTable::add([
				'PRODUCT_ID' => $productId,
				'TIMESTAMP_X' => $timestamp,
			]);
		}
	}

	public static function fetchPending($limit = 2000)
	{
		$limit = max(1, min(2000, (int)$limit));
		$result = [];

		$query = ChangeTable::getList([
			'order' => [ 'TIMESTAMP_X' => 'ASC', 'ID' => 'ASC' ],
			'select' => [ 'ID', 'PRODUCT_ID' ],
			'limit' => $limit,
		]);

		while ($row = $query->fetch())
		{
			$result[] = [
				'ID' => (int)$row['ID'],
				'PRODUCT_ID' => (int)$row['PRODUCT_ID'],
			];
		}

		return $result;
	}

	public static function removeByIds(array $ids)
	{
		$ids = array_values(array_filter(array_map('intval', $ids)));

		if (empty($ids)) { return; }

		foreach (array_chunk($ids, 500) as $chunk)
		{
			ChangeTable::deleteBatch([
				'filter' => [ '@ID' => $chunk ],
			]);
		}
	}

	public static function countPending()
	{
		return (int)ChangeTable::getCount();
	}
}
