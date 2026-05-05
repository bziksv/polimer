<?php
namespace Avito\Export\Admin;

use Avito\Export\Config;
use Avito\Export\Data;

class Path
{
	public static function crmUrl(string $path, array $query = null, bool $useDefaultHost = false) : string
	{
		$siteId = Data\Site::crmSite();
		$relativeUrl = $path . ($query !== null ? '?' . http_build_query($query) : '');

		if ($siteId === null) { return $relativeUrl; }

		return rtrim(Data\SiteDomain::publicUrl($siteId, $useDefaultHost), '/') . '/' . ltrim($relativeUrl, '/');
	}

	public static function moduleUrl(string $scriptName, array $query = null) : string
	{
		return static::pageUrl('avito_export_' . $scriptName, $query);
	}

	public static function pageUrl(string $scriptName, array $query = null) : string
	{
		$scriptName = mb_strtolower($scriptName);
		$path = BX_ROOT . '/admin/' . $scriptName . '.php';

		if ($query !== null)
		{
			$path .= '?' . http_build_query($query);
		}

		return $path;
	}

	public static function toolsUrl(string $relative, array $query = null) : string
	{
		$relative = mb_strtolower($relative);
		$path = BX_ROOT . '/tools/' . Config::getModuleName() . '/' . $relative . '.php';

		if ($query !== null)
		{
			$path .= '?' . http_build_query($query);
		}

		return $path;
	}
}