<?php
namespace Avito\Export\Feed\Logger;

use Avito\Export;
use Avito\Export\Psr;

/** @deprecated  */
class Logger extends Export\Logger\Logger
{
	public function __construct(int $feedId)
	{
		parent::__construct(Export\Glossary::SERVICE_FEED, $feedId);
	}
}