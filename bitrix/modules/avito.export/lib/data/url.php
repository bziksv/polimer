<?php
namespace Avito\Export\Data;

use Bitrix\Main;

class Url
{
	public static function normalizeHost(string $host) : string
	{
		return preg_replace('/:\d+$/', '', $host);
	}

	/** @noinspection HttpUrlsUsage */
	public static function replaceHost(string $url, string $host, bool $useHttps = null) : string
	{
		$uri = new Main\Web\Uri($url);
		$uri = $uri->setHost($host);
		$result = $uri->getUri();

		if ($useHttps !== null)
		{
			$result = preg_replace('#^https?://#i', $useHttps ? 'https://' : 'http://', $result);
		}

		return $result;
	}

	public static function similar(string $a, string $b, bool $isDevelopment = false) : bool
	{
		$a = mb_strtolower($a);
		$b = mb_strtolower($b);
		$aUri = new Main\Web\Uri($a);
		$bUri = new Main\Web\Uri($b);

		if ($aUri->getPathQuery() !== $bUri->getPathQuery()) { return false; }
		if ($aUri->getHost() === $bUri->getHost()) { return true; }

		$aHostPartials = explode('.', $aUri->getHost());
		$bHostPartials = explode('.', $bUri->getHost());
		$aHostPartials = array_slice($aHostPartials, -3);
		$bHostPartials = array_slice($bHostPartials, -3);

		if ($isDevelopment && count($aHostPartials) !== count($bHostPartials)) { return false; }

		$aHostPartials = array_slice($aHostPartials, -2);
		$bHostPartials = array_slice($bHostPartials, -2);

		return implode('.', $aHostPartials) === implode('.', $bHostPartials);
	}
}