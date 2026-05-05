<?php
namespace Avito\Export\Data;

class Number
{
	public static function cast($value) : ?int
	{
		return is_numeric($value) ? (int)$value : null;
	}

	public static function castFloat($value) : ?float
	{
		return is_numeric($value) ? (float)$value : null;
	}
}