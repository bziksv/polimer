<?php

namespace Avito\Export\Feed\Agent;

use Avito\Export;

/** @deprecated */
class Refresh extends Export\Agent\Base
{
	public static function start($feedId) : void
	{
		Export\Watcher\Agent\Refresh::start(Export\Glossary::SERVICE_FEED, $feedId);
	}

	public static function process($feedId) : bool
	{
		return Export\Watcher\Agent\Refresh::process(Export\Glossary::SERVICE_FEED, $feedId);
	}
}
