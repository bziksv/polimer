<?php
namespace Avito\Export\Utils\Logger;

use Avito\Export\Psr;

class NullLogger extends Psr\Logger\AbstractLogger
{
	public function log($level, $message, array $context = array()) : void
	{
		// nothing
	}
}

