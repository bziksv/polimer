<?php
namespace Avito\Export\Push\Agent;

use Avito\Export\Watcher;
use Bitrix\Main;

/** @deprecated */
class ChangesTable extends Watcher\Agent\ChangesTable
{
	public static function migrate(Main\DB\Connection $connection) : void
	{
		// nothing
	}
}