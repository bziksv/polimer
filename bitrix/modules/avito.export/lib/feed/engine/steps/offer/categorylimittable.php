<?php
namespace Avito\Export\Feed\Engine\Steps\Offer;

use Avito\Export\DB;
use Avito\Export\Watcher;
use Avito\Export\Concerns;
use Bitrix\Main;

class CategoryLimitTable extends DB\Table
{
	use Concerns\HasLocale;
	use Watcher\Setup\HasRepositoryRefresh;
	use Watcher\Setup\HasRepositoryChanges;

	public static function getTableName() : string
	{
		return 'avito_export_category_limit';
	}

	public static function getMap() : array
	{
		return [
			new Main\ORM\Fields\IntegerField('FEED_ID', [
				'primary' => true,
				'required' => true,
			]),
			new Main\ORM\Fields\StringField('INDEX', [
				'primary' => true,
				'required' => true,
			]),
			new Main\ORM\Fields\IntegerField('PRIMARY', [
				'primary' => true,
				'required' => true,
			]),
			new Main\ORM\Fields\IntegerField('PRIORITY', [
				'required' => true,
			]),
		];
	}
}