<?php
namespace Avito\Export\Admin;

use Avito\Export\Config;

class Access
{
	public const RIGHTS_READ = 'R';
	public const RIGHTS_WRITE = 'W';

	public static function isReadAllowed() : bool
	{
		return static::hasRights(static::RIGHTS_READ);
	}

	public static function isWriteAllowed() : bool
	{
		return static::hasRights(static::RIGHTS_WRITE);
	}

	public static function hasRights(string $level) : bool
	{
		$rights = static::getRights();

		return $rights === $level || $rights > $level;
	}

	protected static function getRights() : string
	{
		$moduleId = Config::getModuleName();

		return (string)\CMain::GetUserRight($moduleId);
	}
}