<?php
namespace Avito\Export\Feed\Logger;

use Avito\Export;
use Bitrix\Main;

/** @deprecated  */
class Table extends Export\Logger\Table
{
	public static function migrate(Main\DB\Connection $connection) : void
	{
		// nothing
	}
}