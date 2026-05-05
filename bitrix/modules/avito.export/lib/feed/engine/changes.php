<?php

namespace Avito\Export\Feed\Engine;

use Avito\Export\Watcher;
use Avito\Export\Glossary;

/** @deprecated */
class Changes
{
	public static function register(int $feedId, string $entityType, int $entityId) : void
	{
		Watcher\Engine\Changes::register(
			Glossary::SERVICE_FEED,
			$feedId,
			$entityType,
			$entityId
		);
	}

	public static function releaseAll(int $feedId) : void
	{
		Watcher\Engine\Changes::releaseAll(
			Glossary::SERVICE_FEED,
			$feedId
		);
	}
}