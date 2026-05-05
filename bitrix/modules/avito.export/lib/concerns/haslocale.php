<?php
namespace Avito\Export\Concerns;

use Bitrix\Main\Localization\Loc;
use Avito\Export\Config;
use Avito\Export\Utils\MessageRegistry;

trait HasLocale
{
	private static function getLocalePrefix() : string
	{
		return MessageRegistry::getModuleInstance()->getPrefix(self::class);
	}

	private static function includeLocale(): void
	{
		MessageRegistry::getModuleInstance()->load(self::class);
	}

	protected static function getLocale($code, $replaces = null, $fallback = null) : ?string
	{
		self::includeLocale();

		$fullCode = Config::LANG_PREFIX . self::getLocalePrefix() . '_' . $code;

		$result = Loc::getMessage($fullCode, $replaces, 'ru');

		if ($result === '' || $result === null)
		{
			$result = $fallback ?? $code;
		}

		return $result;
	}
}