<?php

namespace Avito\Export\Feed\Agent;

use Avito\Export;

/** @deprecated */
class Changes extends Export\Agent\Base
{
	public static function process() : bool
	{
		return Export\Watcher\Agent\Changes::process(Export\Glossary::SERVICE_FEED);
	}
}