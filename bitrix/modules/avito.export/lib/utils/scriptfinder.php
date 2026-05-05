<?php
namespace Avito\Export\Utils;

use Avito\Export\Data;
use Bitrix\Main;

class ScriptFinder
{
	protected $siteId;

	public function __construct(string $siteId)
	{
		$this->siteId = $siteId;
	}

	public function resolveUrl(string $url) : ?string
	{
		$path = $this->searchRewrite($url) ?? $this->searchFile($url);

		if ($path === null) { return null; }

		return $this->toAbsolute($path);
	}

	protected function searchRewrite(string $url) : ?string
	{
		$result = null;

		foreach (Main\UrlRewriter::getList($this->siteId) as $rule)
		{
			if (preg_match($rule['CONDITION'], $url))
			{
				$result = $rule['PATH'];
				break;
			}
		}

		return $result;
	}

	protected function searchFile(string $url) : ?string
	{
		$path = parse_url($url, PHP_URL_PATH);

		if (!is_string($path)) { return null; }

		if (!preg_match('/\.php$/', $path))
		{
			$path = rtrim($path, '/') . '/index.php';
		}

		return $path;
	}

	protected function toAbsolute(string $path) : string
	{
		$root = Data\Site::documentRoot($this->siteId);

		return Main\IO\Path::combine($root, $path);
	}
}