<?php

namespace Avito\Export;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

class Config
{
	public const LANG_PREFIX = 'AVITO_EXPORT_';

	protected const SERIALIZED_PREFIX = '_AVITO_CONFIG_:';

	public static function getLangMessage($code, $replaces = null, $fallback = null)
	{
		$prefix = static::LANG_PREFIX;

		$result = Loc::getMessage($prefix . $code, $replaces) ?: $fallback;

		if ($result === null)
		{
			$result = $code;
		}

		return $result;
	}

	public static function getNamespace():string
	{
		return '\\' . __NAMESPACE__;
	}

	public static function getModulePath():string
	{
		return __DIR__;
	}

	public static function getModuleName():string
	{
		return 'avito.export';
	}

	public static function getOption(string $name, $default = "", $siteId = false)
	{
		$moduleName = static::getModuleName();
		$optionValue = Option::get($moduleName, $name, null, $siteId);

		if (strpos($optionValue, static::SERIALIZED_PREFIX) === 0)
		{
			$unSerializedValue = unserialize(substr($optionValue, mb_strlen(static::SERIALIZED_PREFIX)), [
				'allowed_classes' => false,
			]);

			$optionValue = ($unSerializedValue !== false ? $unSerializedValue : null);
		}

		if (!isset($optionValue))
		{
			$optionValue = $default;
		}

		return $optionValue;
	}

	public static function setOption(string $name, $value = "", $siteId = ""):void
	{
		$moduleName = static::getModuleName();

		if (!is_scalar($value))
		{
			$value = static::SERIALIZED_PREFIX . serialize($value);
		}

		Option::set($moduleName, $name, $value, $siteId);
	}

	public static function removeOption(string $name) : void
	{
		$moduleName = static::getModuleName();

		Option::delete($moduleName, [ 'name' => $name ]);
	}
}
