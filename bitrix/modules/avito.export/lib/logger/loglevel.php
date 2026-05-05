<?php
namespace Avito\Export\Logger;

use Avito\Export\Psr;

class LogLevel extends Psr\Logger\LogLevel
{
	public static function order() : array
	{
		return [
			static::EMERGENCY,
			static::ALERT,
			static::CRITICAL,
			static::ERROR,
			static::WARNING,
			static::NOTICE,
			static::INFO,
			static::DEBUG,
		];
	}
}