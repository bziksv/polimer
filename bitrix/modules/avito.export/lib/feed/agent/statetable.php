<?php
namespace Avito\Export\Push\Agent;

use Avito\Export\Watcher;
use Bitrix\Main;

/** @deprecated */
class StateTable extends Watcher\Agent\StateTable
{
	public static function migrate(Main\DB\Connection $connection) : void
	{
		// nothing
	}
}