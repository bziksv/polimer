<?php
namespace Avito\Export\Data;

class MarkingCode
{
	public const GROUP_SPLITTER = "\u{001d}";

	public static function sanitize(string $value) : string
	{
		$value = str_replace(static::GROUP_SPLITTER, '', $value);

		if (preg_match('/^(01\d{14}21[A-Za-z0-9!"%&\'*+.\/_,:;=<>?\\\-]{13,27}?)91/', $value, $matches))
		{
			return $matches[1];
		}

		return $value;
	}
}