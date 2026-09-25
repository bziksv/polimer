<?php

namespace Yandex\Market\Reference\Storage\Field;

use Yandex\Market;

class FuzzySerializer extends Serializer
{
	const PREFIX = '__SERIALIZED__:';

	public static function serialize($value)
	{
		if ($value === null || is_scalar($value))
		{
			$result = (string)$value;

			// Скаляр с префиксом иначе на чтении уйдёт в unserialize()
			if (Market\Data\TextString::getPosition($result, static::PREFIX) === 0)
			{
				$result = static::PREFIX . serialize($result);
			}
		}
		else
		{
			$result = static::PREFIX . serialize($value);
		}

		return $result;
	}

	public static function unserialize($value)
	{
		if (Market\Data\TextString::getPosition($value, static::PREFIX) === 0)
		{
			$prefixLength = Market\Data\TextString::getLength(static::PREFIX);
			$serialized = Market\Data\TextString::getSubstring($value, $prefixLength);
			$result = Market\Utils\PhpSerializer::decode($serialized);
		}
		else if ((string)$value !== '')
		{
			$result = $value;
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}
