<?php

namespace Avito\Export\Admin\UserField\Helper;

use Bitrix\Main\Web\Json;

class Attributes
{
	public static function nameToId(string $name) : string
	{
		$result = str_replace(['[', ']', '-', '__'], '_', $name);
		$result = trim($result, '_');

		return $result;
	}

	public static function stringify($attributes) : string
	{
		if (is_array($attributes))
		{
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
					$valueEncoded = Json::encode($value);

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

			$result = implode(' ', $htmlAttributes);
		}
		else
		{
			$result = (string)$attributes;
		}

		return $result;
	}
}