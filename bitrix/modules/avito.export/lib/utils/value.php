<?php

namespace Avito\Export\Utils;

class Value
{
	public static function isEmpty($value) : bool
	{
		if (is_scalar($value))
		{
			$result = (string)$value === '';
		}
		else
		{
			$result = empty($value);
		}

		return $result;
	}
}