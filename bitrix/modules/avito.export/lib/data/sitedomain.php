<?php
namespace Avito\Export\Data;

use Bitrix\Main;

class SiteDomain
{
	public static function isKnown(string $host) : bool
	{
		return static::search($host) !== null;
	}

	public static function search(string $host) : ?string
	{
		return (
			static::searchByServerName($host)
			?? static::searchByDomain($host)
			?? static::searchByDefault($host)
		);
	}

	protected static function searchByServerName(string $host) : ?string
	{
		$site = Main\SiteTable::getRow([
			'filter' => [ '=SERVER_NAME' => $host ],
			'select' => [ 'LID' ],
		]);

		return $site['LID'] ?? null;
	}

	protected static function searchByDomain(string $host) : ?string
	{
		$site = Main\SiteDomainTable::getRow([
			'filter' => [ '=DOMAIN' => $host ],
			'select' => [ 'LID' ],
		]);

		return $site['LID'] ?? null;
	}

	protected static function searchByDefault(string $host) : ?string
	{
		if ($host !== static::hostFromMainConfig()) { return null; }

		$siteId = \CLang::GetDefSite();

		return $siteId ?? null;
	}

	protected static function hostFromMainConfig() : string
	{
		return Main\Config\Option::get('main', 'server_name');
	}

	public static function publicUrl(string $siteId, bool $useDefaultHost = false, bool $https = null) : string
	{
		$url = static::url($siteId, $useDefaultHost, $https);
		$dir = '/' . trim(Site::dir($siteId), '/');

		return $url . $dir;
	}

	public static function defaultUrl(bool $https = null) : string
	{
		$https = $https ?? static::isHttps();
		$host = static::defaultHost();

		return sprintf('%s://%s', $https ? 'https' : 'http', $host);
	}

	public static function url(string $siteId, bool $useDefaultHost = false, bool $https = null) : ?string
	{
		$https = $https ?? static::isHttps();
		$host = static::host($siteId, $useDefaultHost);

		if ($host === null) { return null; }

		return sprintf('%s://%s', $https ? 'https' : 'http', $host);
	}

	public static function host(string $siteId, bool $useDefaultHost = false) : ?string
	{
		$siteHost = Site::serverName($siteId) ?: static::hostFromDomains($siteId);

		if ($siteHost || !$useDefaultHost) { return $siteHost; }

		return static::defaultHost();
	}

	public static function defaultHost() : string
	{
		return static::hostFromRequest() ?: static::hostFromMainConfig();
	}

	protected static function hostFromDomains(string $siteId) : ?string
	{
		$row = Main\SiteDomainTable::getRow([
			'filter' => [ '=LID' => $siteId ],
			'select' => [ 'DOMAIN' ],
		]);

		if (empty($row)) { return null; }

		$host = trim($row['DOMAIN']);

		if ($host === '') { return null; }

		return static::decodeDomain($host);
	}

	protected static function hostFromRequest() : ?string
	{
		$host = Main\Application::getInstance()->getContext()->getRequest()->getHttpHost();
		$host = preg_replace('/:\d+$/', '', $host);

		if ($host === '') { return null; }

		return $host;
	}

	protected static function isHttps() : bool
	{
		return Main\Application::getInstance()->getContext()->getRequest()->isHttps();
	}

	protected static function decodeDomain(string $encoded) : string
	{
		$errors = [];
		$decoded = \CBXPunycode::ToUnicode($encoded, $errors);

		return $decoded ?: $encoded;
	}
}