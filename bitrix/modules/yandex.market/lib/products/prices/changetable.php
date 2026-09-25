<?php
namespace Yandex\Market\Products\Prices;

use Bitrix\Main;
use Yandex\Market;

class ChangeTable extends Market\Reference\Storage\Table
{
	public static function getTableName()
	{
		return 'yamarket_products_price_change';
	}

	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\IntegerField('PRODUCT_ID', [
				'required' => true,
			]),
			new Main\Entity\DatetimeField('TIMESTAMP_X', [
				'required' => true,
			]),
		];
	}

	public static function createIndexes(Main\DB\Connection $connection)
	{
		$tableName = static::getTableName();
		$connection->createIndex($tableName, 'IX_YAMARKET_PRODUCTS_PRICE_CHANGE_PRODUCT', [ 'PRODUCT_ID' ], true);
	}
}
