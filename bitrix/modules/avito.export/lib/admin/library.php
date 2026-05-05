<?php
namespace Avito\Export\Admin;

use Bitrix\Main;
use Avito\Export\Config;

class Library
{
	public static function resolve(string $name, array $fallback = []) : string
	{
		$option = (string)Config::getOption('library_' . $name);
		$variants = array_merge(
			[ $name ],
			$fallback
		);

		if ($option !== '' && in_array($option, $variants, true)) { return $option; }

		$result = $name;

		foreach ($variants as $variant)
		{
			if (\CJSCore::isExtensionLoaded($variant))
			{
				$result = $variant;
				break;
			}
		}

		return $result;
	}

	public static function includedScript(string $name) : bool
	{
		$assets = Main\Page\Asset::getInstance();
		$reflection = new \ReflectionProperty($assets, 'js');
		$reflection->setAccessible(true);
		$jsList = $reflection->getValue($assets);

		if (!is_array($jsList)) { return false; }

		$result = false;

		foreach ($jsList as $path => $unused)
		{
			if (mb_stripos($path, $name) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}
}