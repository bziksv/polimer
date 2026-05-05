<?php

namespace Avito\Export\Utils;

use Bitrix\Main;

class Field
{
	public const GLUE_DOT = 'dot';
	public const GLUE_BRACKET = 'bracket';

	public static function getChainValue($values, $key, $glue = Field::GLUE_DOT)
	{
		$keyParts = static::splitKey($key, $glue);

		$lastLevel = $values;

		foreach ($keyParts as $keyPart)
		{
			if (isset($lastLevel[$keyPart]))
			{
				$lastLevel = $lastLevel[$keyPart];
			}
			else
			{
				$lastLevel = null;
				break;
			}
		}

		return $lastLevel;
	}

	public static function setChainValue(&$values, $key, $value, $glue = Field::GLUE_DOT) : void
	{
		$keyParts = static::splitKey($key, $glue);
		$keyPartIndex = 0;
		$keyPartCount = count($keyParts);
		$lastLevel = &$values;

		foreach ($keyParts as $keyPart)
		{
			if ($keyPartCount === $keyPartIndex + 1)
			{
				$lastLevel[$keyPart] = $value;
			}
			else
			{
				if (!isset($lastLevel[$keyPart]) || !is_array($lastLevel[$keyPart]))
				{
					$lastLevel[$keyPart] = [];
				}

				$lastLevel = &$lastLevel[$keyPart];
			}

			$keyPartIndex++;
		}
	}

	public static function splitKey($key, $glue = Field::GLUE_DOT) : array
	{
		if (is_array($key))
		{
			$result = $key;
		}
		else if ($glue === static::GLUE_DOT)
		{
			$result = explode('.', $key);
		}
		else if ($glue === static::GLUE_BRACKET)
		{
			$result = static::splitKeyByBrackets($key);
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown glue %s', $glue));
		}

		return $result;
	}

	protected static function splitKeyByBrackets($key) : array
	{
		$keyOffset = 0;
		$keyLength = BinaryString::length($key);
		$keyChain = [];

		do
		{
			if ($keyOffset === 0)
			{
				$arrayEnd = BinaryString::position($key, '[');

				if ($arrayEnd === false)
				{
					$keyPart = $key;
					$keyOffset = $keyLength;
				}
				else if ($arrayEnd === 0)
				{
					$keyOffset = 1;
					continue;
				}
				else
				{
					$keyPart = BinaryString::substring($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 1;
				}
			}
			else
			{
				$arrayEnd = BinaryString::position($key, ']', $keyOffset);

				if ($arrayEnd === false)
				{
					$keyPart = BinaryString::substring($key, $keyOffset);
					$keyOffset = $keyLength;
				}
				else
				{
					$keyPart = BinaryString::substring($key, $keyOffset, $arrayEnd - $keyOffset);
					$keyOffset = $arrayEnd + 2;
				}
			}

			if ((string)$keyPart !== '')
			{
				$keyChain[] = $keyPart;
			}
			else
			{
				break;
			}
		}
		while ($keyOffset < $keyLength);

		return $keyChain;
	}
}