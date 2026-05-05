<?php

namespace Avito\Export\Admin\View;

use Bitrix\Main;

class Attributes
{
	protected static $glueAttributes = [
		'class' => ' ',
	];

	public static function stringify($attributes) : string
	{
		if (!is_array($attributes)) { return (string)$attributes; }

		$htmlAttributes = [];

		foreach ($attributes as $key => $value)
		{
			if (is_numeric($key))
			{
				$htmlAttributes[] = $value;
			}
			else if ($value === false || $value === null)
			{
				continue;
			}
			else if (is_array($value))
			{
				$valueEncoded = isset(static::$glueAttributes[$key])
					? implode(static::$glueAttributes[$key], $value)
					: static::encodeJson($value);

				$htmlAttributes[] = htmlspecialcharsbx($key) . '="' . htmlspecialcharsbx($valueEncoded) . '"';
			}
			else if ($value === true || (string)$value === '')
			{
				$htmlAttributes[] = htmlspecialcharsbx($key);
			}
			else
			{
				$htmlAttributes[] = htmlspecialcharsbx($key) . '="' . htmlspecialcharsbx($value) . '"';
			}
		}

		return implode(' ', $htmlAttributes);
	}

	protected static function encodeJson($value)
	{
		$result = Main\Web\Json::encode($value, JSON_UNESCAPED_UNICODE);

		if (!Main\Application::isUtfMode())
		{
			$result = Main\Text\Encoding::convertEncoding($result, 'UTF-8', SITE_CHARSET);
		}

		return $result;
	}
}
