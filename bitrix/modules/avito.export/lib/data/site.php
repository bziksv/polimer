<?php
namespace Avito\Export\Data;

use Avito\Export\Concerns;
use Bitrix\Main;

class Site
{
	use Concerns\HasOnceStatic;

	public static function crmSite() : ?string
	{
		return static::onceStatic('crmSite', static function() {
			$row = Main\SiteTemplateTable::getRow([
				'filter' => [ '%TEMPLATE' => 'bitrix24' ],
			]);

			return $row['SITE_ID'] ?? null;
		});
	}

	public static function compileUrl(string $siteId, string $template) : string
	{
		$fields = static::fields($siteId);

		return str_replace(
			[ '#SITE_DIR#', '#SERVER_NAME#', '#LANG#', '#SITE#' ],
			[ $fields['DIR'], $fields['SERVER_NAME'], $fields['DIR'], $siteId ],
			$template
		);
	}

	public static function documentRoot(string $siteId) : string
	{
		return Main\SiteTable::getDocumentRoot($siteId);
	}

	public static function serverName(string $siteId) : ?string
	{
		$fields = static::fields($siteId);
		$host = trim($fields['SERVER_NAME']);

		if ($host === '') { return null; }

		return $host;
	}

	public static function dir(string $siteId) : string
	{
		$fields = static::fields($siteId);
		$dir = trim($fields['DIR'], '/');

		return '/' . $dir;
	}

	protected static function fields(string $siteId) : array
	{
		return static::onceStatic('fields', static function($siteId) {
			$site = Main\SiteTable::getRow([
				'filter' => [ '=LID' => $siteId ],
				'select' => [ 'DIR', 'SERVER_NAME' ],
			]);

			if (!$site)
			{
				throw new Main\SystemException(sprintf('cant load site %s', $siteId));
			}

			return $site;
		}, $siteId);
	}
}