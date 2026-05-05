<?php

namespace Avito\Export\Data;

class Phone
{
	public static function sanitize(string $phone) : string
	{
		$result = preg_replace('/[^+\d]/', '', $phone);

		if (mb_strpos($result, '8') === 0)
		{
			$result = '+7' . mb_substr($result, 1);
		}

		return $result;
	}
}
