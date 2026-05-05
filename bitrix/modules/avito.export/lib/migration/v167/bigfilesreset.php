<?php
namespace Avito\Export\Migration\V167;

use Avito\Export;

/** @noinspection PhpUnused */
class BigFilesReset implements Export\Migration\Patch
{
	public function version() : string
	{
		return '1.6.7';
	}

	public function run() : void
	{
		$query = Export\Feed\Setup\RepositoryTable::getList();

		while ($feed = $query->fetchObject())
		{
			if (!$feed->hasFullRefresh() || !$feed->isRefreshTooLong()) { continue; }

			$feed->activate(); // migrate to reset agent
		}
	}
}